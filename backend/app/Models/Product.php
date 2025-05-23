<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'price',
        'quantity',
        'unit',
        'season',
        'region',
        'photos',
        'status',
    ];

    protected $casts = [
        'photos' => 'array',
        'price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByRegion($query, $region)
    {
        return $query->where('region', $region);
    }

    public function scopeBySeason($query, $season)
    {
        return $query->where('season', $season);
    }

    // Méthodes
    public function isAvailable(): bool
    {
        return $this->quantity > 0 && $this->status === 'approved';
    }

    public function updateQuantity(int $soldQuantity): void
    {
        $this->quantity -= $soldQuantity;
        if ($this->quantity <= 0) {
            $this->status = 'sold';
        }
        $this->save();
    }

    public function getMainPhotoUrl(): ?string
    {
        $photos = $this->photos ?? [];
        return !empty($photos) ? $photos[0] : null;
    }

    public function getFormattedPrice(): string
    {
        return number_format($this->price, 2, ',', ' ') . ' FCFA/' . $this->unit;
    }

    // Statistiques du produit
    public function getStats()
    {
        return [
            'views' => 0, // À implémenter avec un système de comptage de vues
            'interested_buyers' => $this->transactions()->where('status', 'pending')->count(),
            'completed_sales' => $this->transactions()->where('status', 'completed')->count(),
            'total_revenue' => $this->transactions()
                ->where('status', 'completed')
                ->sum('total_amount'),
        ];
    }

    // Méthode pour obtenir des produits similaires
    public function getSimilarProducts($limit = 4)
    {
        return static::approved()
            ->where('id', '!=', $this->id)
            ->where(function ($query) {
                $query->where('region', $this->region)
                    ->orWhere('name', 'like', '%' . $this->name . '%');
            })
            ->limit($limit)
            ->get();
    }

    // Méthode pour vérifier si un utilisateur peut modifier ce produit
    public function canBeModifiedBy(User $user): bool
    {
        return $user->id === $this->user_id || $user->isAdmin();
    }

    // Boot method pour les événements du modèle
    protected static function boot()
    {
        parent::boot();

        // Avant la suppression, vérifier s'il y a des transactions en cours
        static::deleting(function ($product) {
            if ($product->transactions()->where('status', 'pending')->exists()) {
                throw new \Exception('Ce produit ne peut pas être supprimé car il a des transactions en cours.');
            }
        });
    }
}
