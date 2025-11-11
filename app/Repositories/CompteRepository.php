<?php

namespace App\Repositories;

use App\Models\Compte;
use Illuminate\Database\Eloquent\Collection;

class CompteRepository
{
    protected Compte $model;

    public function __construct(Compte $compte)
    {
        $this->model = $compte;
    }

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function find(int $id): ?Compte
    {
        return $this->model->find($id);
    }

    public function create(array $data): Compte
    {
        return $this->model->create($data);
    }

    public function update(Compte $compte, array $data): bool
    {
        return $compte->update($data);
    }

    public function delete(Compte $compte): bool
    {
        return $compte->delete();
    }

    public function findByEmail(string $email): ?Compte
    {
        return $this->model->where('email', $email)->first();
    }
}