<?php

namespace App\Services;

use App\Models\DroneData;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;

class DroneService
{
    protected $storageDriver;

    public function __construct()
    {
        $this->storageDriver = config('filesystems.default', 'public');
    }

    /**
     * Traiter et sauvegarder les données de drone
     */
    public function processAndSaveDroneData(array $data, array $files): DroneData
    {
        try {
            // Sauvegarder les photos
            $processedPhotos = $this->processPhotos($files['photos']);

            // Analyser les images et générer les données d'analyse
            $analysisData = $this->analyzePhotos($processedPhotos);

            // Créer l'enregistrement DroneData
            $droneData = DroneData::create([
                'user_id' => $data['user_id'],
                'field_name' => $data['field_name'],
                'region' => $data['region'],
                'field_size' => $data['field_size'],
                'photos' => $processedPhotos,
                'analysis_data' => $analysisData,
                'capture_date' => $data['capture_date'] ?? now(),
            ]);

            Log::info('Données drone traitées avec succès', [
                'drone_data_id' => $droneData->id,
                'field_name' => $data['field_name'],
            ]);

            return $droneData;
        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement des données drone', [
                'error' => $e->getMessage(),
                'field_name' => $data['field_name'] ?? null,
            ]);

            throw $e;
        }
    }

    /**
     * Traiter et optimiser les photos
     */
    protected function processPhotos(array $photos): array
    {
        $processedUrls = [];

        foreach ($photos as $photo) {
            // Créer un nom de fichier unique
            $filename = uniqid('drone_') . '.' . $photo->getClientOriginalExtension();
            
            // Optimiser l'image
            $image = Image::make($photo)
                ->resize(1920, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })
                ->encode('jpg', 80);

            // Sauvegarder l'image optimisée
            $path = "drone-photos/{$filename}";
            Storage::disk($this->storageDriver)->put($path, $image->stream());

            // Ajouter l'URL à la liste
            $processedUrls[] = Storage::disk($this->storageDriver)->url($path);
        }

        return $processedUrls;
    }

    /**
     * Analyser les photos et générer les données d'analyse
     */
    protected function analyzePhotos(array $photoUrls): array
    {
        // Initialiser les résultats d'analyse
        $analysis = [
            'health' => $this->analyzeVegetationHealth($photoUrls),
            'diseases' => $this->detectDiseases($photoUrls),
            'yield' => $this->estimateYield($photoUrls),
            'irrigation' => $this->analyzeIrrigation($photoUrls),
            'soil' => $this->analyzeSoilConditions($photoUrls),
        ];

        return $analysis;
    }

    /**
     * Analyser la santé de la végétation (NDVI)
     */
    protected function analyzeVegetationHealth(array $photoUrls): array
    {
        // Simuler l'analyse NDVI
        // Dans une implémentation réelle, utiliser une bibliothèque de traitement d'image
        return [
            'score' => rand(60, 100) / 100,
            'status' => 'healthy',
            'problem_areas' => [],
            'recommendations' => [
                'Maintenir le régime d\'irrigation actuel',
                'Surveiller les zones nord pour d\'éventuels signes de stress',
            ],
        ];
    }

    /**
     * Détecter les maladies des cultures
     */
    protected function detectDiseases(array $photoUrls): array
    {
        // Simuler la détection de maladies
        // Dans une implémentation réelle, utiliser un modèle ML
        return [
            'detected' => [],
            'risk_areas' => [
                [
                    'location' => 'Nord-Est',
                    'risk_level' => 'low',
                    'potential_issues' => ['Risque de champignons'],
                ],
            ],
        ];
    }

    /**
     * Estimer le rendement
     */
    protected function estimateYield(array $photoUrls): array
    {
        // Simuler l'estimation du rendement
        return [
            'estimated' => rand(800, 1200),
            'unit' => 'kg/hectare',
            'confidence' => 0.85,
            'factors' => [
                'vegetation_density' => 0.9,
                'plant_health' => 0.85,
                'growth_stage' => 0.8,
            ],
        ];
    }

    /**
     * Analyser l'irrigation
     */
    protected function analyzeIrrigation(array $photoUrls): array
    {
        return [
            'moisture_level' => rand(60, 90) / 100,
            'distribution' => 'even',
            'problem_areas' => [],
            'recommendations' => [
                'Maintenir le niveau d\'irrigation actuel',
                'Vérifier les zones sud pour une possible sur-irrigation',
            ],
        ];
    }

    /**
     * Analyser les conditions du sol
     */
    protected function analyzeSoilConditions(array $photoUrls): array
    {
        return [
            'type' => 'fertile',
            'moisture' => rand(50, 80) / 100,
            'erosion_risk' => 'low',
            'recommendations' => [
                'Ajouter du paillage dans les zones exposées',
                'Maintenir la couverture végétale actuelle',
            ],
        ];
    }

    /**
     * Générer un rapport PDF
     */
    public function generateReport(DroneData $droneData): string
    {
        try {
            // Créer le PDF avec les données d'analyse
            $pdf = PDF::loadView('reports.drone-analysis', [
                'droneData' => $droneData,
                'analysis' => $droneData->analysis_data,
                'date' => $droneData->capture_date->format('d/m/Y'),
            ]);

            // Sauvegarder le PDF
            $filename = "drone-report-{$droneData->id}.pdf";
            $path = "reports/{$filename}";
            Storage::disk($this->storageDriver)->put($path, $pdf->output());

            return Storage::disk($this->storageDriver)->url($path);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération du rapport drone', [
                'drone_data_id' => $droneData->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Archiver les anciennes données
     */
    public function archiveOldData(int $daysOld = 90): int
    {
        $date = now()->subDays($daysOld);
        
        return DroneData::where('created_at', '<', $date)
            ->update(['archived' => true]);
    }
}
