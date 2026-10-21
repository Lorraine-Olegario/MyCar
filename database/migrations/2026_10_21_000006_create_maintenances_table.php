<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('expense_id')->unique()->constrained()->restrictOnDelete();
            $table->string('type');
            $table->unsignedInteger('odometer')->nullable();
            $table->date('next_due_on')->nullable();
            $table->unsignedInteger('next_due_odometer')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'next_due_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
