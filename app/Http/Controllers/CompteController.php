<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCompteRequest;
use App\Http\Resources\CompteResource;
use App\Models\Compte;
use App\Services\CompteService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CompteController extends Controller
{
    protected CompteService $compteService;

    public function __construct(CompteService $compteService)
    {
        $this->compteService = $compteService;
    }


    /**
     * @OA\Post(
     *     path="/api/comptes",
     *     summary="Créer un nouveau compte Orange Money",
     *     tags={"Comptes"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id_client", "numero_compte", "nom", "email", "telephone", "password"},
     *             @OA\Property(property="id_client", type="string", example="CLI001"),
     *             @OA\Property(property="numero_compte", type="string", example="OM001234567890"),
     *             @OA\Property(property="nom", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="telephone", type="string", example="77 123 45 67"),
     *             @OA\Property(property="type_compte", type="string", enum={"courant", "epargne", "entreprise"}, example="courant"),
     *             @OA\Property(property="statut_compte", type="string", enum={"actif", "inactif", "bloque", "suspendu"}, example="actif"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/CompteResource")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     )
     * )
     */
    public function store(CreateCompteRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $compte = $this->compteService->createCompte($validated);

        return response()->json(new CompteResource($compte), 201);
    }



}