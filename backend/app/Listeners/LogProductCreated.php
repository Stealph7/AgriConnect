<?php

namespace App\Listeners;

use App\Events\ProductCreated;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class LogProductCreated
{
    /**
     * Gérer l'événement de création de produit
     */
    public function handle(ProductCreated $event)
    {
        $product = $event->product;
        $user = $event->user;

        try {
            // Logger l'événement
            Log::info('Nouveau produit créé', [
                'product_id' => $product->id,
                'name' => $product->name,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'region' => $product->region,
            ]);

            // Créer une notification pour les acheteurs de la région
            Notification::create([
                'type' => 'product',
                'title' => 'Nouveau produit disponible',
                'content' => "Un nouveau produit '{$product->name}' est disponible dans votre région",
                'data' => [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => $product->price,
                    'quantity' => $product->quantity,
                    'region' => $product->region,
                ],
                'user_id' => null, // Sera défini pour chaque acheteur
            ]);

            // Notifier les acheteurs de la région
            $buyers = \App\Models\User::where('role', 'acheteur')
                ->where('region', $product->region)
                ->get();

            foreach ($buyers as $buyer) {
                // Créer une notification personnalisée pour chaque acheteur
                Notification::create([
                    'user_id' => $buyer->id,
                    'type' => 'product',
                    'title' => 'Nouveau produit dans votre région',
                    'content' => "Le producteur {$user->name} propose {$product->name} à {$product->price} FCFA/{$product->unit}",
                    'data' => [
                        'product_id' => $product->id,
                        'seller_id' => $user->id,
                        'seller_name' => $user->name,
                    ],
                ]);

                // Envoyer un SMS si l'acheteur a activé les notifications
                if ($buyer->sms_notifications_enabled) {
                    app(\App\Services\SmsService::class)->send(
                        $buyer->phone,
                        "Nouveau produit disponible : {$product->name} à {$product->price} FCFA/{$product->unit} par {$user->name}"
                    );
                }
            }

            // Notifier les coopératives de la région
            $cooperatives = \App\Models\User::where('role', 'cooperative')
                ->where('region', $product->region)
                ->get();

            foreach ($cooperatives as $cooperative) {
                Notification::create([
                    'user_id' => $cooperative->id,
                    'type' => 'product',
                    'title' => 'Nouveau produit ajouté',
                    'content' => "Le producteur {$user->name} a ajouté {$product->name} dans votre région",
                    'data' => [
                        'product_id' => $product->id,
                        'seller_id' => $user->id,
                    ],
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement de ProductCreated', [
                'error' => $e->getMessage(),
                'product_id' => $product->id,
            ]);
        }
    }
}
