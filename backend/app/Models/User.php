<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'region',
        'profile_photo',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relations
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function droneData()
    {
        return $this->hasMany(DroneData::class);
    }

    public function buyerTransactions()
    {
        return $this->hasMany(Transaction::class, 'buyer_id');
    }

    public function sellerTransactions()
    {
        return $this->hasMany(Transaction::class, 'seller_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    // Méthodes d'aide
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isProducteur(): bool
    {
        return $this->role === 'producteur';
    }

    public function isAcheteur(): bool
    {
        return $this->role === 'acheteur';
    }

    public function isCooperative(): bool
    {
        return $this->role === 'cooperative';
    }

    public function canAccessDroneData(): bool
    {
        return in_array($this->role, ['producteur', 'cooperative']);
    }

    // Méthode pour obtenir les conversations
    public function conversations()
    {
        return Message::where(function ($query) {
            $query->where('sender_id', $this->id)
                  ->orWhere('receiver_id', $this->id);
        })
        ->orderBy('created_at', 'desc')
        ->get()
        ->groupBy(function ($message) {
            return $message->sender_id === $this->id 
                ? $message->receiver_id 
                : $message->sender_id;
        });
    }

    // Méthode pour obtenir les messages non lus
    public function unreadMessages()
    {
        return $this->receivedMessages()
            ->where('is_read', false)
            ->count();
    }

    // Méthode pour obtenir les statistiques de l'utilisateur
    public function getStats()
    {
        if ($this->isProducteur()) {
            return [
                'total_products' => $this->products()->count(),
                'active_products' => $this->products()->where('status', 'approved')->count(),
                'total_sales' => $this->sellerTransactions()->where('status', 'completed')->count(),
                'total_revenue' => $this->sellerTransactions()
                    ->where('status', 'completed')
                    ->sum('total_amount'),
            ];
        }

        if ($this->isAcheteur() || $this->isCooperative()) {
            return [
                'total_purchases' => $this->buyerTransactions()->where('status', 'completed')->count(),
                'total_spent' => $this->buyerTransactions()
                    ->where('status', 'completed')
                    ->sum('total_amount'),
            ];
        }

        return [];
    }
}
