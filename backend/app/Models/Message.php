<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'content',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    // Relations
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeBetweenUsers($query, $user1Id, $user2Id)
    {
        return $query->where(function ($q) use ($user1Id, $user2Id) {
            $q->where(function ($inner) use ($user1Id, $user2Id) {
                $inner->where('sender_id', $user1Id)
                      ->where('receiver_id', $user2Id);
            })->orWhere(function ($inner) use ($user1Id, $user2Id) {
                $inner->where('sender_id', $user2Id)
                      ->where('receiver_id', $user1Id);
            });
        });
    }

    // Méthodes
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->is_read = true;
            $this->save();
        }
    }

    public function isFromUser(User $user): bool
    {
        return $this->sender_id === $user->id;
    }

    public function isToUser(User $user): bool
    {
        return $this->receiver_id === $user->id;
    }

    // Méthode pour obtenir le dernier message entre deux utilisateurs
    public static function getLastMessageBetween($user1Id, $user2Id)
    {
        return static::betweenUsers($user1Id, $user2Id)
            ->latest()
            ->first();
    }

    // Méthode pour obtenir l'historique des messages entre deux utilisateurs
    public static function getConversationBetween($user1Id, $user2Id, $limit = 50)
    {
        return static::betweenUsers($user1Id, $user2Id)
            ->latest()
            ->limit($limit)
            ->get()
            ->reverse();
    }

    // Méthode pour marquer tous les messages d'une conversation comme lus
    public static function markConversationAsRead($senderId, $receiverId)
    {
        return static::where('sender_id', $senderId)
            ->where('receiver_id', $receiverId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    // Boot method pour les événements du modèle
    protected static function boot()
    {
        parent::boot();

        // Après la création d'un message, envoyer une notification
        static::created(function ($message) {
            // Créer une notification pour le destinataire
            Notification::create([
                'user_id' => $message->receiver_id,
                'title' => 'Nouveau message',
                'content' => 'Vous avez reçu un nouveau message de ' . $message->sender->name,
                'type' => 'message',
            ]);

            // Ici, on pourrait également déclencher une notification en temps réel
            // via WebSocket/Pusher si configuré
        });
    }
}
