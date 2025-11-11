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
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compte_id')->constrained('comptes')->onDelete('cascade');
            $table->enum('type', ['email', 'sms']);
            $table->string('destinataire'); // email ou numéro téléphone
            $table->enum('statut', ['en_attente', 'envoye', 'echoue'])->default('en_attente');
            $table->text('message')->nullable();
            $table->text('erreur')->nullable();
            $table->timestamp('envoye_at')->nullable();
            $table->timestamps();

            $table->index(['compte_id', 'type']);
            $table->index(['statut', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
