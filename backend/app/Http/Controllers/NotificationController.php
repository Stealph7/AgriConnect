<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    /**
     * Afficher la liste des notifications de l'utilisateur
     */
    public function index(Request $request)
    {
        try {
            $query = Notification::where('user_id', $request->user()->id);

            // Filtrer par type
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Filtrer par statut de lecture
            if ($request->has('is_read')) {
                $query->where('is_read', $request->boolean('is_read'));
            }

            // Pagination avec eager loading des relations
            $notifications = $query->with(['user'])
                                 ->orderBy('created_at', 'desc')
                                 ->paginate(20);

            return response()->json([
                'status' => 'success',
                'notifications' => $notifications,
                'unread_count' => $query->where('is_read', false)->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(Request $request, $id)
    {
        try {
            $notification = Notification::findOrFail($id);

            // Vérifier que la notification appartient à l'utilisateur
            if ($notification->user_id !== $request->user()->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $notification->markAsRead();

            return response()->json([
                'status' => 'success',
                'message' => 'Notification marked as read'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to mark notification as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead(Request $request)
    {
        try {
            Notification::where('user_id', $request->user()->id)
                       ->where('is_read', false)
                       ->update(['is_read' => true]);

            return response()->json([
                'status' => 'success',
                'message' => 'All notifications marked as read'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to mark notifications as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une notification
     */
    public function destroy(Request $request, $id)
    {
        try {
            $notification = Notification::findOrFail($id);

            // Vérifier que la notification appartient à l'utilisateur
            if ($notification->user_id !== $request->user()->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $notification->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Notification deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer toutes les notifications lues
     */
    public function deleteRead(Request $request)
    {
        try {
            Notification::where('user_id', $request->user()->id)
                       ->where('is_read', true)
                       ->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'All read notifications deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir le nombre de notifications non lues
     */
    public function unreadCount(Request $request)
    {
        try {
            $count = Notification::where('user_id', $request->user()->id)
                               ->where('is_read', false)
                               ->count();

            return response()->json([
                'status' => 'success',
                'unread_count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch unread count',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les dernières notifications non lues
     */
    public function latest(Request $request)
    {
        try {
            $notifications = Notification::where('user_id', $request->user()->id)
                                      ->where('is_read', false)
                                      ->orderBy('created_at', 'desc')
                                      ->limit(5)
                                      ->get();

            return response()->json([
                'status' => 'success',
                'notifications' => $notifications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch latest notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
