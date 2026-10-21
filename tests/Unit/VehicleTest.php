<?php

namespace Tests\Unit;

use App\Enums\FuelType;
use App\Enums\VehicleCategory;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_a_valid_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->assertTrue($vehicle->exists);
        $this->assertInstanceOf(VehicleStatus::class, $vehicle->status);
        $this->assertInstanceOf(VehicleCategory::class, $vehicle->category);
        $this->assertInstanceOf(FuelType::class, $vehicle->fuel);
        $this->assertIsInt($vehicle->odometer);
        $this->assertIsInt($vehicle->manufacture_year);
        $this->assertSame(VehicleStatus::Active, $vehicle->status);
    }

    public function test_dates_and_prices_are_cast(): void
    {
        $vehicle = Vehicle::factory()->create([
            'acquisition_date' => '2024-03-10',
            'purchase_price_cents' => 4_599_000,
        ]);

        $this->assertSame('2024-03-10', $vehicle->acquisition_date->format('Y-m-d'));
        $this->assertSame(4_599_000, $vehicle->purchase_price_cents);
    }

    public function test_full_name_and_plate_accessors(): void
    {
        $vehicle = Vehicle::factory()->create([
            'brand' => 'Chevrolet',
            'model' => 'Onix',
            'version' => '1.0 LT',
            'plate' => 'abc-1234',
        ]);

        $this->assertSame('Chevrolet Onix 1.0 LT', $vehicle->full_name);
        $this->assertSame('ABC1234', $vehicle->plate);
        $this->assertSame('ABC-1234', $vehicle->formatted_plate);

        $serialized = $vehicle->toArray();
        $this->assertSame('Chevrolet Onix 1.0 LT', $serialized['full_name']);
        $this->assertSame('ABC-1234', $serialized['formatted_plate']);
    }

    public function test_plate_is_optional(): void
    {
        $vehicle = Vehicle::factory()->create(['plate' => null]);

        $this->assertNull($vehicle->plate);
        $this->assertNull($vehicle->formatted_plate);
    }

    public function test_scopes_filter_by_status_and_archive(): void
    {
        $active = Vehicle::factory()->create();
        $sold = Vehicle::factory()->sold()->create();
        $archived = Vehicle::factory()->archived()->create();

        $this->assertEqualsCanonicalizing([$active->id], Vehicle::query()->active()->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$sold->id], Vehicle::query()->sold()->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$archived->id], Vehicle::query()->archived()->pluck('id')->all());
        $this->assertSame(2, Vehicle::query()->count());
    }

    public function test_soft_delete_archives_the_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create();

        $vehicle->delete();

        $this->assertSoftDeleted($vehicle);
        $this->assertSame(0, Vehicle::query()->count());
        $this->assertSame(1, Vehicle::query()->archived()->count());
        $this->assertTrue(Vehicle::withTrashed()->findOrFail($vehicle->id)->isArchived());
    }

    public function test_sold_vehicle_is_flagged(): void
    {
        $vehicle = Vehicle::factory()->sold()->create();

        $this->assertTrue($vehicle->isSold());
        $this->assertNotNull($vehicle->sale_date);
        $this->assertNotNull($vehicle->sale_price_cents);
    }

    public function test_photo_url_points_to_the_controlled_route_and_hides_the_private_path(): void
    {
        $vehicle = Vehicle::factory()->create(['photo_path' => null]);

        $this->assertNull($vehicle->photo_url);

        $vehicle->update(['photo_path' => 'vehicles/'.$vehicle->id.'/foto.jpg']);

        $this->assertSame(route('vehicles.photo', $vehicle), $vehicle->photo_url);
        $this->assertArrayNotHasKey('photo_path', $vehicle->toArray());
        $this->assertSame($vehicle->photo_url, $vehicle->toArray()['photo_url']);
    }

    public function test_archived_appended_flag_follows_soft_delete(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->assertFalse($vehicle->archived);
        $this->assertTrue($vehicle->toArray()['archived'] === false);

        $vehicle->delete();

        $archived = Vehicle::withTrashed()->findOrFail($vehicle->id);

        $this->assertTrue($archived->archived);
        $this->assertTrue($archived->isArchived());
    }

    public function test_sale_result_requires_purchase_and_sale_prices(): void
    {
        $withoutSale = Vehicle::factory()->create([
            'purchase_price_cents' => 5_000_000,
            'sale_price_cents' => null,
            'sale_date' => null,
        ]);

        $withoutPurchase = Vehicle::factory()->create([
            'purchase_price_cents' => null,
            'sale_price_cents' => 6_200_000,
            'sale_date' => '2024-05-01',
        ]);

        $profit = Vehicle::factory()->create([
            'purchase_price_cents' => 5_000_000,
            'sale_price_cents' => 6_200_000,
            'sale_date' => '2024-05-01',
        ]);

        $loss = Vehicle::factory()->create([
            'purchase_price_cents' => 5_000_000,
            'sale_price_cents' => 4_500_000,
            'sale_date' => '2024-05-01',
        ]);

        $this->assertNull($withoutSale->sale_result_cents);
        $this->assertNull($withoutPurchase->sale_result_cents);
        $this->assertSame(1_200_000, $profit->sale_result_cents);
        $this->assertSame(-500_000, $loss->sale_result_cents);
    }
}
