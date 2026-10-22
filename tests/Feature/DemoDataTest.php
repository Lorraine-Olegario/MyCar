<?php

namespace Tests\Feature;

use App\Domain\Demo\DemoData;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FipeQuote;
use App\Models\Fueling;
use App\Models\Maintenance;
use App\Models\RecurringExpense;
use App\Models\Vehicle;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DemoDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Carbon::setTestNow('2026-06-15 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @return array<string, int>
     */
    private function counts(): array
    {
        return [
            'vehicles' => Vehicle::withTrashed()->count(),
            'expenses' => Expense::count(),
            'fuelings' => Fueling::count(),
            'recurring' => RecurringExpense::count(),
            'maintenances' => Maintenance::count(),
            'fipe' => FipeQuote::count(),
        ];
    }

    public function test_plain_seeding_never_creates_demo_data(): void
    {
        config(['mycar.demo' => false]);

        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoSeeder::class);

        $this->assertSame(0, Vehicle::count());
        $this->assertSame(17, ExpenseCategory::count());
    }

    public function test_seeding_with_the_flag_creates_a_complete_and_labelled_demo(): void
    {
        config(['mycar.demo' => true]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(2, Vehicle::count());
        $this->assertSame(['[DEMO] Chevrolet Onix 1.0 LT', '[DEMO] Honda Fit 1.5 LX'], Vehicle::orderBy('name')->pluck('name')->all());
        $this->assertSame(1, Vehicle::where('status', 'sold')->count());
        $this->assertGreaterThan(8, Expense::count());
        $this->assertSame(5, Fueling::count());
        $this->assertSame(2, RecurringExpense::count());
        $this->assertSame(1, Maintenance::count());
        $this->assertSame(2, FipeQuote::count());
        $this->assertSame(1, Expense::whereNotNull('recurring_expense_id')->count());
    }

    public function test_demo_creation_is_idempotent_by_plate(): void
    {
        $demo = app(DemoData::class);

        $first = $demo->seed();
        $counts = $this->counts();
        $second = $demo->seed();

        $this->assertSame(['DEM0A01', 'DEM0B02'], $first['created']);
        $this->assertSame([], $second['created']);
        $this->assertSame(['DEM0A01', 'DEM0B02'], $second['skipped']);
        $this->assertSame($counts, $this->counts());

        config(['mycar.demo' => true]);
        $this->seed(DemoSeeder::class);
        $this->assertSame($counts, $this->counts());
    }

    public function test_demo_data_feeds_the_screens_with_numbers_that_make_sense(): void
    {
        app(DemoData::class)->seed();

        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('kpis.active_vehicles', 1)
            ->where('vehicle.plate', 'DEM0A01')
            ->where('fipe.with_quote', 1)
            ->where('indicators.average_consumption', fn ($value) => $value > 0)
            ->where('indicators.next_maintenance.status', 'due_soon')
            ->where('upcoming', fn ($items) => count($items) >= 2));

        $this->get('/reports/sale-result')->assertInertia(fn (Assert $page) => $page
            ->has('rows', 1)
            ->where('rows.0.sale_result_cents', fn ($value) => is_int($value)));

        $vehicle = Vehicle::where('plate', 'DEM0A01')->first();
        $this->get("/vehicles/{$vehicle->id}?year=2025")->assertInertia(fn (Assert $page) => $page
            ->where('financial.completeness.is_complete', false)
            ->where('fipe.snapshot.value_cents', 6_550_000)
            ->where('fipe.variation.change_cents', -150_000));
    }

    public function test_clear_removes_only_demo_vehicles_and_their_data(): void
    {
        $real = Vehicle::factory()->create(['name' => 'Meu carro de verdade', 'plate' => 'REA1L23']);
        $category = ExpenseCategory::factory()->create();
        Expense::factory()->create(['vehicle_id' => $real->id, 'expense_category_id' => $category->id]);
        Fueling::factory()->create(['vehicle_id' => $real->id]);
        $before = $this->counts();

        $demo = app(DemoData::class);
        $demo->seed();
        $this->assertGreaterThan($before['vehicles'], $this->counts()['vehicles']);

        $this->assertSame(2, $demo->clear());

        $this->assertSame($before, $this->counts());
        $this->assertNotNull(Vehicle::find($real->id));
        $this->assertSame(0, app(DemoData::class)->clear());
    }

    public function test_command_requires_a_valid_action_and_works_end_to_end(): void
    {
        $this->artisan('mycar:demo', ['action' => 'oops', '--force' => true])->assertExitCode(2);

        $this->artisan('mycar:demo', ['action' => 'seed', '--force' => true])->assertSuccessful();
        $this->assertSame(2, Vehicle::count());

        $this->artisan('mycar:demo', ['action' => 'seed', '--force' => true])->assertSuccessful();
        $this->assertSame(2, Vehicle::count());

        $this->artisan('mycar:demo', ['action' => 'clear', '--force' => true])->assertSuccessful();
        $this->assertSame(0, Vehicle::count());
    }

    public function test_command_asks_for_confirmation_and_can_be_declined(): void
    {
        $this->artisan('mycar:demo', ['action' => 'seed'])
            ->expectsConfirmation('Criar os veículos e lançamentos de demonstração?', 'no')
            ->assertSuccessful();

        $this->assertSame(0, Vehicle::count());
    }

    public function test_command_refuses_to_run_in_production_without_force(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('mycar:demo', ['action' => 'seed'])->assertFailed();
        $this->assertSame(0, Vehicle::count());
    }
}
