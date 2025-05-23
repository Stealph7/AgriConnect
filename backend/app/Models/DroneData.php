<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DroneData extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'field_name',
        'region',
        'field_size',
        'photos',
        'analysis_data',
        'capture_date',
    ];

    protected $casts = [
        'photos' => 'array',
        'analysis_data' => 'array',
        'field_size' => 'decimal:2',
        'capture_date' => 'date',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByRegion($query, $region)
    {
        return $query->where('region', $region);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('capture_date', 'desc');
    }

    public function scopeByDateRange($query, $start, $end)
    {
        return $query->whereBetween('capture_date', [$start, $end]);
    }

    // Méthodes
    public function getMainPhotoUrl(): ?string
    {
        $photos = $this->photos ?? [];
        return !empty($photos) ? $photos[0] : null;
    }

    public function getAllPhotosUrls(): array
    {
        return $this->photos ?? [];
    }

    // Méthode pour obtenir les analyses spécifiques
    public function getHealthAnalysis(): array
    {
        return $this->analysis_data['health'] ?? [];
    }

    public function getDiseaseDetection(): array
    {
        return $this->analysis_data['diseases'] ?? [];
    }

    public function getYieldEstimation(): array
    {
        return $this->analysis_data['yield'] ?? [];
    }

    // Méthode pour ajouter une nouvelle photo
    public function addPhoto(string $photoUrl): void
    {
        $photos = $this->photos ?? [];
        $photos[] = $photoUrl;
        $this->photos = $photos;
        $this->save();
    }

    // Méthode pour mettre à jour les données d'analyse
    public function updateAnalysis(array $newData): void
    {
        $this->analysis_data = array_merge(
            $this->analysis_data ?? [],
            $newData
        );
        $this->save();
    }

    // Méthode pour générer un rapport
    public function generateReport(): array
    {
        return [
            'field_info' => [
                'name' => $this->field_name,
                'region' => $this->region,
                'size' => $this->field_size . ' hectares',
                'capture_date' => $this->capture_date->format('d/m/Y'),
            ],
            'health_analysis' => $this->getHealthAnalysis(),
            'disease_detection' => $this->getDiseaseDetection(),
            'yield_estimation' => $this->getYieldEstimation(),
            'recommendations' => $this->generateRecommendations(),
        ];
    }

    // Méthode privée pour générer des recommandations basées sur l'analyse
    protected function generateRecommendations(): array
    {
        $recommendations = [];
        $analysis = $this->analysis_data;

        // Vérifier la santé générale
        if (isset($analysis['health']['score'])) {
            if ($analysis['health']['score'] < 0.6) {
                $recommendations[] = [
                    'type' => 'warning',
                    'message' => 'La santé générale des cultures est préoccupante. Une intervention est recommandée.',
                ];
            }
        }

        // Vérifier les maladies détectées
        if (isset($analysis['diseases']) && !empty($analysis['diseases'])) {
            foreach ($analysis['diseases'] as $disease) {
                $recommendations[] = [
                    'type' => 'alert',
                    'message' => "Maladie détectée: {$disease['name']}. Traitement recommandé: {$disease['treatment']}",
                ];
            }
        }

        // Recommandations sur le rendement
        if (isset($analysis['yield']['estimated'])) {
            $recommendations[] = [
                'type' => 'info',
                'message' => "Rendement estimé: {$analysis['yield']['estimated']} kg/hectare",
            ];
        }

        return $recommendations;
    }

    // Boot method pour les événements du modèle
    protected static function boot()
    {
        parent::boot();

        // Avant la sauvegarde, s'assurer que les structures de données sont correctes
        static::saving(function ($droneData) {
            if (!is_array($droneData->photos)) {
                $droneData->photos = [];
            }
            if (!is_array($droneData->analysis_data)) {
                $droneData->analysis_data = [];
            }
        });

        // Après la création, créer une notification pour l'utilisateur
        static::created(function ($droneData) {
            Notification::create([
                'user_id' => $droneData->user_id,
                'title' => 'Nouvelles données drone disponibles',
                'content' => "Les données pour {$droneData->field_name} sont maintenant disponibles.",
                'type' => 'drone_data',
            ]);
        });
    }
}
