<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Afficher la liste des transactions
     */
    public function index(Request $request)
    {
        try {
            $query = Transaction::query();
            $user = $request->user();

            // Filtrer selon le rôle de l'utilisateur
            if ($user->isProducteur()) {
                $query->where('seller_id', $user->id);
            } elseif ($user->isAcheteur() || $user->isCooperative()) {
                $query->where('buyer_id', $user->id);
            }

            // Filtrer par statut
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filtrer par date
            if ($request->has('date_start')) {
                $query->where('created_at', '>=', $request->date_start);
            }
            if ($request->has('date_end')) {
                $query->where('created_at', '<=', $request->date_end);
            }

            $transactions = $query->with(['buyer', 'seller', 'product'])
                                ->orderBy('created_at', 'desc')
                                ->paginate(20);

            return response()->json([
                'status' => 'success',
                'transactions' => $transactions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch transactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer une nouvelle transaction
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $transaction = $this->transactionService->createTransaction([
                'buyer_id' => $request->user()->id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Transaction created successfully',
                'transaction' => $transaction
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une transaction spécifique
     */
    public function show(Request $request, $id)
    {
        try {
            $transaction = Transaction::with(['buyer', 'seller', 'product'])->findOrFail($id);
            $user = $request->user();

            // Vérifier l'accès
            if (!$user->isAdmin() && 
                $user->id !== $transaction->buyer_id && 
                $user->id !== $transaction->seller_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'transaction' => $transaction
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Compléter une transaction
     */
    public function complete(Request $request, $id)
    {
        try {
            $transaction = Transaction::findOrFail($id);
            $user = $request->user();

            // Vérifier l'accès (seul le vendeur peut compléter la transaction)
            if (!$user->isAdmin() && $user->id !== $transaction->seller_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $this->transactionService->completeTransaction($transaction);

            return response()->json([
                'status' => 'success',
                'message' => 'Transaction completed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to complete transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Annuler une transaction
     */
    public function cancel(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $transaction = Transaction::findOrFail($id);
            $user = $request->user();

            // Vérifier l'accès (acheteur, vendeur ou admin peuvent annuler)
            if (!$user->isAdmin() && 
                $user->id !== $transaction->buyer_id && 
                $user->id !== $transaction->seller_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $this->transactionService->cancelTransaction($transaction, $request->reason);

            return response()->json([
                'status' => 'success',
                'message' => 'Transaction cancelled successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques des transactions
     */
    public function stats(Request $request)
    {
        try {
            $stats = $this->transactionService->generateTransactionReport(
                $request->user(),
                $request->all()
            );

            return response()->json([
                'status' => 'success',
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch transaction statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
