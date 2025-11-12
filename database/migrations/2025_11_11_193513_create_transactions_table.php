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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('compte_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['paiement', 'transfert', 'depot', 'retrait']);
            $table->enum('methode_paiement', ['code_marchand', 'numero_telephone'])->nullable();
            $table->string('destinataire')->nullable(); // numéro téléphone ou code marchand
            $table->decimal('montant', 15, 2);
            $table->string('devise', 3)->default('XOF');
            $table->enum('statut', ['en_attente', 'reussi', 'echoue', 'annule'])->default('en_attente');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('date_execution')->nullable();
            $table->timestamps();

            $table->index(['compte_id', 'statut']);
            $table->index(['type', 'statut']);
            $table->index('reference');
            $table->index('date_execution');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
