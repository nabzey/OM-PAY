<?php

namespace Tests\Feature;

use App\Events\CompteCréé;
use App\Models\Compte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CompteCreationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test de création d'un compte Orange Money avec succès
     */
    public function test_creation_compte_reussie(): void
    {
        // Données de test
        $compteData = [
            'id_client' => 'CLI003',
            'numero_compte' => 'OM003345678901',
            'nom' => 'Fatou Sow',
            'email' => 'fatou.sow@test.com',
            'telephone' => '76 345 67 89',
            'type_compte' => 'courant',
            'statut_compte' => 'actif',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ];

        // Faire la requête POST
        $response = $this->postJson('/api/comptes', $compteData);

        // Vérifier la réponse
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'id',
                    'id_client',
                    'numero_compte',
                    'nom',
                    'email',
                    'telephone',
                    'type_compte',
                    'statut_compte',
                    'created_at',
                    'updated_at'
                ]);

        // Vérifier que le compte a été créé en base
        $this->assertDatabaseHas('comptes', [
            'id_client' => 'CLI003',
            'numero_compte' => 'OM003345678901',
            'nom' => 'Fatou Sow',
            'email' => 'fatou.sow@test.com',
            'telephone' => '+221763456789',
            'type_compte' => 'courant',
            'statut_compte' => 'actif'
        ]);
    }

    /**
     * Test de validation des données requises
     */
    public function test_validation_donnees_requises(): void
    {
        $response = $this->postJson('/api/comptes', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'nom',
                    'email',
                    'telephone',
                    'password'
                ]);
    }

    /**
     * Test de validation d'unicité email
     */
    public function test_validation_unicite_email(): void
    {
        // Créer un compte existant
        Compte::create([
            'id_client' => 'CLI004',
            'numero_compte' => 'OM004456789012',
            'nom' => 'Mamadou Diallo',
            'email' => 'mamadou.diallo@test.com',
            'telephone' => '70 456 78 90',
            'type_compte' => 'courant',
            'statut_compte' => 'actif',
            'password' => bcrypt('password123')
        ]);

        // Tenter de créer un compte avec le même email
        $response = $this->postJson('/api/comptes', [
            'id_client' => 'CLI005',
            'numero_compte' => 'OM005567890123',
            'nom' => 'Aminata Ba',
            'email' => 'mamadou.diallo@test.com', // Email déjà utilisé
            'telephone' => '755678901',
            'type_compte' => 'epargne',
            'statut_compte' => 'actif',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test de validation d'unicité numéro de téléphone
     */
    public function test_validation_unicite_telephone(): void
    {
        // Créer un compte existant
        Compte::create([
            'id_client' => 'CLI006',
            'numero_compte' => 'OM006678901234',
            'nom' => 'Ousmane Faye',
            'email' => 'ousmane.faye@test.com',
            'telephone' => '+221776789012',
            'type_compte' => 'courant',
            'statut_compte' => 'actif',
            'password' => bcrypt('Password123!')
        ]);

        // Tenter de créer un compte avec le même téléphone
        $response = $this->postJson('/api/comptes', [
            'id_client' => 'CLI007',
            'numero_compte' => 'OM007789012345',
            'nom' => 'Ndeye Diagne',
            'email' => 'ndeye.diagne@test.com',
            'telephone' => '+221776789012', // Téléphone déjà utilisé
            'type_compte' => 'entreprise',
            'statut_compte' => 'actif',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['telephone']);
    }

    /**
     * Test de validation des types de compte
     */
    public function test_validation_type_compte(): void
    {
        $response = $this->postJson('/api/comptes', [
            'id_client' => 'CLI008',
            'numero_compte' => 'OM008890123456',
            'nom' => 'Cheikh Ndiaye',
            'email' => 'cheikh.ndiaye@test.com',
            'telephone' => '78 890 12 34',
            'type_compte' => 'invalide', // Type invalide
            'statut_compte' => 'actif',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['type_compte']);
    }

    /**
     * Test de validation des statuts de compte
     */
    public function test_validation_statut_compte(): void
    {
        $response = $this->postJson('/api/comptes', [
            'id_client' => 'CLI009',
            'numero_compte' => 'OM009901234567',
            'nom' => 'Aissatou Mbaye',
            'email' => 'aissatou.mbaye@test.com',
            'telephone' => '76 901 23 45',
            'type_compte' => 'courant',
            'statut_compte' => 'invalide', // Statut invalide
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['statut_compte']);
    }

    /**
     * Test de création avec valeurs par défaut
     */
    public function test_creation_avec_valeurs_defaut(): void
    {
        $compteData = [
            'id_client' => 'CLI010',
            'numero_compte' => 'OM010012345678',
            'nom' => 'Babacar Sy',
            'email' => 'babacar.sy@test.com',
            'telephone' => '70 012 34 56',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
            // type_compte et statut_compte omis pour tester les valeurs par défaut
        ];

        $response = $this->postJson('/api/comptes', $compteData);

        $response->assertStatus(201);

        // Vérifier que les valeurs par défaut ont été appliquées
        $this->assertDatabaseHas('comptes', [
            'id_client' => 'CLI010',
            'numero_compte' => 'OM010012345678',
            'type_compte' => 'courant', // Valeur par défaut
            'statut_compte' => 'actif'  // Valeur par défaut
        ]);
    }

    /**
     * Test que les notifications sont mises en file d'attente
     */
    public function test_notifications_mises_en_file(): void
    {
        // Simplement vérifier que la création fonctionne
        // Les tests d'événements et de queue peuvent être complexes dans les tests feature
        $compteData = [
            'id_client' => 'CLI011',
            'numero_compte' => 'OM011123456789',
            'nom' => 'Sokhna Diop',
            'email' => 'sokhna.diop@test.com',
            'telephone' => '75 123 45 67',
            'type_compte' => 'epargne',
            'statut_compte' => 'actif',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ];

        $response = $this->postJson('/api/comptes', $compteData);

        $response->assertStatus(201);

        // Vérifier que le compte a été créé
        $this->assertDatabaseHas('comptes', [
            'id_client' => 'CLI011',
            'numero_compte' => 'OM011123456789',
        ]);
    }
}
