<?php

namespace App\Events;

use App\Models\Compte;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompteCréé
{
    use Dispatchable, SerializesModels;

    public Compte $compte;

    public function __construct(Compte $compte)
    {
        $this->compte = $compte;
    }
}