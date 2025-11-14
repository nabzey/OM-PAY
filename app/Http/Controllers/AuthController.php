<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\AuthService;

/**
 * @OA\Info(
 *     title="API Orange Money",
 *     version="1.0.0",
 *     description="API complète pour le système de paiement Orange Money avec authentification OTP et mot de passe"
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8001",
 *     description="Serveur de développement"
 * )
 *
 * @OA\Components(
 *     @OA\SecurityScheme(
 *         securityScheme="bearerAuth",
 *         type="http",
 *         scheme="bearer",
 *         description="Token d'accès Bearer obtenu via OTP ou authentification par mot de passe"
 *     )
 * )
 */


class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/send-otp",
     *     summary="Envoyer un OTP par SMS pour première authentification",
     *     description="Envoie un code OTP de 6 chiffres par SMS au numéro fourni pour la première connexion. L'OTP est valide pendant 5 minutes.",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone"},
     *             @OA\Property(property="telephone", type="string", format="phone", example="+221771234567", description="Numéro de téléphone sénégalais au format international")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP envoyé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="OTP envoyé avec succès"),
     *             @OA\Property(property="otp", type="string", example="123456", description="Code OTP généré"),
     *             @OA\Property(property="sms_sent", type="boolean", example=true, description="Indique si le SMS a été envoyé avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur d'envoi ou numéro invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Erreur lors de l'envoi de l'OTP")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données de requête invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'telephone' => ['required', 'string', 'regex:/^\+221(77|78|70|76|75)\d{7}$/']
        ], [
            'telephone.regex' => 'Le numéro de téléphone doit être au format sénégalais (+221 + 77/78/70/76/75 + 7 chiffres, total 9 chiffres après +221).',
        ]);

        $authService = app(\App\Services\AuthService::class);

        $result = $authService->envoyerOtp($request->telephone);

        if ($result) {
            return response()->json([
                'message' => 'OTP envoyé avec succès',
                'otp' => $result['otp'], // Inclure l'OTP dans la réponse
                'sms_sent' => $result['success']
            ]);
        }

        return response()->json([
            'message' => 'Erreur lors de l\'envoi de l\'OTP'
        ], 400);
    }

    /**
     * @OA\Post(
     *     path="/api/verify-otp",
     *     summary="Vérifier l'OTP et obtenir un token d'accès",
     *     description="Vérifie l'OTP fourni et retourne un token d'accès Bearer si l'authentification réussit. Marque le compte comme vérifié par OTP.",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone", "otp"},
     *             @OA\Property(property="telephone", type="string", format="phone", example="+221771234567", description="Numéro de téléphone utilisé pour l'envoi de l'OTP"),
     *             @OA\Property(property="otp", type="string", example="123456", description="Code OTP de 6 chiffres reçu par SMS")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Authentification réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=31536000, description="Durée de validité du token en secondes"),
     *             @OA\Property(property="access_token", type="string", description="Token d'accès Bearer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="OTP invalide ou expiré",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="OTP invalide ou expiré")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données de requête invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
     public function verifyOtp(Request $request): JsonResponse
     {
         $request->validate([
             'telephone' => ['required', 'string', 'regex:/^\+221(77|78|70|76|75)\d{7}$/'],
             'otp' => ['required', 'string', 'size:6'],
         ], [
             'telephone.regex' => 'Le numéro de téléphone doit être au format sénégalais (+221 + 77/78/70/76/75 + 7 chiffres, total 9 chiffres après +221).',
             'otp.required' => 'Le code OTP est obligatoire.',
             'otp.size' => 'Le code OTP doit contenir exactement 6 chiffres.',
         ]);

         $authService = app(\App\Services\AuthService::class);
         $compte = $authService->verifierOtp($request->telephone, $request->otp);

         if (!$compte) {
             return response()->json([
                 'message' => 'OTP invalide ou expiré'
             ], 401);
         }

         $tokenData = $authService->genererTokenAcces($compte);

         return response()->json([
             'token_type' => $tokenData['token_type'],
             'expires_in' => $tokenData['expires_in'],
             'access_token' => $tokenData['access_token']
         ]);
     }

     /**
      * @OA\Post(
      *     path="/api/login",
      *     summary="Connexion avec mot de passe après vérification OTP",
      *     description="Authentifie l'utilisateur avec son numéro de téléphone et mot de passe après vérification OTP préalable.",
      *     tags={"Authentification"},
      *     @OA\RequestBody(
      *         required=true,
      *         @OA\JsonContent(
      *             required={"telephone", "password"},
      *             @OA\Property(property="telephone", type="string", format="phone", example="+221771234567", description="Numéro de téléphone utilisé pour la vérification OTP"),
      *             @OA\Property(property="password", type="string", example="password123", description="Mot de passe du compte")
      *         )
      *     ),
      *     @OA\Response(
      *         response=200,
      *         description="Authentification réussie",
      *         @OA\JsonContent(
      *             @OA\Property(property="token_type", type="string", example="Bearer"),
      *             @OA\Property(property="expires_in", type="integer", example=31536000, description="Durée de validité du token en secondes"),
      *             @OA\Property(property="access_token", type="string", description="Token d'accès Bearer"),
      *             @OA\Property(property="user", type="object",
      *                 @OA\Property(property="id", type="integer"),
      *                 @OA\Property(property="nom", type="string"),
      *                 @OA\Property(property="email", type="string"),
      *                 @OA\Property(property="telephone", type="string"),
      *                 @OA\Property(property="type_compte", type="string"),
      *                 @OA\Property(property="statut_compte", type="string")
      *             )
      *         )
      *     ),
      *     @OA\Response(
      *         response=401,
      *         description="Authentification échouée",
      *         @OA\JsonContent(
      *             @OA\Property(property="message", type="string", example="Numéro de téléphone ou mot de passe invalide")
      *         )
      *     ),
      *     @OA\Response(
      *         response=422,
      *         description="Données de requête invalides",
      *         @OA\JsonContent(
      *             @OA\Property(property="message", type="string"),
      *             @OA\Property(property="errors", type="object")
      *         )
      *     )
      * )
      */
     public function login(Request $request): JsonResponse
     {
         $request->validate([
             'telephone' => ['required', 'string', 'regex:/^\+221(77|78|70|76|75)\d{7}$/'],
             'password' => ['required', 'string', 'min:6'],
         ], [
             'telephone.regex' => 'Le numéro de téléphone doit être au format sénégalais (+221 + 77/78/70/76/75 + 7 chiffres, total 9 chiffres après +221).',
             'password.required' => 'Le mot de passe est obligatoire.',
             'password.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
         ]);

         $authService = app(\App\Services\AuthService::class);
         $compte = $authService->authentifierParMotDePasse($request->telephone, $request->password);

         if (!$compte) {
             return response()->json([
                 'message' => 'Numéro de téléphone ou mot de passe invalide'
             ], 401);
         }

         $tokenData = $authService->genererTokenAcces($compte);

         return response()->json($tokenData);
     }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Authentification par mot de passe",
     *     description="Authentifie un utilisateur avec son numéro de téléphone et mot de passe après vérification OTP initiale",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone", "password"},
     *             @OA\Property(property="telephone", type="string", format="phone", example="+221771234567", description="Numéro de téléphone sénégalais au format international"),
     *             @OA\Property(property="password", type="string", format="password", example="password123", description="Mot de passe du compte")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Authentification réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=31536000, description="Durée de validité du token en secondes"),
     *             @OA\Property(property="access_token", type="string", description="Token d'accès Bearer"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="nom", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="telephone", type="string"),
     *                 @OA\Property(property="type_compte", type="string"),
     *                 @OA\Property(property="statut_compte", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Numéro de téléphone ou mot de passe invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Numéro de téléphone ou mot de passe invalide")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données de requête invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */

    /**
     * @OA\Get(
     *     path="/api/dashboard",
     *     summary="Récupérer les informations du dashboard client",
     *     description="Retourne les informations du client connecté, les détails de son compte avec solde et ses dernières transactions",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Informations du dashboard récupérées avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="client", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="nom", type="string"),
     *                 @OA\Property(property="telephone", type="string")
     *             ),
     *             @OA\Property(property="compte", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="numero_compte", type="string"),
     *                 @OA\Property(property="solde", type="number", format="decimal", description="Solde actuel du compte"),
     *                 @OA\Property(property="code_marchand", type="string", nullable=true)
     *             ),
     *             @OA\Property(property="transactions_recentes", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="reference", type="string"),
     *                     @OA\Property(property="type", type="string"),
     *                     @OA\Property(property="montant", type="number", format="decimal"),
     *                     @OA\Property(property="statut", type="string"),
     *                     @OA\Property(property="date_execution", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function dashboard(Request $request): JsonResponse
    {
        $compte = $request->user(); // Le compte authentifié via Sanctum

        // Récupérer les dernières transactions (5 plus récentes)
        $transactionsRecentes = $compte->transactions()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'reference', 'type', 'montant', 'statut', 'date_execution']);

        return response()->json([
            'client' => [
                'id' => $compte->id,
                'nom' => $compte->nom,
                'telephone' => $compte->telephone,
            ],
            'compte' => [
                'id' => $compte->id,
                'numero_compte' => $compte->numero_compte,
                'solde' => $compte->solde,
                'code_marchand' => $compte->code_marchand,
            ],
            'transactions_recentes' => $transactionsRecentes,
        ]);
    }


}
