<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'type',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    // Types de notifications
    const TYPE_MESSAGE = 'message';
    const TYPE_TRANSACTION = 'transaction';
    const TYPE_PRODUCT = 'product';
    const TYPE_DRONE_DATA = 'drone_data';
    const TYPE_SYSTEM = 'system';

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Méthodes
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->is_read = true;
            $this->save();
        }
    }

    public function markAsUnread(): void
    {
        if ($this->is_read) {
            $this->is_read = false;
            $this->save();
        }
    }

    // Méthodes statiques pour créer différents types de notifications
    public static function createMessageNotification(User $user, Message $message): self
    {
        return static::create([
            'user_id' => $user->id,
            'title' => 'Nouveau message',
            'content' => "Vous avez reçu un nouveau message de {$message->sender->name}",
            'type' => self::TYPE_MESSAGE,
        ]);
    }

    public static function createTransactionNotification(User $user, Transaction $transaction, string $action): self
    {
        $title = match ($action) {
            'created' => 'Nouvelle transaction',
            'completed' => 'Transaction complétée',
            'cancelled' => 'Transaction annulée',
            default => 'Mise à jour de la transaction'
        };

        return static::create([
            'user_id' => $user->id,
            'title' => $title,
            'content' => static::generateTransactionContent($transaction, $action),
            'type' => self::TYPE_TRANSACTION,
        ]);
    }

    public static function createProductNotification(User $user, Product $product, string $action): self
    {
        return static::create([
            'user_id' => $user->id,
            'title' => "Produit {$action}",
            'content' => static::generateProductContent($product, $action),
            'type' => self::TYPE_PRODUCT,
        ]);
    }

    public static function createDroneDataNotification(User $user, DroneData $droneData): self
    {
        return static::create([
            'user_id' => $user->id,
            'title' => 'Nouvelles données drone',
            'content' => "Les données pour votre champ '{$droneData->field_name}' sont disponibles",
            'type' => self::TYPE_DRONE_DATA,
        ]);
    }

    public static function createSystemNotification(User $user, string $title, string $content): self
    {
        return static::create([
            'user_id' => $user->id,
            'title' => $title,
            'content' => $content,
            'type' => self::TYPE_SYSTEM,
        ]);
    }

    // Méthodes privées pour générer le contenu des notifications
    private static function generateTransactionContent(Transaction $transaction, string $action): string
    {
        return match ($action) {
            'created' => "Nouvelle demande d'achat pour {$transaction->quantity} {$transaction->product->unit} de {$transaction->product->name}",
            'completed' => "La transaction pour {$transaction->quantity} {$transaction->product->unit} de {$transaction->product->name} a été complétée",
            'cancelled' => "La transaction pour {$transaction->quantity} {$transaction->product->unit} de {$transaction->product->name} a été annulée",
            default => "Mise à jour de la transaction pour {$transaction->product->name}"
        };
    }

    private static function generateProductContent(Product $product, string $action): string
    {
        return match ($action) {
            'created' => "Nouveau produit ajouté : {$product->name}",
            'updated' => "Le produit {$product->name} a été mis à jour",
            'approved' => "Votre produit {$product->name} a été approuvé",
            'rejected' => "Votre produit {$product->name} a été rejeté",
            default => "Mise à jour du produit {$product->name}"
        };
    }

    // Boot method pour les événements du modèle
    protected static function boot()
    {
        parent::boot();

        // Après la création d'une notification, envoyer une notification push si configuré
        static::created(function ($notification) {
            // Ici, on pourrait implémenter l'envoi de notifications push
            // via WebSocket/Pusher ou un service de push notifications
        });
    }
}
