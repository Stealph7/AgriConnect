<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',
        'seller_id',
        'product_id',
        'quantity',
        'price_per_unit',
        'total_amount',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price_per_unit' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    // Constantes pour les statuts
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    // Relations
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('buyer_id', $userId)
                    ->orWhere('seller_id', $userId);
    }

    // Méthodes
    public function complete(): bool
    {
        try {
            // Vérifier si la transaction peut être complétée
            if (!$this->canBeCompleted()) {
                throw new \Exception('La transaction ne peut pas être complétée.');
            }

            // Mettre à jour le statut et la date de complétion
            $this->status = self::STATUS_COMPLETED;
            $this->completed_at = now();
            $this->save();

            // Mettre à jour la quantité du produit
            $this->product->updateQuantity($this->quantity);

            // Créer les notifications
            $this->createCompletionNotifications();

            return true;
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la complétion de la transaction: ' . $e->getMessage());
            return false;
        }
    }

    public function cancel(): bool
    {
        try {
            if (!$this->canBeCancelled()) {
                throw new \Exception('La transaction ne peut pas être annulée.');
            }

            $this->status = self::STATUS_CANCELLED;
            $this->save();

            // Créer les notifications
            $this->createCancellationNotifications();

            return true;
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'annulation de la transaction: ' . $e->getMessage());
            return false;
        }
    }

    public function canBeCompleted(): bool
    {
        return $this->status === self::STATUS_PENDING &&
               $this->product->quantity >= $this->quantity;
    }

    public function canBeCancelled(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function calculateTotalAmount(): void
    {
        $this->total_amount = $this->quantity * $this->price_per_unit;
    }

    // Méthodes privées pour les notifications
    private function createCompletionNotifications(): void
    {
        // Notification pour l'acheteur
        Notification::create([
            'user_id' => $this->buyer_id,
            'title' => 'Transaction complétée',
            'content' => "Votre achat de {$this->quantity} {$this->product->unit} de {$this->product->name} a été complété.",
            'type' => 'transaction',
        ]);

        // Notification pour le vendeur
        Notification::create([
            'user_id' => $this->seller_id,
            'title' => 'Vente complétée',
            'content' => "La vente de {$this->quantity} {$this->product->unit} de {$this->product->name} a été complétée.",
            'type' => 'transaction',
        ]);
    }

    private function createCancellationNotifications(): void
    {
        // Notification pour l'acheteur
        Notification::create([
            'user_id' => $this->buyer_id,
            'title' => 'Transaction annulée',
            'content' => "Votre achat de {$this->quantity} {$this->product->unit} de {$this->product->name} a été annulé.",
            'type' => 'transaction',
        ]);

        // Notification pour le vendeur
        Notification::create([
            'user_id' => $this->seller_id,
            'title' => 'Vente annulée',
            'content' => "La vente de {$this->quantity} {$this->product->unit} de {$this->product->name} a été annulée.",
            'type' => 'transaction',
        ]);
    }

    // Boot method pour les événements du modèle
    protected static function boot()
    {
        parent::boot();

        // Avant la création, calculer le montant total
        static::creating(function ($transaction) {
            $transaction->calculateTotalAmount();
        });

        // Avant la mise à jour, recalculer le montant total si nécessaire
        static::updating(function ($transaction) {
            if ($transaction->isDirty(['quantity', 'price_per_unit'])) {
                $transaction->calculateTotalAmount();
            }
        });
    }
}
