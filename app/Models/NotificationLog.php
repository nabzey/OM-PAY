<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'compte_id',
        'type',
        'destinataire',
        'statut',
        'message',
        'erreur',
        'envoye_at',
    ];

    protected $casts = [
        'envoye_at' => 'datetime',
    ];

    /**
     * Relation avec le compte
     */
    public function compte(): BelongsTo
    {
        return $this->belongsTo(Compte::class);
    }

    /**
     * Scope pour les notifications en attente
     */
    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    /**
     * Scope pour les notifications envoyées
     */
    public function scopeEnvoyees($query)
    {
        return $query->where('statut', 'envoye');
    }

    /**
     * Scope pour les notifications échouées
     */
    public function scopeEchouees($query)
    {
        return $query->where('statut', 'echoue');
    }

    /**
     * Scope pour filtrer par type
     */
    public function scopeParType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Marquer comme envoyé
     */
    public function marquerCommeEnvoye(string $message = null): void
    {
        $this->update([
            'statut' => 'envoye',
            'message' => $message,
            'envoye_at' => now(),
            'erreur' => null,
        ]);
    }

    /**
     * Marquer comme échoué
     */
    public function marquerCommeEchoue(string $erreur): void
    {
        $this->update([
            'statut' => 'echoue',
            'erreur' => $erreur,
        ]);
    }
}
