<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AuthCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'telephone',
        'code',
        'expires_at',
        'used',
    ];

    protected $table = 'auth_codes';

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean',
    ];

    /**
     * Générer un OTP de 6 chiffres
     */
    public static function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Créer un nouveau OTP d'authentification
     */
    public static function createForTelephone(string $telephone): self
    {
        // Supprimer les anciens OTP pour ce numéro (au lieu de les marquer comme utilisés)
        self::where('telephone', $telephone)->delete();

        return self::create([
            'telephone' => $telephone,
            'code' => self::generateOtp(),
            'expires_at' => Carbon::now()->addMinutes(5), // Expire dans 5 minutes
            'used' => false,
        ]);
    }

    /**
     * Vérifier si l'OTP est valide
     */
    public function isValid(string $otp): bool
    {
        return !$this->used &&
               $this->code === $otp &&
               $this->expires_at->isFuture();
    }

    /**
     * Marquer le code comme utilisé
     */
    public function markAsUsed(): void
    {
        $this->update(['used' => true]);
    }

    /**
     * Scope pour les codes valides
     */
    public function scopeValid($query)
    {
        return $query->where('used', false)
                    ->where('expires_at', '>', now());
    }

    /**
     * Nettoyer les codes expirés
     */
    public static function cleanExpired(): int
    {
        return self::where('expires_at', '<', now())->delete();
    }
}
