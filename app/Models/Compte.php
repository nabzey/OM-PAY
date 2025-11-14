<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

/**
 * @OA\Schema(
 *     schema="Compte",
 *     type="object",
 *     title="Compte",
 *     description="Modèle représentant un compte utilisateur",
 *     @OA\Property(property="id", type="integer", description="ID du compte"),
 *     @OA\Property(property="id_client", type="string", description="ID unique du client"),
 *     @OA\Property(property="numero_compte", type="string", description="Numéro unique du compte"),
 *     @OA\Property(property="nom", type="string", description="Nom du compte"),
 *     @OA\Property(property="email", type="string", format="email", description="Email du compte"),
 *     @OA\Property(property="telephone", type="string", description="Numéro de téléphone Orange Money"),
 *     @OA\Property(property="type_compte", type="string", enum={"courant", "epargne", "entreprise"}, description="Type de compte"),
 *     @OA\Property(property="statut_compte", type="string", enum={"actif", "inactif", "bloque", "suspendu"}, description="Statut du compte"),
 *     @OA\Property(property="password", type="string", description="Mot de passe hashé"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Date de mise à jour")
 * )
 */
class Compte extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_client',
        'numero_compte',
        'nom',
        'email',
        'telephone',
        'type_compte',
        'statut_compte',
        'solde',
        'code_marchand',
        'password',
        'otp_verified',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'solde' => 'decimal:2',
        'otp_verified' => 'boolean',
    ];

    /**
     * Relation avec les transactions
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Calculer le solde en tenant compte des transactions réussies
     */
    public function getSoldeCalculéAttribute()
    {
        $soldeInitial = $this->solde ?? 0;

        $totalCredits = $this->transactions()
            ->reussies()
            ->whereIn('type', ['depot', 'transfert'])
            ->sum('montant');

        $totalDebits = $this->transactions()
            ->reussies()
            ->whereIn('type', ['retrait', 'paiement'])
            ->sum('montant');

        return $soldeInitial + $totalCredits - $totalDebits;
    }
}