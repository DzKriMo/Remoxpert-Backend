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
            // Add expert_id column after expert_nom
            $table->unsignedBigInteger('expert_id')->nullable()->after('expert_nom');
            
            // Add foreign key constraint
            $table->foreign('expert_id')->references('id')->on('admins')->onDelete('set null');
            
            // Add index for better performance
            $table->index('expert_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dossiers', function (Blueprint $table) {
            // Drop foreign key constraint
            $table->dropForeign(['expert_id']);
            
            // Drop the column
            $table->dropColumn('expert_id');
        });
    }
};