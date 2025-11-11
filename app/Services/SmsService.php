<?php

namespace App\Services;

use App\Models\Compte;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class SmsService
{
    protected Client $twilio;

    public function __construct()
    {
        $this->twilio = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
    }

    /**
     * Envoyer un SMS de bienvenue après création de compte
     */
    public function envoyerSmsBienvenue(Compte $compte): bool
    {
        $message = $this->genererMessageBienvenue($compte);
        return $this->envoyerSms($compte->telephone, $message);
    }

    /**
     * Générer le message de bienvenue
     */
    private function genererMessageBienvenue(Compte $compte): string
    {
        return "Bienvenue {$compte->nom} sur Orange Money !\n" .
               "Votre compte {$compte->numero_compte} a été créé avec succès.\n" .
               "Type: " . ucfirst($compte->type_compte) . "\n" .
               "Orange Money - Service sécurisé.";
    }

    /**
     * Envoyer un SMS via Twilio
     */
    private function envoyerSmsViaService(string $telephone, string $message): bool
    {
        try {
            // Formater le numéro de téléphone pour Twilio (format international)
            $formattedPhone = $this->formatterNumeroTelephone($telephone);

            $this->twilio->messages->create(
                $formattedPhone,
                [
                    'from' => config('services.twilio.from'),
                    'body' => $message
                ]
            );

            Log::info("SMS envoyé avec succès à {$formattedPhone}");
            return true;

        } catch (\Exception $e) {
            Log::error("Erreur envoi SMS à {$telephone}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Formater le numéro de téléphone pour Twilio
     */
    private function formatterNumeroTelephone(string $telephone): string
    {
        // Supprimer les espaces et caractères spéciaux
        $telephone = preg_replace('/\s+/', '', $telephone);

        // Si le numéro commence par 77, 78, 70, etc. (Sénégal), ajouter +221
        if (preg_match('/^(77|78|70|76|75)/', $telephone)) {
            return '+221' . $telephone;
        }

        // Si le numéro ne commence pas par +, l'ajouter
        if (!str_starts_with($telephone, '+')) {
            return '+' . $telephone;
        }

        return $telephone;
    }

    /**
     * Envoyer un SMS générique
     */
    public function envoyerSms(string $telephone, string $message): bool
    {
        return $this->envoyerSmsViaService($telephone, $message);
    }
}