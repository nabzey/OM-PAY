<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Compte>
 */
class CompteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['courant', 'epargne', 'entreprise'];
        $statuts = ['actif', 'inactif', 'bloque', 'suspendu'];

        // Générer des valeurs uniques pour éviter les conflits
        do {
            $idClient = 'CLI-' . fake()->numberBetween(100000, 999999);
        } while (\App\Models\Compte::where('id_client', $idClient)->exists());

        do {
            $numeroCompte = 'OM-' . fake()->numberBetween(1000000000, 9999999999);
        } while (\App\Models\Compte::where('numero_compte', $numeroCompte)->exists());

        do {
            $email = fake()->unique()->safeEmail();
        } while (\App\Models\Compte::where('email', $email)->exists());

        do {
            $telephone = '+221' . fake()->randomElement(['77', '78', '76', '70', '75']) . fake()->numberBetween(10000000, 99999999);
        } while (\App\Models\Compte::where('telephone', $telephone)->exists());

        return [
            'id_client' => $idClient,
            'numero_compte' => $numeroCompte,
            'nom' => fake()->name(),
            'email' => $email,
            'telephone' => $telephone,
            'type_compte' => fake()->randomElement($types),
            'statut_compte' => fake()->randomElement($statuts),
            'solde' => fake()->randomFloat(2, 0, 1000000), // Solde entre 0 et 1M XOF
            'code_marchand' => fake()->optional(0.3)->regexify('[A-Z]{2}[0-9]{8}'), // 30% des comptes ont un code marchand
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ];
    }
}
