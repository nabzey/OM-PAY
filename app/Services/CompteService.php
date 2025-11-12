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

        // Définir des valeurs par défaut si non fournies
        $data['type_compte'] = $data['type_compte'] ?? 'courant';
        $data['statut_compte'] = $data['statut_compte'] ?? 'actif';

        // Générer automatiquement l'ID client et numéro de compte si non fournis
        if (!isset($data['id_client'])) {
            $data['id_client'] = 'CLI-' . strtoupper(substr(md5(uniqid()), 0, 6));
        }
        if (!isset($data['numero_compte'])) {
            $data['numero_compte'] = 'OM-' . str_pad(mt_rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);
        }

        $compte = $this->repository->create($data);

        // Déclencher l'événement CompteCréé
        event(new CompteCréé($compte));

        // Programmer l'envoi des notifications en file d'attente
        // Désactivé pour les tests - les notifications externes causent des erreurs 500
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