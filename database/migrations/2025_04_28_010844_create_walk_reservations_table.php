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
        Schema::create('walk_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('dog_id')->constrained('dogs')->onDelete('cascade');
            $table->foreignId('walker_id')->nullable()->constrained('walkers')->onDelete('set null');
            $table->date('reservation_date');
            $table->time('reservation_time');
            $table->enum('status', ['pending', 'confirmed', 'completed', 'in_progress','cancelled'])->default('pending');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('walk_reservations');
    }
};
