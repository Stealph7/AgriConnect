<?php

namespace App\Services;

use App\Models\User;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatisticsService
{
    /**
     * Obtenir les statistiques globales de la plateforme
     */
    public function getGlobalStats()
    {
        return [
            'users' => $this->getUserStats(),
            'transactions' => $this->getTransactionStats(),
            'products' => $this->getProductStats(),
            'regions' => $this->getRegionalStats(),
        ];
    }

    /**
     * Obtenir les statistiques des utilisateurs
     */
    public function getUserStats()
    {
        return [
            'total_users' => User::count(),
            'users_by_role' => User::select('role', DB::raw('count(*) as count'))
                ->groupBy('role')
                ->pluck('count', 'role'),
            'users_by_region' => User::select('region', DB::raw('count(*) as count'))
                ->groupBy('region')
                ->pluck('count', 'region'),
            'active_users' => User::where('last_login_at', '>=', now()->subDays(30))->count(),
            'new_users' => User::where('created_at', '>=', now()->subDays(30))->count(),
        ];
    }

    /**
     * Obtenir les statistiques des transactions
     */
    public function getTransactionStats()
    {
        return [
            'total_transactions' => Transaction::count(),
            'total_value' => Transaction::sum('total_amount'),
            'average_transaction' => Transaction::avg('total_amount'),
            'transactions_by_status' => Transaction::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status'),
            'monthly_transactions' => $this->getMonthlyTransactions(),
            'top_products' => $this->getTopProducts(),
            'top_sellers' => $this->getTopSellers(),
            'top_buyers' => $this->getTopBuyers(),
        ];
    }

    /**
     * Obtenir les statistiques des produits
     */
    public function getProductStats()
    {
        return [
            'total_products' => Product::count(),
            'active_products' => Product::where('status', 'approved')->count(),
            'total_quantity' => Product::sum('quantity'),
            'average_price' => Product::avg('price'),
            'products_by_season' => Product::select('season', DB::raw('count(*) as count'))
                ->groupBy('season')
                ->pluck('count', 'season'),
            'low_stock_products' => Product::where('quantity', '<=', DB::raw('initial_quantity * 0.1'))->count(),
        ];
    }

    /**
     * Obtenir les statistiques par région
     */
    public function getRegionalStats()
    {
        return [
            'transactions_by_region' => $this->getTransactionsByRegion(),
            'products_by_region' => $this->getProductsByRegion(),
            'average_price_by_region' => $this->getAveragePriceByRegion(),
            'top_regions' => $this->getTopRegions(),
        ];
    }

    /**
     * Obtenir les transactions mensuelles
     */
    private function getMonthlyTransactions()
    {
        return Transaction::select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
            DB::raw('count(*) as count'),
            DB::raw('sum(total_amount) as total_value')
        )
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    /**
     * Obtenir les produits les plus vendus
     */
    private function getTopProducts()
    {
        return Transaction::select(
            'product_id',
            DB::raw('count(*) as total_sales'),
            DB::raw('sum(quantity) as total_quantity'),
            DB::raw('sum(total_amount) as total_value')
        )
            ->with('product:id,name')
            ->groupBy('product_id')
            ->orderByDesc('total_value')
            ->limit(10)
            ->get();
    }

    /**
     * Obtenir les meilleurs vendeurs
     */
    private function getTopSellers()
    {
        return Transaction::select(
            'seller_id',
            DB::raw('count(*) as total_sales'),
            DB::raw('sum(total_amount) as total_value')
        )
            ->with('seller:id,name')
            ->groupBy('seller_id')
            ->orderByDesc('total_value')
            ->limit(10)
            ->get();
    }

    /**
     * Obtenir les meilleurs acheteurs
     */
    private function getTopBuyers()
    {
        return Transaction::select(
            'buyer_id',
            DB::raw('count(*) as total_purchases'),
            DB::raw('sum(total_amount) as total_spent')
        )
            ->with('buyer:id,name')
            ->groupBy('buyer_id')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get();
    }

    /**
     * Obtenir les transactions par région
     */
    private function getTransactionsByRegion()
    {
        return Transaction::join('products', 'transactions.product_id', '=', 'products.id')
            ->select(
                'products.region',
                DB::raw('count(*) as total_transactions'),
                DB::raw('sum(transactions.total_amount) as total_value')
            )
            ->groupBy('products.region')
            ->get();
    }

    /**
     * Obtenir les produits par région
     */
    private function getProductsByRegion()
    {
        return Product::select(
            'region',
            DB::raw('count(*) as total_products'),
            DB::raw('sum(quantity) as total_quantity'),
            DB::raw('avg(price) as average_price')
        )
            ->groupBy('region')
            ->get();
    }

    /**
     * Obtenir le prix moyen par région
     */
    private function getAveragePriceByRegion()
    {
        return Product::select(
            'region',
            DB::raw('avg(price) as average_price')
        )
            ->groupBy('region')
            ->get();
    }

    /**
     * Obtenir les meilleures régions
     */
    private function getTopRegions()
    {
        return Transaction::join('products', 'transactions.product_id', '=', 'products.id')
            ->select(
                'products.region',
                DB::raw('count(*) as total_transactions'),
                DB::raw('sum(transactions.total_amount) as total_value')
            )
            ->groupBy('products.region')
            ->orderByDesc('total_value')
            ->limit(5)
            ->get();
    }

    /**
     * Générer un rapport PDF des statistiques
     */
    public function generateStatisticsReport()
    {
        $stats = $this->getGlobalStats();
        
        // Utiliser une bibliothèque PDF pour générer le rapport
        $pdf = \PDF::loadView('reports.statistics', [
            'stats' => $stats,
            'generated_at' => now(),
        ]);

        $filename = 'statistics-report-' . now()->format('Y-m-d') . '.pdf';
        $path = 'reports/' . $filename;

        // Sauvegarder le PDF
        \Storage::put($path, $pdf->output());

        return \Storage::url($path);
    }
}
