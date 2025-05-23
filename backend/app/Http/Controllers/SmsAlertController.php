<?php

namespace App\Http\Controllers;

use App\Models\SmsAlert;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SmsAlertController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
        $this->middleware('admin')->except(['index', 'show']);
    }

    /**
     * Afficher la liste des alertes SMS
     */
    public function index(Request $request)
    {
        try {
            $query = SmsAlert::query();

            // Filtrer par région si spécifié
            if ($request->has('region')) {
                $query->where('region', $request->region);
            }

            // Filtrer par type
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Filtrer par statut d'envoi
            if ($request->has('sent')) {
                if ($request->sent) {
                    $query->whereNotNull('sent_at');
                } else {
                    $query->whereNull('sent_at');
                }
            }

            $alerts = $query->orderBy('created_at', 'desc')->paginate(20);

            return response()->json([
                'status' => 'success',
                'alerts' => $alerts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch SMS alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer une nouvelle alerte SMS
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:meteo,maladie,conseil',
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'region' => 'nullable|string',
                'languages' => 'required|array',
                'languages.*.code' => 'required|string',
                'languages.*.title' => 'required|string',
                'languages.*.content' => 'required|string',
                'send_now' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $alert = SmsAlert::create([
                'type' => $request->type,
                'title' => $request->title,
                'content' => $request->content,
                'region' => $request->region,
                'languages' => $request->languages,
            ]);

            // Envoyer immédiatement si demandé
            if ($request->send_now) {
                $alert->send();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'SMS alert created successfully',
                'alert' => $alert
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create SMS alert',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une alerte SMS spécifique
     */
    public function show($id)
    {
        try {
            $alert = SmsAlert::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'alert' => $alert
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch SMS alert',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour une alerte SMS
     */
    public function update(Request $request, $id)
    {
        try {
            $alert = SmsAlert::findOrFail($id);

            // Ne pas permettre la modification d'une alerte déjà envoyée
            if ($alert->sent_at) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot modify sent alert'
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'type' => 'sometimes|in:meteo,maladie,conseil',
                'title' => 'sometimes|string|max:255',
                'content' => 'sometimes|string',
                'region' => 'nullable|string',
                'languages' => 'sometimes|array',
                'languages.*.code' => 'required_with:languages|string',
                'languages.*.title' => 'required_with:languages|string',
                'languages.*.content' => 'required_with:languages|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $alert->update($validator->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'SMS alert updated successfully',
                'alert' => $alert
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update SMS alert',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une alerte SMS
     */
    public function destroy($id)
    {
        try {
            $alert = SmsAlert::findOrFail($id);

            // Ne pas permettre la suppression d'une alerte déjà envoyée
            if ($alert->sent_at) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete sent alert'
                ], 422);
            }

            $alert->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'SMS alert deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete SMS alert',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envoyer une alerte SMS
     */
    public function send($id)
    {
        try {
            $alert = SmsAlert::findOrFail($id);

            // Vérifier si l'alerte n'a pas déjà été envoyée
            if ($alert->sent_at) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Alert already sent'
                ], 422);
            }

            $result = $alert->send();

            if ($result) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'SMS alert sent successfully'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to send SMS alert'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send SMS alert',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
