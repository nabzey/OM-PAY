<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comptes', function (Blueprint $table) {
            $table->id();
            $table->string('id_client')->unique();
            $table->string('numero_compte')->unique();
            $table->string('nom');
            $table->string('email')->unique();
            $table->string('telephone')->unique();
            $table->enum('type_compte', ['courant', 'epargne', 'entreprise'])->default('courant');
            $table->enum('statut_compte', ['actif', 'inactif', 'bloque', 'suspendu'])->default('actif');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comptes');
    }
};
