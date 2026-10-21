<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $table->string('description');
            $table->unsignedBigInteger('amount_cents');
            $table->date('occurred_on')->index();
            $table->unsignedInteger('odometer')->nullable();
            $table->string('vendor')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('receipt_path')->nullable();
            $table->text('notes')->nullable();
            // A tabela de recorrências chega na tarefa 15, que adiciona a FK.
            $table->unsignedBigInteger('recurring_expense_id')->nullable()->index();
            $table->timestamps();

            $table->index(['vehicle_id', 'occurred_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
