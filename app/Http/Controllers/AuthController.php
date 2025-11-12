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
 *     description="API complète pour le système de paiement Orange Money avec authentification OTP"
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
 *         description="Token d'accès Bearer obtenu via OTP"
 *     )
 * )
 */


class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/send-otp",
     *     summary="Envoyer un OTP par SMS pour authentification",
     *     description="Envoie un code OTP de 6 chiffres par SMS au numéro fourni. L'OTP est valide pendant 5 minutes.",
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
     *             @OA\Property(property="message", type="string", example="OTP envoyé avec succès")
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

        if ($authService->envoyerOtp($request->telephone)) {
            return response()->json([
                'message' => 'OTP envoyé avec succès'
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
     *     description="Vérifie l'OTP fourni et retourne un token d'accès Bearer si l'authentification réussit.",
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

        return response()->json($tokenData);
    }


}
