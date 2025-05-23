<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Gérer la vérification des rôles utilisateur
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!$request->user()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ], 401);
        }

        $userRole = $request->user()->role;

        if (!in_array($userRole, $roles)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Insufficient permissions.'
            ], 403);
        }

        return $next($request);
    }
}
