<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'compte_id',
        'type',
        'methode_paiement',
        'destinataire',
        'montant',
        'devise',
        'statut',
        'description',
        'metadata',
        'date_execution',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'metadata' => 'array',
        'date_execution' => 'datetime',
    ];

    /**
     * Relation avec le compte
     */
    public function compte(): BelongsTo
    {
        return $this->belongsTo(Compte::class);
    }

    /**
     * Générer une référence unique
     */
    public static function generateReference(): string
    {
        do {
            $reference = 'TXN' . date('Ymd') . strtoupper(Str::random(8));
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Marquer comme réussie
     */
    public function marquerCommeReussie(): void
    {
        $this->update([
            'statut' => 'reussi',
            'date_execution' => now(),
        ]);
    }

    /**
     * Marquer comme échouée
     */
    public function marquerCommeEchouee(string $raison = null): void
    {
        $metadata = $this->metadata ?? [];
        if ($raison) {
            $metadata['raison_echec'] = $raison;
        }

        $this->update([
            'statut' => 'echoue',
            'metadata' => $metadata,
        ]);
    }

    /**
     * Scope pour les transactions réussies
     */
    public function scopeReussies($query)
    {
        return $query->where('statut', 'reussi');
    }

    /**
     * Scope pour les transactions en attente
     */
    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    /**
     * Scope pour les transactions par type
     */
    public function scopeParType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope pour les transactions d'un compte
     */
    public function scopePourCompte($query, int $compteId)
    {
        return $query->where('compte_id', $compteId);
    }
}
