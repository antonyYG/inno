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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

        $table->foreignId('practicing_id')->constrained()->cascadeOnDelete();

        $table->date('date');

        $table->dateTime('check_in')->nullable();
        $table->dateTime('check_out')->nullable();

        $table->dateTime('last_join')->nullable();
        $table->dateTime('last_leave')->nullable();

        $table->integer('worked_minutes')->default(0);
        $table->enum('shift', ['mañana', 'tarde']);
        $table->enum('status', [
            'pending',
            'active',
            'paused',
            'finished'
        ])->default('pending');

        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
