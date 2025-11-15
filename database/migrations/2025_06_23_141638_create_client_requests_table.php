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
        Schema::create('client_requests', function (Blueprint $table) {
            $table->id();
            $table->string('client_name');
            $table->string('email')->unique();
            $table->string('phone_number');
            $table->string('company_name');
            $table->string('company_code');
            $table->enum('status', ['pending', 'created', 'rejected'])->default('pending');
            $table->text('admin_comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_requests');
    }
};
