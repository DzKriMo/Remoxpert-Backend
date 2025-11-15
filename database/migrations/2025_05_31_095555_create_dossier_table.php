<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dossiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('agence');
            $table->string('num_sinistre');
            $table->date('date_sinistre');
            $table->date('date_declaration');
            $table->string('expert_nom')->nullable();
            $table->string('assure_nom');
            $table->string('num_police');
            $table->string('compagnie');
            $table->string('code_agence');
            $table->string('num_chassis');
            $table->string('matricule');
            $table->integer('annee');
            $table->string('categorie');
            $table->date('date_debut_assurance');
            $table->date('date_fin_assurance');
            $table->string('carte_grise_photo');
            $table->string('declaration_recto_photo');
            $table->string('declaration_verso_photo');
            $table->string('tiers_nom');
            $table->string('tiers_matricule');
            $table->string('tiers_code_agence');
            $table->string('tiers_num_police');
            $table->string('tiers_compagnie');
            $table->json('photos_accident');
            $table->string('link_pv')->nullable();
            $table->string('link_note')->nullable();
            $table->enum('status', ['new', 'in_progress', 'ended','rejected'])->default('new');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dossiers');
    }
};