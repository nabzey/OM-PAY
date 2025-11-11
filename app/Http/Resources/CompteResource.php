<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="CompteResource",
 *     type="object",
 *     title="Compte Resource",
 *     description="Ressource représentant un compte Orange Money formaté",
 *     @OA\Property(property="id", type="integer", description="ID du compte"),
 *     @OA\Property(property="id_client", type="string", description="ID unique du client"),
 *     @OA\Property(property="numero_compte", type="string", description="Numéro unique du compte"),
 *     @OA\Property(property="nom", type="string", description="Nom du compte"),
 *     @OA\Property(property="email", type="string", format="email", description="Email du compte"),
 *     @OA\Property(property="telephone", type="string", description="Numéro de téléphone"),
 *     @OA\Property(property="type_compte", type="string", enum={"courant", "epargne", "entreprise"}, description="Type de compte"),
 *     @OA\Property(property="statut_compte", type="string", enum={"actif", "inactif", "bloque", "suspendu"}, description="Statut du compte"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Date de mise à jour")
 * )
 */
class CompteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_client' => $this->id_client,
            'numero_compte' => $this->numero_compte,
            'nom' => $this->nom,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'type_compte' => $this->type_compte,
            'statut_compte' => $this->statut_compte,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}