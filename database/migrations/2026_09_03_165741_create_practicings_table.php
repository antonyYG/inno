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
        Schema::create('practicings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
        ->constrained()
        ->cascadeOnDelete();
            $table->foreignId('area_id')->constrained()->onDelete('cascade');
            $table->string('discord_id')->unique();
            $table->boolean('status')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practicings');
    }
};
