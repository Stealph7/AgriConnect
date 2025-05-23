<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Services\DroneService;
use App\Services\TransactionService;
use App\Services\StatisticsService;
use App\Models\User;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\DroneData;
use Carbon\Carbon;

class Kernel extends ConsoleKernel
{
    /**
     * Définir les commandes de l'application
     */
    protected $commands = [
        //
    ];

    /**
     * Définir le planning des commandes
     */
    protected function schedule(Schedule $schedule)
    {
        // Nettoyage quotidien des données temporaires
        $schedule->command('temp:clean')->daily();

        // Archivage des anciennes données
        $schedule->call(function () {
            app(DroneService::class)->archiveOldData(90); // Archives après 90 jours
        })->weekly();

        // Génération des rapports hebdomadaires
        $schedule->call(function () {
            $this->generateWeeklyReports();
        })->weeklyOn(1, '01:00'); // Chaque lundi à 1h du matin

        // Vérification des stocks bas
        $schedule->call(function () {
            $this->checkLowStock();
        })->dailyAt('06:00');

        // Envoi des alertes météo
        $schedule->call(function () {
            $this->sendWeatherAlerts();
        })->twiceDaily(6, 18);

        // Mise à jour des statistiques
        $schedule->call(function () {
            $this->updateStatistics();
        })->hourly();

        // Nettoyage des notifications lues
        $schedule->call(function () {
            $this->cleanOldNotifications();
        })->monthly();

        // Vérification des transactions en attente
        $schedule->call(function () {
            $this->checkPendingTransactions();
        })->everyThirtyMinutes();

        // Envoi des rappels aux utilisateurs inactifs
        $schedule->call(function () {
            $this->sendInactivityReminders();
        })->weekly();

        // Mise à jour des prix moyens du marché
        $schedule->call(function () {
            $this->updateMarketPrices();
        })->dailyAt('00:00');

        // Sauvegarde de la base de données
        $schedule->command('backup:run')->dailyAt('02:00');
    }

    /**
     * Générer les rapports hebdomadaires
     */
    private function generateWeeklyReports()
    {
        $statisticsService = app(StatisticsService::class);
        
        // Générer le rapport global
        $reportUrl = $statisticsService->generateStatisticsReport();

        // Notifier les administrateurs
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            \App\Models\Notification::create([
                'user_id' => $admin->id,
                'type' => 'weekly_report',
                'title' => 'Rapport hebdomadaire disponible',
                'content' => 'Le rapport hebdomadaire des statistiques est disponible.',
                'data' => ['report_url' => $reportUrl],
            ]);
        }
    }

    /**
     * Vérifier les stocks bas
     */
    private function checkLowStock()
    {
        $lowStockProducts = Product::where('quantity', '<=', \DB::raw('initial_quantity * 0.1'))
            ->where('status', 'approved')
            ->get();

        foreach ($lowStockProducts as $product) {
            // Notifier le producteur
            \App\Models\Notification::create([
                'user_id' => $product->user_id,
                'type' => 'low_stock',
                'title' => 'Stock bas',
                'content' => "Le stock de {$product->name} est bas ({$product->quantity} {$product->unit} restants)",
            ]);
        }
    }

    /**
     * Envoyer les alertes météo
     */
    private function sendWeatherAlerts()
    {
        // Récupérer les données météo par région
        $weatherData = $this->getWeatherData();

        foreach ($weatherData as $region => $data) {
            if ($this->shouldSendWeatherAlert($data)) {
                // Trouver les utilisateurs de la région
                $users = User::where('region', $region)
                    ->where('weather_alerts_enabled', true)
                    ->get();

                foreach ($users as $user) {
                    // Créer l'alerte SMS
                    \App\Models\SmsAlert::create([
                        'user_id' => $user->id,
                        'type' => 'weather',
                        'content' => $this->formatWeatherAlert($data),
                    ]);
                }
            }
        }
    }

    /**
     * Mettre à jour les statistiques
     */
    private function updateStatistics()
    {
        $statisticsService = app(StatisticsService::class);
        $stats = $statisticsService->getGlobalStats();

        // Sauvegarder les statistiques dans le cache
        \Cache::put('global_statistics', $stats, now()->addHour());
    }

    /**
     * Nettoyer les anciennes notifications
     */
    private function cleanOldNotifications()
    {
        // Supprimer les notifications lues de plus de 3 mois
        \App\Models\Notification::where('is_read', true)
            ->where('created_at', '<', now()->subMonths(3))
            ->delete();
    }

    /**
     * Vérifier les transactions en attente
     */
    private function checkPendingTransactions()
    {
        $pendingTransactions = Transaction::where('status', 'pending')
            ->where('created_at', '<', now()->subHours(24))
            ->get();

        foreach ($pendingTransactions as $transaction) {
            // Notifier l'acheteur et le vendeur
            $message = "La transaction #{$transaction->id} est en attente depuis plus de 24 heures.";
            
            \App\Models\Notification::create([
                'user_id' => $transaction->buyer_id,
                'type' => 'transaction_reminder',
                'title' => 'Rappel de transaction',
                'content' => $message,
            ]);

            \App\Models\Notification::create([
                'user_id' => $transaction->seller_id,
                'type' => 'transaction_reminder',
                'title' => 'Rappel de transaction',
                'content' => $message,
            ]);
        }
    }

    /**
     * Envoyer des rappels aux utilisateurs inactifs
     */
    private function sendInactivityReminders()
    {
        $inactiveUsers = User::where('last_login_at', '<', now()->subDays(30))
            ->where('is_active', true)
            ->get();

        foreach ($inactiveUsers as $user) {
            \App\Models\Notification::create([
                'user_id' => $user->id,
                'type' => 'inactivity_reminder',
                'title' => 'Vous nous manquez !',
                'content' => 'Cela fait un moment que vous ne vous êtes pas connecté. Revenez voir les dernières offres !',
            ]);
        }
    }

    /**
     * Mettre à jour les prix moyens du marché
     */
    private function updateMarketPrices()
    {
        $products = Product::where('status', 'approved')
            ->select('name', \DB::raw('AVG(price) as avg_price'))
            ->groupBy('name')
            ->get();

        foreach ($products as $product) {
            \Cache::put(
                "market_price_{$product->name}",
                $product->avg_price,
                now()->addDay()
            );
        }
    }
}
