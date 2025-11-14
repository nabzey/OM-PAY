<?php

namespace Tests\Feature;

use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoldeTest extends TestCase
{
    use RefreshDatabase;

    public function test_solde_sans_transactions()
    {
        $compte = Compte::factory()->create(['solde' => 1000.00]);

        $response = $this->actingAs($compte, 'api')
            ->getJson("/api/compte/{$compte->id}/solde");

        $response->assertStatus(200)
            ->assertJson(['solde' => 1000.00]);
    }

    public function test_solde_avec_transactions_reussies()
    {
        $compte = Compte::factory()->create(['solde' => 1000.00]);

        // Crédit réussi (dépôt)
        Transaction::create([
            'reference' => 'TXN20251113TEST1',
            'compte_id' => $compte->id,
            'type' => 'depot',
            'montant' => 500.00,
            'statut' => 'reussi',
            'devise' => 'XOF',
            'methode_paiement' => 'numero_telephone',
        ]);

        // Débit réussi (retrait)
        Transaction::create([
            'reference' => 'TXN20251113TEST2',
            'compte_id' => $compte->id,
            'type' => 'retrait',
            'montant' => 200.00,
            'statut' => 'reussi',
            'devise' => 'XOF',
            'methode_paiement' => 'numero_telephone',
        ]);

        // Transaction en attente (ne doit pas affecter le solde)
        Transaction::create([
            'reference' => 'TXN20251113TEST3',
            'compte_id' => $compte->id,
            'type' => 'retrait',
            'montant' => 100.00,
            'statut' => 'en_attente',
            'devise' => 'XOF',
            'methode_paiement' => 'numero_telephone',
        ]);

        $response = $this->actingAs($compte, 'api')
            ->getJson("/api/compte/{$compte->id}/solde");

        $response->assertStatus(200)
            ->assertJson(['solde' => 1300.00]); // 1000 + 500 - 200
    }

    public function test_acces_non_autorise_solde_autre_compte()
    {
        $compte1 = Compte::factory()->create();
        $compte2 = Compte::factory()->create();

        $response = $this->actingAs($compte1, 'api')
            ->getJson("/api/compte/{$compte2->id}/solde");

        $response->assertStatus(403)
            ->assertJson(['message' => 'Accès non autorisé']);
    }

    public function test_compte_inexistant()
    {
        $compte = Compte::factory()->create();

        $response = $this->actingAs($compte, 'api')
            ->getJson("/api/compte/999/solde");

        $response->assertStatus(404);
    }
}
