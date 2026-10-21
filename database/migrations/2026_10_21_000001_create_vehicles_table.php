<?php

use App\Enums\FuelType;
use App\Enums\VehicleCategory;
use App\Enums\VehicleStatus;
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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('brand');
            $table->string('model');
            $table->string('version')->nullable();
            $table->unsignedSmallInteger('manufacture_year');
            $table->unsignedSmallInteger('model_year');
            $table->string('category')->default(VehicleCategory::Car->value);
            $table->string('plate')->nullable()->unique();
            $table->string('color')->nullable();
            $table->string('fuel')->default(FuelType::Flex->value);
            $table->unsignedInteger('odometer')->default(0);
            $table->date('acquisition_date');
            $table->unsignedInteger('acquisition_odometer')->nullable();
            $table->unsignedBigInteger('purchase_price_cents')->nullable();
            $table->date('sale_date')->nullable();
            $table->unsignedBigInteger('sale_price_cents')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default(VehicleStatus::Active->value);
            $table->date('tracking_start_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('category');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
