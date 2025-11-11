<?php

namespace App\Mail;

use App\Models\Compte;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompteCree extends Mailable
{
    use Queueable, SerializesModels;

    public Compte $compte;

    public function __construct(Compte $compte)
    {
        $this->compte = $compte;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bienvenue sur Orange Money - Compte créé avec succès',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.compte-cree',
            with: [
                'compte' => $this->compte,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}