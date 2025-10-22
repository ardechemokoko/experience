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
        Schema::create('dossiers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('candidat_id')->constrained('candidats')->onDelete('cascade');
            $table->foreignUuid('auto_ecole_id')->constrained('auto_ecoles')->onDelete('cascade');
            $table->foreignUuid('formation_id')->constrained('formation_auto_ecoles')->onDelete('cascade');
            $table->enum('statut', ['en_attente', 'en_cours', 'valide', 'rejete'])->default('en_attente');
            $table->date('date_creation');
            $table->date('date_modification')->nullable();
            $table->text('commentaires')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dossiers');
    }
};
