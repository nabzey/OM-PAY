<?php

namespace App\Listeners;

use App\Events\CompteCréé;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendSmsNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function handle(CompteCréé $event): void
    {
        try {
            // Envoyer le SMS de bienvenue
            $result = $this->smsService->envoyerSmsBienvenue($event->compte);

            if ($result) {
                Log::info('SMS de bienvenue envoyé avec succès à ' . $event->compte->telephone);
            } else {
                Log::error('Échec de l\'envoi du SMS à ' . $event->compte->telephone);
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi du SMS à ' . $event->compte->telephone . ': ' . $e->getMessage());
        }
    }
}