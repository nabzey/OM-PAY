<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Créer 10 comptes avec des données variées (réduit pour éviter les conflits)
        \App\Models\Compte::factory(10)->create();

        // Créer quelques comptes spécifiques pour les tests
        \App\Models\Compte::factory()->create([
            'id_client' => 'CLI-TEST001',
            'numero_compte' => 'OM1234567890',
            'nom' => 'Test User',
            'email' => 'test@example.com',
            'telephone' => '+221773657435',
            'type_compte' => 'courant',
            'statut_compte' => 'actif',
        ]);

        \App\Models\Compte::factory()->create([
            'id_client' => 'CLI-TEST002',
            'numero_compte' => 'OM0987654321',
            'nom' => 'Admin User',
            'email' => 'admin@example.com',
            'telephone' => '+221778765432',
            'type_compte' => 'entreprise',
            'statut_compte' => 'actif',
        ]);

        \App\Models\Compte::factory()->create([
            'id_client' => 'CLI-TEST003',
            'numero_compte' => 'OM1122334455',
            'nom' => 'Inactive User',
            'email' => 'inactive@example.com',
            'telephone' => '+221769876543',
            'type_compte' => 'epargne',
            'statut_compte' => 'inactif',
        ]);

        \App\Models\Compte::factory()->create([
            'id_client' => 'CLI-TEST004',
            'numero_compte' => 'OM7768069690',
            'nom' => 'Test OTP User',
            'email' => 'otp@example.com',
            'telephone' => '+2217768069690',
            'type_compte' => 'courant',
            'statut_compte' => 'actif',
        ]);
    }
}
