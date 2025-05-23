<?php

namespace App\Http\Controllers;

use App\Models\DroneData;
use App\Services\DroneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DroneDataController extends Controller
{
    protected $droneService;

    public function __construct(DroneService $droneService)
    {
        $this->droneService = $droneService;
        $this->middleware('can:access-drone-data');
    }

    /**
     * Afficher la liste des données drone
     */
    public function index(Request $request)
    {
        try {
            $query = DroneData::query();

            // Filtrer par utilisateur si ce n'est pas un admin
            if (!$request->user()->isAdmin()) {
                $query->where('user_id', $request->user()->id);
            }

            // Filtrer par région
            if ($request->has('region')) {
                $query->where('region', $request->region);
            }

            // Filtrer par date
            if ($request->has('date_start')) {
                $query->where('capture_date', '>=', $request->date_start);
            }
            if ($request->has('date_end')) {
                $query->where('capture_date', '<=', $request->date_end);
            }

            $droneData = $query->orderBy('capture_date', 'desc')
                              ->paginate(20);

            return response()->json([
                'status' => 'success',
                'data' => $droneData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch drone data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enregistrer de nouvelles données drone
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'field_name' => 'required|string|max:255',
                'region' => 'required|string|max:255',
                'field_size' => 'required|numeric|min:0',
                'photos' => 'required|array',
                'photos.*' => 'required|image|max:10240', // Max 10MB par image
                'capture_date' => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $data['user_id'] = $request->user()->id;

            $droneData = $this->droneService->processAndSaveDroneData(
                $data,
                ['photos' => $request->file('photos')]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Drone data saved successfully',
                'data' => $droneData
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save drone data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher des données drone spécifiques
     */
    public function show(Request $request, $id)
    {
        try {
            $droneData = DroneData::findOrFail($id);

            // Vérifier l'accès
            if (!$request->user()->isAdmin() && $droneData->user_id !== $request->user()->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'data' => $droneData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch drone data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer un rapport d'analyse
     */
    public function generateReport(Request $request, $id)
    {
        try {
            $droneData = DroneData::findOrFail($id);

            // Vérifier l'accès
            if (!$request->user()->isAdmin() && $droneData->user_id !== $request->user()->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $reportUrl = $this->droneService->generateReport($droneData);

            return response()->json([
                'status' => 'success',
                'message' => 'Report generated successfully',
                'report_url' => $reportUrl
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer des données drone
     */
    public function destroy(Request $request, $id)
    {
        try {
            $droneData = DroneData::findOrFail($id);

            // Vérifier l'accès
            if (!$request->user()->isAdmin() && $droneData->user_id !== $request->user()->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $droneData->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Drone data deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete drone data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques des données drone
     */
    public function stats(Request $request)
    {
        try {
            $query = DroneData::query();

            // Filtrer par utilisateur si ce n'est pas un admin
            if (!$request->user()->isAdmin()) {
                $query->where('user_id', $request->user()->id);
            }

            $stats = [
                'total_captures' => $query->count(),
                'total_field_size' => $query->sum('field_size'),
                'regions' => $query->distinct('region')->pluck('region'),
                'captures_by_month' => $query->selectRaw('DATE_FORMAT(capture_date, "%Y-%m") as month, COUNT(*) as count')
                    ->groupBy('month')
                    ->orderBy('month', 'desc')
                    ->get(),
            ];

            return response()->json([
                'status' => 'success',
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch drone statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
