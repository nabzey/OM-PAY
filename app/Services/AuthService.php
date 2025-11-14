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
     * @return array|null Retourne ['success' => bool, 'otp' => string] ou null si échec
     */
    public function envoyerOtp(string $telephone): ?array
    {
        try {
            // Vérifier si le numéro appartient à un compte existant
            $compte = Compte::where('telephone', $telephone)->first();
            if (!$compte) {
                Log::warning("Tentative d'authentification avec numéro inconnu: {$telephone}");
                return null; // Ne pas révéler qu'un numéro n'existe pas
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

            // Retourner toujours l'OTP généré, même si le SMS échoue
            return [
                'success' => $result,
                'otp' => $authCode->code
            ];

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi de l'OTP à {$telephone}: " . $e->getMessage());
            return null;
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

            // Retourner le compte associé et marquer comme vérifié par OTP
            $compte = Compte::where('telephone', $telephone)->first();

            if ($compte) {
                // Marquer le compte comme vérifié par OTP pour permettre l'authentification par mot de passe
                $compte->update(['otp_verified' => true]);
                Log::info("Authentification OTP réussie pour le compte {$compte->id} ({$telephone})");
            }

            return $compte;

        } catch (\Exception $e) {
            Log::error("Erreur lors de la vérification de l'OTP pour {$telephone}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Authentifier avec mot de passe (après vérification OTP)
     * @param string $telephone
     * @param string $password
     * @return Compte|null
     */
    public function authentifierParMotDePasse(string $telephone, string $password): ?Compte
    {
        try {
            // Trouver le compte
            $compte = Compte::where('telephone', $telephone)->first();

            if (!$compte) {
                Log::warning("Tentative d'authentification avec numéro inconnu: {$telephone}");
                return null;
            }

            // Vérifier que le compte a été vérifié par OTP au préalable
            if (!$compte->otp_verified) {
                Log::warning("Tentative d'authentification par mot de passe sans vérification OTP préalable pour {$telephone}");
                return null;
            }

            // Vérifier le mot de passe
            if (!\Illuminate\Support\Facades\Hash::check($password, $compte->password)) {
                Log::warning("Mot de passe invalide pour {$telephone}");
                return null;
            }

            // Vérifier que le compte est actif
            if ($compte->statut_compte !== 'actif') {
                Log::warning("Tentative d'authentification sur un compte inactif: {$telephone}");
                return null;
            }

            Log::info("Authentification par mot de passe réussie pour le compte {$compte->id} ({$telephone})");
            return $compte;

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'authentification par mot de passe pour {$telephone}: " . $e->getMessage());
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
        ];
    }
}