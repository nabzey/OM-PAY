<?php

namespace App\Listeners;

use App\Events\CompteCréé;
use App\Mail\CompteCree;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(CompteCréé $event): void
    {
        try {
            // Envoyer l'email de bienvenue
            Mail::to($event->compte->email)->send(new CompteCree($event->compte));

            Log::info('Email de bienvenue envoyé avec succès à ' . $event->compte->email);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email à ' . $event->compte->email . ': ' . $e->getMessage());
        }
    }
}