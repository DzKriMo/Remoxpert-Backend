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
        Schema::table('dossiers', function (Blueprint $table) {
            $table->decimal('note_honoraire_montant', 10, 2)->nullable()->after('admin_comment');
            $table->boolean('seenbyadmin')->default(false)->after('note_honoraire_montant');
            $table->boolean('adminchangeseen')->default(true)->after('seenbyadmin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dossiers', function (Blueprint $table) {
            $table->dropColumn(['note_honoraire_montant', 'seenbyadmin', 'adminchangeseen']);
        });
    }
};