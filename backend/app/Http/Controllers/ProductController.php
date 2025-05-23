<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Afficher la liste des produits de l'utilisateur connecté
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $products = Product::where('user_id', $user->id)->get();

        return response()->json($products);
    }

    /**
     * Créer un nouveau produit
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'unit' => 'required|string|max:50',
            'season' => 'required|in:principale,intermediaire',
            'region' => 'required|string|max:255',
            'photos' => 'nullable|array',
            'photos.*' => 'string|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['user_id'] = $user->id;
        $data['status'] = 'pending';

        $product = Product::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Product created successfully',
            'product' => $product,
        ], 201);
    }

    /**
     * Afficher un produit spécifique
     */
    public function show(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        // Vérifier que l'utilisateur a accès au produit
        if ($product->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access',
            ], 403);
        }

        return response()->json($product);
    }

    /**
     * Mettre à jour un produit
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        if ($product->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'quantity' => 'sometimes|integer|min:0',
            'unit' => 'sometimes|string|max:50',
            'season' => 'sometimes|in:principale,intermediaire',
            'region' => 'sometimes|string|max:255',
            'photos' => 'nullable|array',
            'photos.*' => 'string|url',
            'status' => 'sometimes|in:pending,approved,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $product->update($validator->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Product updated successfully',
            'product' => $product,
        ]);
    }

    /**
     * Supprimer un produit
     */
    public function destroy(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        if ($product->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access',
            ], 403);
        }

        $product->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully',
        ]);
    }
}
