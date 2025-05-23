<?php

namespace App\Services;

use App\Models\Message;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class MessageService
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Envoyer un message
     */
    public function sendMessage(array $data): Message
    {
        try {
            DB::beginTransaction();

            // Créer le message
            $message = Message::create([
                'sender_id' => $data['sender_id'],
                'receiver_id' => $data['receiver_id'],
                'content' => $data['content'],
                'is_read' => false,
            ]);

            // Créer une notification pour le destinataire
            $this->createMessageNotification($message);

            // Envoyer une notification SMS si l'utilisateur le souhaite
            $this->sendSmsNotificationIfEnabled($message);

            DB::commit();

            return $message;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'envoi du message', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Marquer un message comme lu
     */
    public function markAsRead(Message $message): void
    {
        if (!$message->is_read) {
            $message->is_read = true;
            $message->save();
        }
    }

    /**
     * Marquer tous les messages d'une conversation comme lus
     */
    public function markConversationAsRead(int $userId, int $otherUserId): void
    {
        Message::where('sender_id', $otherUserId)
            ->where('receiver_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    /**
     * Obtenir les conversations d'un utilisateur
     */
    public function getUserConversations(User $user): Collection
    {
        // Obtenir tous les utilisateurs avec qui l'utilisateur a échangé des messages
        $conversations = Message::where('sender_id', $user->id)
            ->orWhere('receiver_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function ($message) use ($user) {
                return $message->sender_id === $user->id 
                    ? $message->receiver_id 
                    : $message->sender_id;
            })
            ->map(function ($messages, $otherUserId) {
                $otherUser = User::find($otherUserId);
                $lastMessage = $messages->first();
                
                return [
                    'user' => [
                        'id' => $otherUser->id,
                        'name' => $otherUser->name,
                        'profile_photo' => $otherUser->profile_photo,
                    ],
                    'last_message' => [
                        'content' => $lastMessage->content,
                        'created_at' => $lastMessage->created_at,
                        'is_read' => $lastMessage->is_read,
                    ],
                    'unread_count' => $messages->where('is_read', false)->count(),
                ];
            });

        return $conversations;
    }

    /**
     * Obtenir les messages d'une conversation
     */
    public function getConversationMessages(int $user1Id, int $user2Id, int $limit = 50): Collection
    {
        return Message::where(function ($query) use ($user1Id, $user2Id) {
            $query->where(function ($q) use ($user1Id, $user2Id) {
                $q->where('sender_id', $user1Id)
                  ->where('receiver_id', $user2Id);
            })->orWhere(function ($q) use ($user1Id, $user2Id) {
                $q->where('sender_id', $user2Id)
                  ->where('receiver_id', $user1Id);
            });
        })
        ->orderBy('created_at', 'desc')
        ->limit($limit)
        ->get()
        ->reverse();
    }

    /**
     * Supprimer un message
     */
    public function deleteMessage(Message $message): bool
    {
        try {
            return $message->delete();
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression du message', [
                'error' => $e->getMessage(),
                'message_id' => $message->id,
            ]);
            return false;
        }
    }

    /**
     * Créer une notification pour un nouveau message
     */
    protected function createMessageNotification(Message $message): void
    {
        Notification::create([
            'user_id' => $message->receiver_id,
            'title' => 'Nouveau message',
            'content' => "Vous avez reçu un nouveau message de {$message->sender->name}",
            'type' => 'message',
        ]);
    }

    /**
     * Envoyer une notification SMS si l'utilisateur l'a activé
     */
    protected function sendSmsNotificationIfEnabled(Message $message): void
    {
        $receiver = $message->receiver;
        
        // Vérifier si l'utilisateur a activé les notifications SMS
        // Cette logique devrait être implémentée selon vos besoins
        if ($this->shouldSendSmsNotification($receiver)) {
            $this->smsService->send(
                $receiver->phone,
                "Nouveau message de {$message->sender->name} sur AgriConnect"
            );
        }
    }

    /**
     * Vérifier si l'utilisateur devrait recevoir des notifications SMS
     */
    protected function shouldSendSmsNotification(User $user): bool
    {
        // À implémenter selon vos besoins
        // Par exemple, vérifier les préférences de l'utilisateur
        return false;
    }

    /**
     * Obtenir les statistiques de messagerie d'un utilisateur
     */
    public function getUserMessageStats(User $user): array
    {
        return [
            'total_messages' => Message::where('sender_id', $user->id)
                ->orWhere('receiver_id', $user->id)
                ->count(),
            'sent_messages' => Message::where('sender_id', $user->id)->count(),
            'received_messages' => Message::where('receiver_id', $user->id)->count(),
            'unread_messages' => Message::where('receiver_id', $user->id)
                ->where('is_read', false)
                ->count(),
            'conversations_count' => $this->getUserConversations($user)->count(),
        ];
    }

    /**
     * Archiver les anciens messages
     */
    public function archiveOldMessages(int $daysOld = 90): int
    {
        $date = now()->subDays($daysOld);
        
        return Message::where('created_at', '<', $date)
            ->update(['archived' => true]);
    }
}
