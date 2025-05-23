<?php

namespace App\Listeners;

use App\Events\ProductUpdated;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class LogProductUpdated
{
    /**
     * Gérer l'événement de mise à jour de produit
     */
    public function handle(ProductUpdated $event)
    {
        $product = $event->product;
        $user = $event->user;
        $changes = $event->changes;

        try {
            // Logger les modifications
            Log::info('Produit mis à jour', [
                'product_id' => $product->id,
                'name' => $product->name,
                'user_id' => $user->id,
                'changes' => $changes,
            ]);

            // Vérifier les changements significatifs
            $significantChanges = $this->getSignificantChanges($changes);
            if (empty($significantChanges)) {
                return;
            }

            // Préparer le message de notification
            $changeMessage = $this->formatChangeMessage($significantChanges);

            // Notifier les acheteurs intéressés
            $this->notifyInterestedBuyers($product, $changeMessage);

            // Notifier les coopératives de la région
            $this->notifyRegionalCooperatives($product, $changeMessage);

            // Si le prix a changé, envoyer des notifications spéciales
            if (isset($changes['price'])) {
                $this->handlePriceChange(
                    $product,
                    $changes['price']['old'],
                    $changes['price']['new']
                );
            }

            // Si la quantité a changé, vérifier le stock
            if (isset($changes['quantity'])) {
                $this->checkStockLevel($product);
            }

        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement de ProductUpdated', [
                'error' => $e->getMessage(),
                'product_id' => $product->id,
            ]);
        }
    }

    /**
     * Filtrer les changements significatifs
     */
    private function getSignificantChanges(array $changes): array
    {
        $significant = [];
        $significantFields = ['price', 'quantity', 'status', 'description'];

        foreach ($changes as $field => $value) {
            if (in_array($field, $significantFields)) {
                $significant[$field] = $value;
            }
        }

        return $significant;
    }

    /**
     * Formater le message de changement
     */
    private function formatChangeMessage(array $changes): string
    {
        $messages = [];

        foreach ($changes as $field => $value) {
            switch ($field) {
                case 'price':
                    $messages[] = "Prix modifié de {$value['old']} à {$value['new']} FCFA";
                    break;
                case 'quantity':
                    $messages[] = "Quantité mise à jour de {$value['old']} à {$value['new']}";
                    break;
                case 'status':
                    $messages[] = "Statut changé de {$value['old']} à {$value['new']}";
                    break;
            }
        }

        return implode(', ', $messages);
    }

    /**
     * Notifier les acheteurs intéressés
     */
    private function notifyInterestedBuyers($product, $changeMessage)
    {
        // Trouver les acheteurs qui ont interagi avec ce produit
        $interestedBuyers = \App\Models\User::whereHas('transactions', function ($query) use ($product) {
            $query->where('product_id', $product->id);
        })->orWhereHas('notifications', function ($query) use ($product) {
            $query->where('data->product_id', $product->id);
        })->where('role', 'acheteur')
          ->get();

        foreach ($interestedBuyers as $buyer) {
            Notification::create([
                'user_id' => $buyer->id,
                'type' => 'product_update',
                'title' => 'Mise à jour produit',
                'content' => "Le produit {$product->name} a été mis à jour : {$changeMessage}",
                'data' => [
                    'product_id' => $product->id,
                    'changes' => $changes,
                ],
            ]);
        }
    }

    /**
     * Notifier les coopératives de la région
     */
    private function notifyRegionalCooperatives($product, $changeMessage)
    {
        $cooperatives = \App\Models\User::where('role', 'cooperative')
            ->where('region', $product->region)
            ->get();

        foreach ($cooperatives as $cooperative) {
            Notification::create([
                'user_id' => $cooperative->id,
                'type' => 'product_update',
                'title' => 'Mise à jour produit dans votre région',
                'content' => "Le produit {$product->name} a été mis à jour : {$changeMessage}",
                'data' => [
                    'product_id' => $product->id,
                    'seller_id' => $product->user_id,
                ],
            ]);
        }
    }

    /**
     * Gérer les changements de prix
     */
    private function handlePriceChange($product, $oldPrice, $newPrice)
    {
        $percentChange = (($newPrice - $oldPrice) / $oldPrice) * 100;
        
        // Si le changement de prix est significatif (>10%)
        if (abs($percentChange) > 10) {
            // Notifier les administrateurs
            $admins = \App\Models\User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'price_alert',
                    'title' => 'Changement de prix significatif',
                    'content' => "Le prix de {$product->name} a changé de {$percentChange}% ({$oldPrice} → {$newPrice} FCFA)",
                    'data' => [
                        'product_id' => $product->id,
                        'price_change' => $percentChange,
                    ],
                ]);
            }
        }
    }

    /**
     * Vérifier le niveau de stock
     */
    private function checkStockLevel($product)
    {
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
}
