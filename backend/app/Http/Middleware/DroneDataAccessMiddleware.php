<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DroneDataAccessMiddleware
{
    /**
     * Vérifier si l'utilisateur peut accéder aux données des drones
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ], 401);
        }

        // Seuls les producteurs, les coopératives et les admins peuvent accéder aux données drones
        if (!$user->canAccessDroneData() && !$user->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Drone data access is restricted to producers, cooperatives, and administrators.'
            ], 403);
        }

        // Si l'utilisateur est un producteur, vérifier qu'il accède uniquement à ses propres données
        if ($user->isProducteur()) {
            $droneDataId = $request->route('id');
            if ($droneDataId) {
                $droneData = \App\Models\DroneData::find($droneDataId);
                if ($droneData && $droneData->user_id !== $user->id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Access denied. You can only access your own drone data.'
                    ], 403);
                }
            }
        }

        // Si l'utilisateur est une coopérative, vérifier qu'il accède uniquement aux données de sa région
        if ($user->isCooperative()) {
            $droneDataId = $request->route('id');
            if ($droneDataId) {
                $droneData = \App\Models\DroneData::find($droneDataId);
                if ($droneData && $droneData->region !== $user->region) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Access denied. You can only access drone data from your region.'
                    ], 403);
                }
            }
        }

        return $next($request);
    }
}
