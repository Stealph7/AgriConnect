<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimitMiddleware
{
    /**
     * Gérer la limitation des requêtes API
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $key = $user ? 'user:'.$user->id : 'ip:'.$request->ip();

        // Définir les limites selon le type d'utilisateur
        $limits = $this->getLimits($user);

        // Vérifier les limites pour chaque type de requête
        foreach ($limits as $type => $limit) {
            if ($this->matchesRequestType($request, $type)) {
                $limiterKey = "{$key}:{$type}";
                
                if (RateLimiter::tooManyAttempts($limiterKey, $limit['max'])) {
                    $seconds = RateLimiter::availableIn($limiterKey);
                    
                    return response()->json([
                        'status' => 'error',
                        'message' => "Too many requests. Please try again in {$seconds} seconds.",
                        'retry_after' => $seconds
                    ], 429);
                }

                RateLimiter::hit($limiterKey, $limit['decay']);
            }
        }

        $response = $next($request);

        // Ajouter les headers de rate limit
        if (isset($limiterKey)) {
            $response->headers->add([
                'X-RateLimit-Limit' => $limit['max'],
                'X-RateLimit-Remaining' => RateLimiter::remaining($limiterKey, $limit['max']),
                'X-RateLimit-Reset' => RateLimiter::availableIn($limiterKey)
            ]);
        }

        return $response;
    }

    /**
     * Obtenir les limites selon le type d'utilisateur
     */
    private function getLimits($user): array
    {
        // Limites par défaut pour les utilisateurs non authentifiés
        $defaultLimits = [
            'global' => ['max' => 60, 'decay' => 60], // 60 requêtes par minute
            'auth' => ['max' => 5, 'decay' => 60],    // 5 tentatives de connexion par minute
        ];

        if (!$user) {
            return $defaultLimits;
        }

        // Limites selon le rôle de l'utilisateur
        switch ($user->role) {
            case 'admin':
                return [
                    'global' => ['max' => 1000, 'decay' => 60],  // 1000 requêtes par minute
                    'create' => ['max' => 100, 'decay' => 60],   // 100 créations par minute
                    'update' => ['max' => 200, 'decay' => 60],   // 200 mises à jour par minute
                    'delete' => ['max' => 50, 'decay' => 60],    // 50 suppressions par minute
                ];

            case 'producteur':
                return [
                    'global' => ['max' => 300, 'decay' => 60],   // 300 requêtes par minute
                    'create' => ['max' => 20, 'decay' => 60],    // 20 créations par minute
                    'update' => ['max' => 50, 'decay' => 60],    // 50 mises à jour par minute
                    'delete' => ['max' => 10, 'decay' => 60],    // 10 suppressions par minute
                    'upload' => ['max' => 50, 'decay' => 3600],  // 50 uploads par heure
                ];

            case 'acheteur':
                return [
                    'global' => ['max' => 200, 'decay' => 60],   // 200 requêtes par minute
                    'search' => ['max' => 100, 'decay' => 60],   // 100 recherches par minute
                    'message' => ['max' => 50, 'decay' => 60],   // 50 messages par minute
                ];

            case 'cooperative':
                return [
                    'global' => ['max' => 500, 'decay' => 60],   // 500 requêtes par minute
                    'create' => ['max' => 50, 'decay' => 60],    // 50 créations par minute
                    'update' => ['max' => 100, 'decay' => 60],   // 100 mises à jour par minute
                    'delete' => ['max' => 20, 'decay' => 60],    // 20 suppressions par minute
                ];

            default:
                return $defaultLimits;
        }
    }

    /**
     * Vérifier si la requête correspond à un type spécifique
     */
    private function matchesRequestType(Request $request, string $type): bool
    {
        switch ($type) {
            case 'global':
                return true;

            case 'auth':
                return $request->is('api/login', 'api/register');

            case 'create':
                return $request->isMethod('post');

            case 'update':
                return $request->isMethod('put') || $request->isMethod('patch');

            case 'delete':
                return $request->isMethod('delete');

            case 'search':
                return $request->has('search') || $request->is('api/*/search');

            case 'upload':
                return $request->hasFile('*');

            case 'message':
                return $request->is('api/messages/*');

            default:
                return false;
        }
    }
}
