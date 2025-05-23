<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected $provider;
    protected $apiKey;
    protected $apiSecret;
    protected $senderId;
    protected $baseUrl;

    public function __construct()
    {
        $this->provider = config('services.sms.provider', 'orange');
        $this->apiKey = config('services.sms.api_key');
        $this->apiSecret = config('services.sms.api_secret');
        $this->senderId = config('services.sms.sender_id', 'AgriConnect');
        
        // Définir l'URL de base en fonction du fournisseur
        $this->baseUrl = $this->getProviderBaseUrl();
    }

    /**
     * Envoyer un SMS
     *
     * @param string $phoneNumber
     * @param string|array $message
     * @return bool
     */
    public function send(string $phoneNumber, $message): bool
    {
        try {
            // Formater le numéro de téléphone
            $phoneNumber = $this->formatPhoneNumber($phoneNumber);
            
            // Si le message est un tableau, le convertir en texte
            if (is_array($message)) {
                $message = $this->formatMessage($message);
            }

            // Envoyer le SMS via le fournisseur approprié
            $response = match($this->provider) {
                'orange' => $this->sendViaOrange($phoneNumber, $message),
                'mtn' => $this->sendViaMTN($phoneNumber, $message),
                'moov' => $this->sendViaMoov($phoneNumber, $message),
                default => throw new \Exception("Fournisseur SMS non supporté: {$this->provider}"),
            };

            // Logger le succès
            Log::info('SMS envoyé avec succès', [
                'phone' => $phoneNumber,
                'provider' => $this->provider,
            ]);

            return true;
        } catch (\Exception $e) {
            // Logger l'erreur
            Log::error('Erreur lors de l\'envoi du SMS', [
                'phone' => $phoneNumber,
                'provider' => $this->provider,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Envoyer un SMS en masse
     *
     * @param array $phoneNumbers
     * @param string|array $message
     * @return array
     */
    public function sendBulk(array $phoneNumbers, $message): array
    {
        $results = [];

        foreach ($phoneNumbers as $phone) {
            $results[$phone] = $this->send($phone, $message);
        }

        return $results;
    }

    /**
     * Envoyer un SMS via Orange
     */
    protected function sendViaOrange(string $phoneNumber, string $message): bool
    {
        // Obtenir le token d'accès
        $token = $this->getOrangeAccessToken();

        // Envoyer le SMS
        $response = Http::withToken($token)
            ->post($this->baseUrl . '/messages', [
                'outboundSMSMessageRequest' => [
                    'address' => "tel:+{$phoneNumber}",
                    'senderAddress' => "tel:+{$this->senderId}",
                    'outboundSMSTextMessage' => [
                        'message' => $message
                    ]
                ]
            ]);

        if (!$response->successful()) {
            throw new \Exception('Erreur lors de l\'envoi du SMS via Orange: ' . $response->body());
        }

        return true;
    }

    /**
     * Envoyer un SMS via MTN
     */
    protected function sendViaMTN(string $phoneNumber, string $message): bool
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
        ])->post($this->baseUrl . '/sms/send', [
            'from' => $this->senderId,
            'to' => $phoneNumber,
            'message' => $message,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Erreur lors de l\'envoi du SMS via MTN: ' . $response->body());
        }

        return true;
    }

    /**
     * Envoyer un SMS via Moov
     */
    protected function sendViaMoov(string $phoneNumber, string $message): bool
    {
        $response = Http::withHeaders([
            'X-API-Key' => $this->apiKey,
        ])->post($this->baseUrl . '/messages', [
            'sender' => $this->senderId,
            'recipient' => $phoneNumber,
            'content' => $message,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Erreur lors de l\'envoi du SMS via Moov: ' . $response->body());
        }

        return true;
    }

    /**
     * Obtenir le token d'accès Orange
     */
    protected function getOrangeAccessToken(): string
    {
        $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
            ->post($this->baseUrl . '/oauth/token', [
                'grant_type' => 'client_credentials'
            ]);

        if (!$response->successful()) {
            throw new \Exception('Erreur lors de l\'obtention du token Orange');
        }

        return $response->json()['access_token'];
    }

    /**
     * Formater le numéro de téléphone
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        // Supprimer tous les caractères non numériques
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Ajouter le préfixe pays si nécessaire (225 pour la Côte d'Ivoire)
        if (strlen($phoneNumber) === 8) {
            $phoneNumber = '225' . $phoneNumber;
        }

        return $phoneNumber;
    }

    /**
     * Formater le message si c'est un tableau
     */
    protected function formatMessage($message): string
    {
        if (is_array($message)) {
            return implode("\n\n", array_filter([
                $message['title'] ?? null,
                $message['content'] ?? null,
            ]));
        }

        return $message;
    }

    /**
     * Obtenir l'URL de base du fournisseur
     */
    protected function getProviderBaseUrl(): string
    {
        return match($this->provider) {
            'orange' => 'https://api.orange.com/smsmessaging/v1',
            'mtn' => 'https://api.mtn.com/v1',
            'moov' => 'https://api.moov.ci/v1',
            default => throw new \Exception("Fournisseur SMS non supporté: {$this->provider}"),
        };
    }
}
