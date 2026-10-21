<?php

namespace Tests\Unit;

use App\Domain\Fuel\FuelConsumptionCalculator;
use App\Models\Fueling;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class FuelConsumptionCalculatorTest extends TestCase
{
    private static int $nextId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        self::$nextId = 1;
    }

    private function fueling(string $date, int $odometer, float $liters, int $totalCents, bool $full = true): Fueling
    {
        $fueling = new Fueling([
            'vehicle_id' => 1,
            'fueled_on' => $date,
            'odometer' => $odometer,
            'liters' => $liters,
            'price_per_liter' => round($totalCents / 100 / $liters, 3),
            'total_cents' => $totalCents,
            'full_tank' => $full,
        ]);
        $fueling->id = self::$nextId++;

        return $fueling;
    }

    /**
     * @param  list<Fueling>  $items
     * @return Collection<int, Fueling>
     */
    private function collect(array $items): Collection
    {
        return new Collection($items);
    }

    public function test_first_fueling_has_no_consumption(): void
    {
        $first = $this->fueling('2026-01-01', 10_000, 40, 24_000);

        $segments = (new FuelConsumptionCalculator)->segments($this->collect([$first]));

        $this->assertNull($segments[$first->id]['km_per_liter']);
        $this->assertNull($segments[$first->id]['cost_per_km_cents']);
        $this->assertStringContainsString('Primeiro tanque cheio', $segments[$first->id]['reason']);
    }

    public function test_two_consecutive_full_tanks_give_km_per_liter_and_cost_per_km(): void
    {
        $a = $this->fueling('2026-01-01', 10_000, 40, 24_000);
        $b = $this->fueling('2026-01-20', 10_400, 32, 19_200);

        $segment = (new FuelConsumptionCalculator)->segments($this->collect([$a, $b]))[$b->id];

        $this->assertSame(400, $segment['distance_km']);
        $this->assertSame(32.0, $segment['liters']);
        $this->assertSame(12.5, $segment['km_per_liter']);
        $this->assertSame(48.0, $segment['cost_per_km_cents']);
        $this->assertNull($segment['reason']);
    }

    public function test_partial_tank_liters_count_toward_the_next_full_tank(): void
    {
        $a = $this->fueling('2026-01-01', 10_000, 40, 24_000);
        $partial = $this->fueling('2026-01-10', 10_200, 15, 9_000, false);
        $b = $this->fueling('2026-01-20', 10_500, 20, 12_000);

        $segments = (new FuelConsumptionCalculator)->segments($this->collect([$a, $partial, $b]));

        $this->assertNull($segments[$partial->id]['km_per_liter']);
        $this->assertStringContainsString('Tanque parcial', $segments[$partial->id]['reason']);
        $this->assertSame(35.0, $segments[$b->id]['liters']);
        $this->assertSame(500, $segments[$b->id]['distance_km']);
        $this->assertSame(14.29, $segments[$b->id]['km_per_liter']);
        $this->assertSame(42.0, $segments[$b->id]['cost_per_km_cents']);
    }

    public function test_input_order_does_not_matter_and_same_day_uses_odometer(): void
    {
        $a = $this->fueling('2026-01-01', 10_000, 40, 24_000);
        $b = $this->fueling('2026-01-20', 10_400, 40, 24_000);
        $c = $this->fueling('2026-01-20', 10_800, 40, 24_000);

        $segments = (new FuelConsumptionCalculator)->segments($this->collect([$c, $a, $b]));

        $this->assertSame(10.0, $segments[$b->id]['km_per_liter']);
        $this->assertSame(10.0, $segments[$c->id]['km_per_liter']);
    }

    public function test_inconsistent_odometer_returns_null_instead_of_zero_or_negative(): void
    {
        $a = $this->fueling('2026-01-01', 10_000, 40, 24_000);
        $b = $this->fueling('2026-01-20', 10_000, 40, 24_000);

        $segment = (new FuelConsumptionCalculator)->segments($this->collect([$a, $b]))[$b->id];

        $this->assertNull($segment['km_per_liter']);
        $this->assertNull($segment['distance_km']);
        $this->assertStringContainsString('sem avanço', $segment['reason']);
    }

    public function test_summary_without_enough_data_returns_null_consumption(): void
    {
        $calculator = new FuelConsumptionCalculator;

        $empty = $calculator->summary($this->collect([]));
        $this->assertSame(0, $empty['count']);
        $this->assertNull($empty['average_km_per_liter']);
        $this->assertNull($empty['average_price_cents_per_liter']);
        $this->assertNull($empty['cost_per_km_cents']);

        $single = $calculator->summary($this->collect([$this->fueling('2026-01-01', 10_000, 40, 24_000)]));
        $this->assertNull($single['average_km_per_liter']);
        $this->assertSame(600, $single['average_price_cents_per_liter']);
    }

    public function test_summary_weights_price_by_liters_and_consumption_by_distance(): void
    {
        $a = $this->fueling('2026-01-01', 10_000, 40, 24_000);
        $b = $this->fueling('2026-01-20', 10_400, 40, 26_000);
        $c = $this->fueling('2026-02-10', 11_000, 50, 35_000);

        $summary = (new FuelConsumptionCalculator)->summary($this->collect([$a, $b, $c]));

        $this->assertSame(130.0, $summary['total_liters']);
        $this->assertSame(85_000, $summary['total_cents']);
        $this->assertSame(654, $summary['average_price_cents_per_liter']);
        $this->assertSame(1_000, $summary['distance_km']);
        $this->assertSame(11.11, $summary['average_km_per_liter']);
        $this->assertSame(61.0, $summary['cost_per_km_cents']);
    }
}
