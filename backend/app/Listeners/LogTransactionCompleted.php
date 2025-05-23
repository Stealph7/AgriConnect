<?php

namespace App\Listeners;

use App\Events\TransactionCompleted;
use App\Models\Notification;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

class LogTransactionCompleted
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Gérer l'événement de complétion de transaction
     */
    public function handle(TransactionCompleted $event)
    {
        $transaction = $event->transaction;
        $buyer = $event->buyer;
        $seller = $event->seller;
        $product = $event->product;

        try {
            // Logger la transaction
            Log::info('Transaction complétée', [
                'transaction_id' => $transaction->id,
                'product' => $product->name,
                'quantity' => $transaction->quantity,
                'total_amount' => $transaction->total_amount,
                'buyer' => $buyer->name,
                'seller' => $seller->name,
            ]);

            // Notifier l'acheteur
            $this->notifyBuyer($transaction);

            // Notifier le vendeur
            $this->notifySeller($transaction);

            // Mettre à jour les statistiques
            $this->updateStatistics($transaction);

            // Vérifier le stock après la transaction
            $this->checkProductStock($transaction);

            // Notifier les administrateurs pour les grandes transactions
            $this->notifyAdminsIfLargeTransaction($transaction);

        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement de TransactionCompleted', [
                'error' => $e->getMessage(),
                'transaction_id' => $transaction->id,
            ]);
        }
    }

    /**
     * Notifier l'acheteur
     */
    private function notifyBuyer($transaction)
    {
        // Créer une notification dans l'application
        Notification::create([
            'user_id' => $transaction->buyer_id,
            'type' => 'transaction_completed',
            'title' => 'Achat confirmé',
            'content' => "Votre achat de {$transaction->quantity} {$transaction->product->unit} de {$transaction->product->name} a été confirmé",
            'data' => [
                'transaction_id' => $transaction->id,
                'product_id' => $transaction->product_id,
                'amount' => $transaction->total_amount,
            ],
        ]);

        // Envoyer un SMS si activé
        if ($transaction->buyer->sms_notifications_enabled) {
            $this->smsService->send(
                $transaction->buyer->phone,
                "Achat confirmé : {$transaction->quantity} {$transaction->product->unit} de {$transaction->product->name} pour {$transaction->total_amount} FCFA"
            );
        }
    }

    /**
     * Notifier le vendeur
     */
    private function notifySeller($transaction)
    {
        // Créer une notification dans l'application
        Notification::create([
            'user_id' => $transaction->seller_id,
            'type' => 'transaction_completed',
            'title' => 'Vente confirmée',
            'content' => "Votre vente de {$transaction->quantity} {$transaction->product->unit} de {$transaction->product->name} a été confirmée",
            'data' => [
                'transaction_id' => $transaction->id,
                'product_id' => $transaction->product_id,
                'amount' => $transaction->total_amount,
            ],
        ]);

        // Envoyer un SMS si activé
        if ($transaction->seller->sms_notifications_enabled) {
            $this->smsService->send(
                $transaction->seller->phone,
                "Vente confirmée : {$transaction->quantity} {$transaction->product->unit} de {$transaction->product->name} pour {$transaction->total_amount} FCFA"
            );
        }
    }

    /**
     * Mettre à jour les statistiques
     */
    private function updateStatistics($transaction)
    {
        // Mettre à jour les statistiques de l'acheteur
        $buyerStats = $transaction->buyer->statistics()->firstOrCreate([]);
        $buyerStats->increment('total_purchases');
        $buyerStats->increment('total_spent', $transaction->total_amount);

        // Mettre à jour les statistiques du vendeur
        $sellerStats = $transaction->seller->statistics()->firstOrCreate([]);
        $sellerStats->increment('total_sales');
        $sellerStats->increment('total_earned', $transaction->total_amount);

        // Mettre à jour les statistiques du produit
        $productStats = $transaction->product->statistics()->firstOrCreate([]);
        $productStats->increment('total_sales');
        $productStats->increment('total_quantity_sold', $transaction->quantity);
        $productStats->increment('total_revenue', $transaction->total_amount);
    }

    /**
     * Vérifier le stock du produit
     */
    private function checkProductStock($transaction)
    {
        $product = $transaction->product;

        // Si le stock est bas (moins de 10% de la quantité initiale)
        if ($product->quantity <= ($product->initial_quantity * 0.1)) {
            Notification::create([
                'user_id' => $product->user_id,
                'type' => 'stock_alert',
                'title' => 'Stock bas',
                'content' => "Le stock de {$product->name} est bas ({$product->quantity} {$product->unit} restants)",
                'data' => [
                    'product_id' => $product->id,
                    'quantity' => $product->quantity,
                ],
            ]);
        }
    }

    /**
     * Notifier les administrateurs pour les grandes transactions
     */
    private function notifyAdminsIfLargeTransaction($transaction)
    {
        // Définir le seuil pour les grandes transactions (ex: 1 000 000 FCFA)
        $threshold = config('agriconnect.large_transaction_threshold', 1000000);

        if ($transaction->total_amount >= $threshold) {
            $admins = \App\Models\User::where('role', 'admin')->get();

            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'large_transaction',
                    'title' => 'Grande transaction détectée',
                    'content' => "Une transaction de {$transaction->total_amount} FCFA a été complétée",
                    'data' => [
                        'transaction_id' => $transaction->id,
                        'amount' => $transaction->total_amount,
                        'buyer' => $transaction->buyer->name,
                        'seller' => $transaction->seller->name,
                    ],
                ]);
            }
        }
    }
}
