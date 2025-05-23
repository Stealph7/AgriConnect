<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\WebhookEndpoint;
use App\Models\WebhookLog;

class WebhookService
{
    /**
     * Envoyer un événement à tous les endpoints enregistrés
     */
    public function dispatchEvent(string $event, array $data)
    {
        $endpoints = WebhookEndpoint::where('events', 'like', "%{$event}%")
            ->where('is_active', true)
            ->get();

        foreach ($endpoints as $endpoint) {
            $this->sendWebhook($endpoint, $event, $data);
        }
    }

    /**
     * Envoyer un webhook à un endpoint spécifique
     */
    protected function sendWebhook(WebhookEndpoint $endpoint, string $event, array $data)
    {
        try {
            $payload = [
                'event' => $event,
                'timestamp' => now()->toIso8601String(),
                'data' => $data,
            ];

            // Signer le payload
            $signature = $this->generateSignature($payload, $endpoint->secret);

            // Envoyer la requête
            $response = Http::timeout(5)
                ->withHeaders([
                    'User-Agent' => 'AgriConnect-Webhook/1.0',
                    'X-AgriConnect-Event' => $event,
                    'X-AgriConnect-Signature' => $signature,
                    'X-AgriConnect-Timestamp' => time(),
                ])
                ->post($endpoint->url, $payload);

            // Enregistrer la tentative
            $this->logWebhookAttempt($endpoint, $event, $payload, $response);

            // Gérer les erreurs
            if (!$response->successful()) {
                throw new \Exception("Webhook failed with status {$response->status()}");
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Webhook delivery failed', [
                'endpoint_id' => $endpoint->id,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);

            // Planifier une nouvelle tentative si nécessaire
            if ($endpoint->retry_count < $endpoint->max_retries) {
                $this->scheduleRetry($endpoint, $event, $data);
            }

            return false;
        }
    }

    /**
     * Générer une signature pour le payload
     */
    protected function generateSignature(array $payload, string $secret): string
    {
        $payloadString = json_encode($payload);
        return hash_hmac('sha256', $payloadString, $secret);
    }

    /**
     * Enregistrer une tentative d'envoi de webhook
     */
    protected function logWebhookAttempt(WebhookEndpoint $endpoint, string $event, array $payload, $response)
    {
        WebhookLog::create([
            'webhook_endpoint_id' => $endpoint->id,
            'event' => $event,
            'payload' => $payload,
            'response_code' => $response->status(),
            'response_body' => $response->body(),
            'success' => $response->successful(),
        ]);
    }

    /**
     * Planifier une nouvelle tentative d'envoi
     */
    protected function scheduleRetry(WebhookEndpoint $endpoint, string $event, array $data)
    {
        $delay = $this->calculateRetryDelay($endpoint->retry_count);

        \Queue::later($delay, new \App\Jobs\RetryWebhook([
            'endpoint_id' => $endpoint->id,
            'event' => $event,
            'data' => $data,
            'retry_count' => $endpoint->retry_count + 1,
        ]));

        $endpoint->increment('retry_count');
    }

    /**
     * Calculer le délai avant la prochaine tentative
     */
    protected function calculateRetryDelay(int $retryCount): int
    {
        // Utiliser un délai exponentiel : 1min, 5min, 15min, 30min, 1h
        return min(60 * pow(2, $retryCount), 3600);
    }

    /**
     * Vérifier la validité d'une signature de webhook
     */
    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Enregistrer un nouvel endpoint de webhook
     */
    public function registerEndpoint(array $data): WebhookEndpoint
    {
        return WebhookEndpoint::create([
            'url' => $data['url'],
            'events' => $data['events'],
            'description' => $data['description'] ?? null,
            'secret' => $this->generateSecret(),
            'is_active' => true,
            'max_retries' => $data['max_retries'] ?? 5,
        ]);
    }

    /**
     * Générer un secret pour un nouvel endpoint
     */
    protected function generateSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Désactiver un endpoint de webhook
     */
    public function deactivateEndpoint(WebhookEndpoint $endpoint)
    {
        $endpoint->update(['is_active' => false]);
    }

    /**
     * Nettoyer les logs de webhook
     */
    public function cleanupLogs(int $daysOld = 30)
    {
        WebhookLog::where('created_at', '<', now()->subDays($daysOld))->delete();
    }

    /**
     * Obtenir les statistiques des webhooks
     */
    public function getStats()
    {
        return [
            'total_endpoints' => WebhookEndpoint::count(),
            'active_endpoints' => WebhookEndpoint::where('is_active', true)->count(),
            'total_deliveries' => WebhookLog::count(),
            'success_rate' => WebhookLog::where('success', true)->count() / WebhookLog::count() * 100,
            'recent_failures' => WebhookLog::where('success', false)
                ->where('created_at', '>=', now()->subDay())
                ->count(),
        ];
    }

    /**
     * Obtenir les événements disponibles pour les webhooks
     */
    public function getAvailableEvents(): array
    {
        return [
            'product.created' => 'Nouveau produit créé',
            'product.updated' => 'Produit mis à jour',
            'product.deleted' => 'Produit supprimé',
            'transaction.created' => 'Nouvelle transaction',
            'transaction.completed' => 'Transaction complétée',
            'transaction.cancelled' => 'Transaction annulée',
            'user.registered' => 'Nouvel utilisateur inscrit',
            'message.sent' => 'Message envoyé',
            'drone.data.uploaded' => 'Données drone téléchargées',
            'alert.created' => 'Nouvelle alerte créée',
        ];
    }
}
