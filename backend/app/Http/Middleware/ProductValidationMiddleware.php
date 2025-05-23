<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductValidationMiddleware
{
    /**
     * Gérer la validation des produits agricoles
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

        // Vérifier si c'est une création ou modification de produit
        if ($request->isMethod('post') || $request->isMethod('put') || $request->isMethod('patch')) {
            
            // Seuls les producteurs peuvent créer des produits
            if (!$user->isProducteur() && !$user->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only producers can create or modify products'
                ], 403);
            }

            // Vérifier les limites de produits par utilisateur
            if ($request->isMethod('post')) {
                $activeProductsCount = Product::where('user_id', $user->id)
                    ->where('status', '!=', 'rejected')
                    ->count();

                $maxProductsAllowed = config('agriconnect.max_products_per_user', 50);
                
                if ($activeProductsCount >= $maxProductsAllowed) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "You have reached the maximum limit of {$maxProductsAllowed} active products"
                    ], 403);
                }
            }

            // Vérifier les restrictions saisonnières
            $season = $request->input('season');
            if ($season && !$this->isSeasonValid($season)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Products cannot be added for this season at the moment'
                ], 403);
            }

            // Vérifier la région
            $region = $request->input('region');
            if ($region && $region !== $user->region) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You can only add products for your registered region'
                ], 403);
            }

            // Vérifier les limites de prix
            $price = $request->input('price');
            if ($price) {
                $minPrice = config('agriconnect.min_product_price', 100);
                $maxPrice = config('agriconnect.max_product_price', 1000000);

                if ($price < $minPrice || $price > $maxPrice) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Price must be between {$minPrice} and {$maxPrice} FCFA"
                    ], 422);
                }
            }

            // Vérifier la taille des images
            if ($request->hasFile('photos')) {
                $maxSize = config('agriconnect.max_photo_size', 5120); // 5MB
                foreach ($request->file('photos') as $photo) {
                    if ($photo->getSize() > $maxSize * 1024) {
                        return response()->json([
                            'status' => 'error',
                            'message' => "Each photo must not exceed {$maxSize}KB"
                        ], 422);
                    }
                }
            }
        }

        // Pour les modifications de produits existants
        if ($request->isMethod('put') || $request->isMethod('patch')) {
            $productId = $request->route('id');
            $product = Product::find($productId);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found'
                ], 404);
            }

            // Vérifier que l'utilisateur est propriétaire du produit
            if ($product->user_id !== $user->id && !$user->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You can only modify your own products'
                ], 403);
            }

            // Empêcher la modification des produits vendus
            if ($product->status === 'sold') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot modify sold products'
                ], 403);
            }
        }

        return $next($request);
    }

    /**
     * Vérifier si la saison est valide pour l'ajout de produits
     */
    private function isSeasonValid(string $season): bool
    {
        $currentMonth = date('n');
        
        // Saison principale : Mars à Juillet (3-7)
        // Saison intermédiaire : Septembre à Novembre (9-11)
        
        if ($season === 'principale' && !in_array($currentMonth, [3,4,5,6,7])) {
            return false;
        }
        
        if ($season === 'intermediaire' && !in_array($currentMonth, [9,10,11])) {
            return false;
        }

        return true;
    }
}
