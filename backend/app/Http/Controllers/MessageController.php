<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    protected $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    /**
     * Obtenir la liste des conversations de l'utilisateur
     */
    public function conversations(Request $request)
    {
        try {
            $conversations = $this->messageService->getUserConversations($request->user());

            return response()->json([
                'status' => 'success',
                'conversations' => $conversations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch conversations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les messages d'une conversation spécifique
     */
    public function conversation(Request $request, $userId)
    {
        try {
            // Vérifier que l'autre utilisateur existe
            $otherUser = User::findOrFail($userId);

            // Obtenir les messages
            $messages = $this->messageService->getConversationMessages(
                $request->user()->id,
                $otherUser->id
            );

            // Marquer les messages comme lus
            $this->messageService->markConversationAsRead(
                $request->user()->id,
                $otherUser->id
            );

            return response()->json([
                'status' => 'success',
                'messages' => $messages,
                'other_user' => [
                    'id' => $otherUser->id,
                    'name' => $otherUser->name,
                    'profile_photo' => $otherUser->profile_photo,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch conversation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envoyer un nouveau message
     */
    public function send(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'receiver_id' => 'required|exists:users,id',
                'content' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Vérifier que l'utilisateur n'essaie pas de s'envoyer un message à lui-même
            if ($request->receiver_id == $request->user()->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot send message to yourself'
                ], 422);
            }

            $message = $this->messageService->sendMessage([
                'sender_id' => $request->user()->id,
                'receiver_id' => $request->receiver_id,
                'content' => $request->content,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Message sent successfully',
                'data' => $message
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marquer un message comme lu
     */
    public function markAsRead(Request $request, $id)
    {
        try {
            $message = Message::findOrFail($id);

            // Vérifier que l'utilisateur est bien le destinataire
            if ($message->receiver_id !== $request->user()->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $this->messageService->markAsRead($message);

            return response()->json([
                'status' => 'success',
                'message' => 'Message marked as read'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to mark message as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un message
     */
    public function delete(Request $request, $id)
    {
        try {
            $message = Message::findOrFail($id);

            // Vérifier que l'utilisateur est l'expéditeur ou le destinataire
            if (!in_array($request->user()->id, [$message->sender_id, $message->receiver_id])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $this->messageService->deleteMessage($message);

            return response()->json([
                'status' => 'success',
                'message' => 'Message deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques de messagerie
     */
    public function stats(Request $request)
    {
        try {
            $stats = $this->messageService->getUserMessageStats($request->user());

            return response()->json([
                'status' => 'success',
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch message statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
