<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('fipe_vehicle_type')->nullable();
            $table->string('fipe_brand_id')->nullable();
            $table->string('fipe_model_id')->nullable();
            $table->string('fipe_year_id')->nullable();
            $table->string('fipe_code')->nullable();
            $table->unsignedBigInteger('fipe_value_cents')->nullable();
            $table->string('fipe_reference_month', 7)->nullable();
            $table->timestamp('fipe_consulted_at')->nullable();
        });

        Schema::create('fipe_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('fipe_code');
            $table->unsignedBigInteger('value_cents');
            $table->string('reference_month', 7);
            $table->string('brand');
            $table->string('model');
            $table->unsignedSmallInteger('model_year');
            $table->string('fuel');
            $table->timestamp('consulted_at');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'consulted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fipe_quotes');

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'fipe_vehicle_type', 'fipe_brand_id', 'fipe_model_id', 'fipe_year_id',
                'fipe_code', 'fipe_value_cents', 'fipe_reference_month', 'fipe_consulted_at',
            ]);
        });
    }
};
