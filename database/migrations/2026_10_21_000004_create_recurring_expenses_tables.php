<?php

use App\Enums\OccurrenceStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $table->string('description');
            $table->unsignedBigInteger('expected_amount_cents');
            $table->string('frequency');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedTinyInteger('due_day');
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('recurring_expense_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurring_expense_id')->constrained()->cascadeOnDelete();
            $table->date('competence_date');
            $table->date('due_date');
            $table->unsignedBigInteger('expected_amount_cents');
            $table->string('status')->default(OccurrenceStatus::Forecast->value);
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('paid_amount_cents')->nullable();
            $table->foreignId('expense_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['recurring_expense_id', 'competence_date']);
            $table->index(['status', 'due_date']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreign('recurring_expense_id')->references('id')->on('recurring_expenses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['recurring_expense_id']);
        });

        Schema::dropIfExists('recurring_expense_occurrences');
        Schema::dropIfExists('recurring_expenses');
    }
};
