<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Product;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Créer une nouvelle transaction
     */
    public function createTransaction(array $data): Transaction
    {
        try {
            DB::beginTransaction();

            // Vérifier la disponibilité du produit
            $product = Product::findOrFail($data['product_id']);
            
            if ($product->quantity < $data['quantity']) {
                throw new \Exception('Quantité demandée non disponible.');
            }

            // Calculer le montant total
            $totalAmount = $data['quantity'] * $product->price;

            // Créer la transaction
            $transaction = Transaction::create([
                'buyer_id' => $data['buyer_id'],
                'seller_id' => $product->user_id,
                'product_id' => $product->id,
                'quantity' => $data['quantity'],
                'price_per_unit' => $product->price,
                'total_amount' => $totalAmount,
                'status' => Transaction::STATUS_PENDING,
            ]);

            // Réserver la quantité
            $product->quantity -= $data['quantity'];
            $product->save();

            // Envoyer les notifications
            $this->sendTransactionNotifications($transaction, 'created');

            DB::commit();

            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création de la transaction', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Compléter une transaction
     */
    public function completeTransaction(Transaction $transaction): bool
    {
        try {
            DB::beginTransaction();

            if (!$transaction->canBeCompleted()) {
                throw new \Exception('La transaction ne peut pas être complétée.');
            }

            // Mettre à jour le statut
            $transaction->status = Transaction::STATUS_COMPLETED;
            $transaction->completed_at = now();
            $transaction->save();

            // Mettre à jour les statistiques
            $this->updateUserStats($transaction);

            // Envoyer les notifications
            $this->sendTransactionNotifications($transaction, 'completed');

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la complétion de la transaction', [
                'error' => $e->getMessage(),
                'transaction_id' => $transaction->id,
            ]);
            throw $e;
        }
    }

    /**
     * Annuler une transaction
     */
    public function cancelTransaction(Transaction $transaction, string $reason = null): bool
    {
        try {
            DB::beginTransaction();

            if (!$transaction->canBeCancelled()) {
                throw new \Exception('La transaction ne peut pas être annulée.');
            }

            // Restaurer la quantité du produit
            $product = $transaction->product;
            $product->quantity += $transaction->quantity;
            $product->save();

            // Mettre à jour le statut
            $transaction->status = Transaction::STATUS_CANCELLED;
            $transaction->save();

            // Envoyer les notifications
            $this->sendTransactionNotifications($transaction, 'cancelled', $reason);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'annulation de la transaction', [
                'error' => $e->getMessage(),
                'transaction_id' => $transaction->id,
            ]);
            throw $e;
        }
    }

    /**
     * Envoyer les notifications de transaction
     */
    protected function sendTransactionNotifications(Transaction $transaction, string $action, string $reason = null): void
    {
        $buyer = $transaction->buyer;
        $seller = $transaction->seller;
        $product = $transaction->product;

        // Créer les notifications dans l'application
        $this->createNotifications($transaction, $action, $reason);

        // Envoyer les SMS
        $this->sendSmsNotifications($transaction, $action, $reason);
    }

    /**
     * Créer les notifications dans l'application
     */
    protected function createNotifications(Transaction $transaction, string $action, string $reason = null): void
    {
        $messages = $this->getNotificationMessages($transaction, $action, $reason);

        // Notification pour l'acheteur
        Notification::create([
            'user_id' => $transaction->buyer_id,
            'title' => $messages['buyer']['title'],
            'content' => $messages['buyer']['content'],
            'type' => 'transaction',
        ]);

        // Notification pour le vendeur
        Notification::create([
            'user_id' => $transaction->seller_id,
            'title' => $messages['seller']['title'],
            'content' => $messages['seller']['content'],
            'type' => 'transaction',
        ]);
    }

    /**
     * Envoyer les SMS de notification
     */
    protected function sendSmsNotifications(Transaction $transaction, string $action, string $reason = null): void
    {
        $messages = $this->getNotificationMessages($transaction, $action, $reason);

        // SMS pour l'acheteur
        $this->smsService->send(
            $transaction->buyer->phone,
            $messages['buyer']['content']
        );

        // SMS pour le vendeur
        $this->smsService->send(
            $transaction->seller->phone,
            $messages['seller']['content']
        );
    }

    /**
     * Obtenir les messages de notification
     */
    protected function getNotificationMessages(Transaction $transaction, string $action, string $reason = null): array
    {
        $product = $transaction->product;
        $quantity = $transaction->quantity;
        $amount = number_format($transaction->total_amount, 0, ',', ' ');

        $messages = [
            'created' => [
                'buyer' => [
                    'title' => 'Nouvelle commande créée',
                    'content' => "Votre commande de {$quantity} {$product->unit} de {$product->name} pour {$amount} FCFA a été créée.",
                ],
                'seller' => [
                    'title' => 'Nouvelle commande reçue',
                    'content' => "Vous avez reçu une commande de {$quantity} {$product->unit} de {$product->name} pour {$amount} FCFA.",
                ],
            ],
            'completed' => [
                'buyer' => [
                    'title' => 'Commande complétée',
                    'content' => "Votre commande de {$quantity} {$product->unit} de {$product->name} a été complétée.",
                ],
                'seller' => [
                    'title' => 'Vente complétée',
                    'content' => "Votre vente de {$quantity} {$product->unit} de {$product->name} a été complétée.",
                ],
            ],
            'cancelled' => [
                'buyer' => [
                    'title' => 'Commande annulée',
                    'content' => "Votre commande de {$quantity} {$product->unit} de {$product->name} a été annulée." . ($reason ? " Raison: {$reason}" : ''),
                ],
                'seller' => [
                    'title' => 'Vente annulée',
                    'content' => "La vente de {$quantity} {$product->unit} de {$product->name} a été annulée." . ($reason ? " Raison: {$reason}" : ''),
                ],
            ],
        ];

        return $messages[$action];
    }

    /**
     * Mettre à jour les statistiques des utilisateurs
     */
    protected function updateUserStats(Transaction $transaction): void
    {
        // Mettre à jour les statistiques de l'acheteur
        $buyerStats = $transaction->buyer->getStats();
        // Sauvegarder les nouvelles statistiques...

        // Mettre à jour les statistiques du vendeur
        $sellerStats = $transaction->seller->getStats();
        // Sauvegarder les nouvelles statistiques...
    }

    /**
     * Générer un rapport de transactions
     */
    public function generateTransactionReport(User $user, array $filters = []): array
    {
        $query = Transaction::query();

        // Appliquer les filtres
        if ($user->isProducteur()) {
            $query->where('seller_id', $user->id);
        } elseif ($user->isAcheteur() || $user->isCooperative()) {
            $query->where('buyer_id', $user->id);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_start'])) {
            $query->where('created_at', '>=', $filters['date_start']);
        }

        if (isset($filters['date_end'])) {
            $query->where('created_at', '<=', $filters['date_end']);
        }

        // Calculer les statistiques
        $stats = [
            'total_transactions' => $query->count(),
            'total_amount' => $query->sum('total_amount'),
            'average_amount' => $query->avg('total_amount'),
            'transactions_by_status' => $query->groupBy('status')
                ->selectRaw('status, count(*) as count')
                ->pluck('count', 'status'),
        ];

        return $stats;
    }
}
