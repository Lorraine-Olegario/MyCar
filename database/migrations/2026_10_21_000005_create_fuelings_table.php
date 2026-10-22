<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuelings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->date('fueled_on');
            $table->unsignedInteger('odometer');
            $table->decimal('liters', 8, 3);
            $table->decimal('price_per_liter', 8, 3);
            $table->unsignedBigInteger('total_cents');
            $table->string('fuel_type');
            $table->string('station')->nullable();
            $table->boolean('full_tank')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'fueled_on']);
            $table->index(['vehicle_id', 'odometer']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuelings');
    }
};
