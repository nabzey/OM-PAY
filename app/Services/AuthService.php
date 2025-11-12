<?php

namespace App\Services;

use App\Models\AuthCode;
use App\Models\Compte;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

class AuthService
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Envoyer un OTP par SMS
     * @param string $telephone
     * @return bool
     */
    public function envoyerOtp(string $telephone): bool
    {
        try {
            // Vérifier si le numéro appartient à un compte existant
            $compte = Compte::where('telephone', $telephone)->first();
            if (!$compte) {
                Log::warning("Tentative d'authentification avec numéro inconnu: {$telephone}");
                return false; // Ne pas révéler qu'un numéro n'existe pas
            }

            // Supprimer l'ancien OTP pour ce numéro s'il existe
            AuthCode::where('telephone', $telephone)->delete();

            // Créer un nouveau OTP d'authentification
            $authCode = AuthCode::createForTelephone($telephone);

            // Envoyer l'OTP par SMS et afficher dans les logs pour les tests
            $message = "Votre OTP Orange Money: {$authCode->code}. Valide 5 minutes.";

            // Essayer d'envoyer le SMS, mais ne pas échouer si la limite est atteinte
            try {
                $result = $this->smsService->envoyerSms($telephone, $message);
            } catch (\Exception $e) {
                // En développement, on considère que c'est réussi même si le SMS échoue
                if (config('app.env') === 'local') {
                    $result = true;
                    Log::info("SMS non envoyé (limite atteinte), mais OTP généré avec succès pour {$telephone}: {$authCode->code}");
                } else {
                    $result = false;
                }
            }

            // En développement, forcer le succès si le SMS échoue mais que l'OTP est généré
            if (config('app.env') === 'local' && !$result) {
                $result = true;
                Log::info("Mode développement: OTP généré avec succès malgré l'échec SMS pour {$telephone}: {$authCode->code}");
            }

            // Afficher toujours dans les logs pour faciliter les tests en développement
            Log::info("OTP généré pour {$telephone}: {$authCode->code}");

            if ($result) {
                Log::info("OTP envoyé avec succès à {$telephone}");
                return true;
            } else {
                Log::error("Échec d'envoi du SMS pour l'OTP à {$telephone}");
                return false;
            }

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi de l'OTP à {$telephone}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier l'OTP
     * @param string $telephone
     * @param string $otp
     * @return Compte|null
     */
    public function verifierOtp(string $telephone, string $otp): ?Compte
    {
        try {
            // Nettoyer les OTP expirés
            AuthCode::cleanExpired();

            // Trouver l'OTP valide pour ce numéro
            $authCode = AuthCode::where('telephone', $telephone)
                               ->valid()
                               ->first();

            if (!$authCode) {
                Log::warning("Aucun OTP valide trouvé pour {$telephone}");
                return null;
            }

            // Vérifier l'OTP
            if (!$authCode->isValid($otp)) {
                Log::warning("OTP invalide fourni pour {$telephone}");
                return null;
            }

            // Marquer l'OTP comme utilisé
            $authCode->markAsUsed();

            // Retourner le compte associé
            $compte = Compte::where('telephone', $telephone)->first();

            if ($compte) {
                Log::info("Authentification réussie pour le compte {$compte->id} ({$telephone})");
            }

            return $compte;

        } catch (\Exception $e) {
            Log::error("Erreur lors de la vérification de l'OTP pour {$telephone}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Générer un token d'accès pour le compte
     * @param Compte $compte
     * @return array
     */
    public function genererTokenAcces(Compte $compte): array
    {
        // Générer le token d'accès
        $token = $compte->createToken('Orange Money API')->accessToken;

        return [
            'token_type' => 'Bearer',
            'expires_in' => 31536000, // 1 an
            'access_token' => $token,
            'user' => [
                'id' => $compte->id,
                'nom' => $compte->nom,
                'email' => $compte->email,
                'telephone' => $compte->telephone,
                'type_compte' => $compte->type_compte,
                'statut_compte' => $compte->statut_compte,
            ]
        ];
    }
}