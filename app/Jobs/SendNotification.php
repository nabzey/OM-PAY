<?php

namespace App\Jobs;

use App\Models\Compte;
use App\Models\NotificationLog;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\CompteCree;

class SendNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Compte $compte;
    protected string $type; // 'email' ou 'sms'
    protected ?NotificationLog $log = null;

    /**
     * Create a new job instance.
     */
    public function __construct(Compte $compte, string $type)
    {
        $this->compte = $compte;
        $this->type = $type;

        // Créer le log de notification
        $this->log = NotificationLog::create([
            'compte_id' => $compte->id,
            'type' => $type,
            'destinataire' => $type === 'email' ? $compte->email : $compte->telephone,
            'statut' => 'en_attente',
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            if ($this->type === 'email') {
                $this->sendEmail();
            } elseif ($this->type === 'sms') {
                $this->sendSms();
            }
        } catch (\Exception $e) {
            $this->log->marquerCommeEchoue($e->getMessage());
            Log::error("Erreur lors de l'envoi de {$this->type} pour le compte {$this->compte->id}: " . $e->getMessage());
            throw $e; // Re-throw pour que le job soit marqué comme échoué
        }
    }

    /**
     * Envoyer l'email de bienvenue
     */
    private function sendEmail(): void
    {
        try {
            Mail::to($this->compte->email)->send(new CompteCree($this->compte));
            $this->log->marquerCommeEnvoye("Email de bienvenue envoyé avec succès");
            Log::info("Email de bienvenue envoyé avec succès à {$this->compte->email}");
        } catch (\Exception $e) {
            $this->log->marquerCommeEchoue($e->getMessage());
            Log::error("Erreur lors de l'envoi de l'email à {$this->compte->email}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Envoyer le SMS de bienvenue
     */
    private function sendSms(): void
    {
        try {
            $smsService = app(SmsService::class);
            $result = $smsService->envoyerSmsBienvenue($this->compte);

            if ($result) {
                $this->log->marquerCommeEnvoye("SMS de bienvenue envoyé avec succès");
                Log::info("SMS de bienvenue envoyé avec succès à {$this->compte->telephone}");
            } else {
                throw new \Exception("Échec de l'envoi du SMS");
            }
        } catch (\Exception $e) {
            $this->log->marquerCommeEchoue($e->getMessage());
            Log::error("Erreur lors de l'envoi du SMS à {$this->compte->telephone}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Déterminer le nombre maximum de tentatives
     */
    public function tries(): int
    {
        return 3; // Tenter 3 fois avant d'échouer définitivement
    }

    /**
     * Délai entre les tentatives (en secondes)
     */
    public function backoff(): array
    {
        return [10, 30, 60]; // 10s, 30s, 60s entre les tentatives
    }
}
