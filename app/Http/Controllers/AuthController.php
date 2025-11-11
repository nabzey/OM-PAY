<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * @OA\Info(
 *     title="API Orange Money",
 *     version="1.0.0",
 *     description="API pour la gestion des comptes Orange Money"
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Serveur de développement"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="passport",
 *     type="oauth2",
 *     @OA\Flow(
 *         flow="password",
 *         tokenUrl="http://localhost:8000/api/login",
 *         refreshUrl="http://localhost:8000/api/login",
 *         scopes={}
 *     )
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Connexion utilisateur",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=31536000),
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="refresh_token", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Identifiants invalides"
     *     )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $compte = Compte::where('email', $request->email)->first();

        if (!$compte || !Hash::check($request->password, $compte->password)) {
            return response()->json([
                'message' => 'Identifiants invalides'
            ], 401);
        }

        // Générer le token d'accès
        $token = $compte->createToken('Orange Money API')->accessToken;

        return response()->json([
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
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Déconnexion utilisateur",
     *     tags={"Authentification"},
     *     security={{"passport": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'Déconnexion réussie'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/client",
     *     summary="Informations du client connecté",
     *     tags={"Authentification"},
     *     security={{"passport": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Informations client",
     *         @OA\JsonContent(ref="#/components/schemas/Compte")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     )
     * )
     */
    public function client(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    /**
     * @OA\Post(
     *     path="/api/refresh",
     *     summary="Rafraîchir le token d'accès",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"refresh_token"},
     *             @OA\Property(property="refresh_token", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token rafraîchi",
     *         @OA\JsonContent(
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=31536000),
     *             @OA\Property(property="access_token", type="string")
     *         )
     *     )
     * )
     */
    public function refresh(Request $request): JsonResponse
    {
        $request->validate([
            'refresh_token' => 'required|string',
        ]);

        // Cette fonctionnalité nécessite une implémentation plus avancée
        // Pour l'instant, on retourne une erreur
        return response()->json([
            'message' => 'Fonctionnalité de rafraîchissement à implémenter'
        ], 501);
    }
}
