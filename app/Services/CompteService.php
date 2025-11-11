<?php

namespace App\Services;

use App\Jobs\SendNotification;
use App\Models\Compte;
use App\Repositories\CompteRepository;
use App\Events\CompteCréé;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class CompteService
{
    protected CompteRepository $repository;

    public function __construct(CompteRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAllComptes(): Collection
    {
        return $this->repository->all();
    }

    public function getCompteById(int $id): ?Compte
    {
        return $this->repository->find($id);
    }

    public function createCompte(array $data): Compte
    {
        $data['password'] = Hash::make($data['password']);

        $compte = $this->repository->create($data);

        // Déclencher l'événement CompteCréé
        event(new CompteCréé($compte));

        // Programmer l'envoi des notifications en file d'attente
        // Temporairement désactivé pour éviter les erreurs SendGrid
        // SendNotification::dispatch($compte, 'email');
        // SendNotification::dispatch($compte, 'sms');

        return $compte;
    }

    public function updateCompte(Compte $compte, array $data): bool
    {
        return $this->repository->update($compte, $data);
    }

    public function deleteCompte(Compte $compte): bool
    {
        return $this->repository->delete($compte);
    }

    public function getCompteByEmail(string $email): ?Compte
    {
        return $this->repository->findByEmail($email);
    }
}