<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\SmsService;

class SmsAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'content',
        'region',
        'languages',
        'sent_at',
    ];

    protected $casts = [
        'languages' => 'array',
        'sent_at' => 'datetime',
    ];

    // Constantes pour les types d'alertes
    const TYPE_METEO = 'meteo';
    const TYPE_MALADIE = 'maladie';
    const TYPE_CONSEIL = 'conseil';

    // Scopes
    public function scopeUnsent($query)
    {
        return $query->whereNull('sent_at');
    }

    public function scopeByRegion($query, $region)
    {
        return $query->where('region', $region);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Méthodes
    public function send()
    {
        try {
            // Récupérer tous les utilisateurs de la région ciblée
            $users = User::where(function ($query) {
                if ($this->region) {
                    $query->where('region', $this->region);
                }
            })
            ->where('role', 'producteur')
            ->get();

            foreach ($users as $user) {
                // Déterminer la langue préférée de l'utilisateur (à implémenter)
                $preferredLanguage = $this->getPreferredLanguage($user);
                
                // Obtenir le contenu traduit
                $translatedContent = $this->getTranslatedContent($preferredLanguage);

                // Envoyer le SMS via le service SMS
                $smsService = app(SmsService::class);
                $smsService->send(
                    $user->phone,
                    $translatedContent
                );
            }

            // Marquer comme envoyé
            $this->sent_at = now();
            $this->save();

            return true;
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'envoi des SMS: ' . $e->getMessage());
            return false;
        }
    }

    protected function getPreferredLanguage(User $user)
    {
        // À implémenter : logique pour déterminer la langue préférée de l'utilisateur
        // Par défaut, utiliser le français
        return 'fr';
    }

    protected function getTranslatedContent($language)
    {
        // Vérifier si la traduction existe
        if (!isset($this->languages[$language])) {
            // Si pas de traduction, utiliser le français par défaut
            $language = 'fr';
        }

        return [
            'title' => $this->languages[$language]['title'] ?? $this->title,
            'content' => $this->languages[$language]['content'] ?? $this->content,
        ];
    }

    // Méthodes statiques pour créer différents types d'alertes
    public static function createMeteoAlert($data)
    {
        return static::create(array_merge(
            $data,
            ['type' => self::TYPE_METEO]
        ));
    }

    public static function createMaladieAlert($data)
    {
        return static::create(array_merge(
            $data,
            ['type' => self::TYPE_MALADIE]
        ));
    }

    public static function createConseilAlert($data)
    {
        return static::create(array_merge(
            $data,
            ['type' => self::TYPE_CONSEIL]
        ));
    }

    // Méthode pour planifier l'envoi d'une alerte
    public function schedule($datetime)
    {
        // À implémenter : intégration avec le système de files d'attente de Laravel
        // pour planifier l'envoi de l'alerte à une date/heure spécifique
    }

    // Boot method pour les événements du modèle
    protected static function boot()
    {
        parent::boot();

        // Avant la sauvegarde, s'assurer que les langues sont au bon format
        static::saving(function ($alert) {
            if (!is_array($alert->languages)) {
                $alert->languages = ['fr' => [
                    'title' => $alert->title,
                    'content' => $alert->content,
                ]];
            }
        });
    }
}
