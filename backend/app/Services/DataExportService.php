<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use League\Csv\Writer;
use League\Csv\Reader;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\DroneData;

class DataExportService
{
    /**
     * Exporter les données au format CSV
     */
    public function exportToCsv(string $type, array $filters = []): string
    {
        $method = 'export' . ucfirst($type);
        if (!method_exists($this, $method)) {
            throw new \InvalidArgumentException("Export type '{$type}' not supported");
        }

        $data = $this->$method($filters);
        return $this->generateCsv($data, $type);
    }

    /**
     * Exporter les données des produits
     */
    protected function exportProducts(array $filters = []): array
    {
        $query = Product::query();

        if (isset($filters['region'])) {
            $query->where('region', $filters['region']);
        }

        if (isset($filters['date_start'])) {
            $query->where('created_at', '>=', $filters['date_start']);
        }

        if (isset($filters['date_end'])) {
            $query->where('created_at', '<=', $filters['date_end']);
        }

        $products = $query->with(['user'])->get();

        $data = [
            ['ID', 'Nom', 'Description', 'Prix', 'Quantité', 'Unité', 'Région', 'Producteur', 'Date de création']
        ];

        foreach ($products as $product) {
            $data[] = [
                $product->id,
                $product->name,
                $product->description,
                $product->price,
                $product->quantity,
                $product->unit,
                $product->region,
                $product->user->name,
                $product->created_at->format('Y-m-d H:i:s'),
            ];
        }

        return $data;
    }

    /**
     * Exporter les données des transactions
     */
    protected function exportTransactions(array $filters = []): array
    {
        $query = Transaction::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_start'])) {
            $query->where('created_at', '>=', $filters['date_start']);
        }

        if (isset($filters['date_end'])) {
            $query->where('created_at', '<=', $filters['date_end']);
        }

        $transactions = $query->with(['buyer', 'seller', 'product'])->get();

        $data = [
            ['ID', 'Produit', 'Quantité', 'Prix unitaire', 'Montant total', 'Acheteur', 'Vendeur', 'Statut', 'Date']
        ];

        foreach ($transactions as $transaction) {
            $data[] = [
                $transaction->id,
                $transaction->product->name,
                $transaction->quantity,
                $transaction->price_per_unit,
                $transaction->total_amount,
                $transaction->buyer->name,
                $transaction->seller->name,
                $transaction->status,
                $transaction->created_at->format('Y-m-d H:i:s'),
            ];
        }

        return $data;
    }

    /**
     * Exporter les données des drones
     */
    protected function exportDroneData(array $filters = []): array
    {
        $query = DroneData::query();

        if (isset($filters['region'])) {
            $query->where('region', $filters['region']);
        }

        if (isset($filters['date_start'])) {
            $query->where('capture_date', '>=', $filters['date_start']);
        }

        if (isset($filters['date_end'])) {
            $query->where('capture_date', '<=', $filters['date_end']);
        }

        $droneData = $query->with(['user'])->get();

        $data = [
            ['ID', 'Champ', 'Région', 'Taille', 'Propriétaire', 'Date de capture', 'Données']
        ];

        foreach ($droneData as $record) {
            $data[] = [
                $record->id,
                $record->field_name,
                $record->region,
                $record->field_size,
                $record->user->name,
                $record->capture_date,
                json_encode($record->data),
            ];
        }

        return $data;
    }

    /**
     * Générer un fichier CSV
     */
    protected function generateCsv(array $data, string $type): string
    {
        $csv = Writer::createFromString('');
        $csv->insertAll($data);

        $filename = sprintf(
            '%s_export_%s.csv',
            $type,
            Carbon::now()->format('Y-m-d_His')
        );

        Storage::put("exports/{$filename}", $csv->toString());

        return Storage::url("exports/{$filename}");
    }

    /**
     * Importer des données depuis un fichier CSV
     */
    public function importFromCsv(string $type, string $filepath): array
    {
        $method = 'import' . ucfirst($type);
        if (!method_exists($this, $method)) {
            throw new \InvalidArgumentException("Import type '{$type}' not supported");
        }

        $csv = Reader::createFromPath(storage_path("app/{$filepath}"), 'r');
        $csv->setHeaderOffset(0);

        $records = iterator_to_array($csv->getRecords());
        return $this->$method($records);
    }

    /**
     * Importer des données de produits
     */
    protected function importProducts(array $records): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'failed' => 0];

        foreach ($records as $record) {
            try {
                $product = Product::updateOrCreate(
                    ['name' => $record['Nom'], 'user_id' => auth()->id()],
                    [
                        'description' => $record['Description'],
                        'price' => $record['Prix'],
                        'quantity' => $record['Quantité'],
                        'unit' => $record['Unité'],
                        'region' => $record['Région'],
                    ]
                );

                $stats[$product->wasRecentlyCreated ? 'created' : 'updated']++;
            } catch (\Exception $e) {
                $stats['failed']++;
            }
        }

        return $stats;
    }

    /**
     * Générer un rapport d'export complet
     */
    public function generateFullReport(array $filters = []): array
    {
        $reports = [];

        // Exporter les différents types de données
        $types = ['products', 'transactions', 'droneData'];
        
        foreach ($types as $type) {
            try {
                $reports[$type] = $this->exportToCsv($type, $filters);
            } catch (\Exception $e) {
                $reports[$type] = null;
            }
        }

        // Générer un fichier ZIP contenant tous les rapports
        $zipName = 'full_report_' . Carbon::now()->format('Y-m-d_His') . '.zip';
        $zip = new \ZipArchive();
        
        if ($zip->open(storage_path("app/exports/{$zipName}"), \ZipArchive::CREATE) === true) {
            foreach ($reports as $type => $path) {
                if ($path) {
                    $zip->addFile(storage_path("app/public/" . basename($path)), basename($path));
                }
            }
            $zip->close();
        }

        return [
            'individual_reports' => $reports,
            'full_report' => Storage::url("exports/{$zipName}"),
        ];
    }

    /**
     * Nettoyer les anciens fichiers d'export
     */
    public function cleanupOldExports(int $daysOld = 7)
    {
        $files = Storage::files('exports');
        
        foreach ($files as $file) {
            $lastModified = Storage::lastModified($file);
            if (Carbon::createFromTimestamp($lastModified)->addDays($daysOld)->isPast()) {
                Storage::delete($file);
            }
        }
    }
}
