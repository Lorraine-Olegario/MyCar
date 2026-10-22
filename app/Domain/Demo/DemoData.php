<?php

namespace App\Domain\Demo;

use App\Domain\Fuel\FuelingRecorder;
use App\Domain\Maintenance\MaintenanceService;
use App\Domain\Recurring\RecurringExpenseService;
use App\Enums\VehicleStatus;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FipeQuote;
use App\Models\Fueling;
use App\Models\Maintenance;
use App\Models\RecurringExpense;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Database\Seeders\ExpenseCategorySeeder;
use Illuminate\Support\Facades\DB;

/**
 * Dados de demonstração, claramente separados dos reais: todo veículo demo tem o prefixo
 * "[DEMO]" no nome e uma placa reservada. A criação é idempotente (por placa) e a remoção
 * apaga somente o que pertence a esses veículos.
 */
class DemoData
{
    public const PREFIX = '[DEMO] ';

    public const ACTIVE_PLATE = 'DEM0A01';

    public const SOLD_PLATE = 'DEM0B02';

    public function __construct(
        private readonly RecurringExpenseService $recurring,
        private readonly FuelingRecorder $fuelings,
        private readonly MaintenanceService $maintenances,
    ) {}

    /**
     * @return array{created: list<string>, skipped: list<string>}
     */
    public function seed(): array
    {
        app(ExpenseCategorySeeder::class)->run();

        $today = CarbonImmutable::now()->startOfDay();
        $result = ['created' => [], 'skipped' => []];

        foreach ([self::ACTIVE_PLATE => 'seedActive', self::SOLD_PLATE => 'seedSold'] as $plate => $method) {
            if (Vehicle::withTrashed()->where('plate', $plate)->exists()) {
                $result['skipped'][] = $plate;

                continue;
            }

            DB::transaction(fn () => $this->{$method}($today));
            $result['created'][] = $plate;
        }

        return $result;
    }

    /**
     * Remove os veículos de demonstração e tudo que depende deles.
     */
    public function clear(): int
    {
        $vehicles = Vehicle::withTrashed()->whereIn('plate', [self::ACTIVE_PLATE, self::SOLD_PLATE])->where('name', 'like', self::PREFIX.'%')->get();

        DB::transaction(function () use ($vehicles): void {
            foreach ($vehicles as $vehicle) {
                Maintenance::where('vehicle_id', $vehicle->id)->delete();
                Expense::where('vehicle_id', $vehicle->id)->get()->each->delete();
                RecurringExpense::where('vehicle_id', $vehicle->id)->delete();
                Fueling::where('vehicle_id', $vehicle->id)->delete();
                FipeQuote::where('vehicle_id', $vehicle->id)->delete();
                $vehicle->forceDelete();
            }
        });

        return $vehicles->count();
    }

    private function category(string $slug): int
    {
        return ExpenseCategory::where('slug', $slug)->value('id');
    }

    /**
     * @param  list<array{0: string, 1: string, 2: int, 3: string}>  $items  [data, slug, centavos, descrição]
     */
    private function expenses(Vehicle $vehicle, array $items): void
    {
        foreach ($items as [$date, $slug, $cents, $description]) {
            Expense::create([
                'vehicle_id' => $vehicle->id,
                'expense_category_id' => $this->category($slug),
                'description' => $description,
                'amount_cents' => $cents,
                'occurred_on' => $date,
            ]);
        }
    }

    private function seedActive(CarbonImmutable $today): void
    {
        $vehicle = Vehicle::create([
            'name' => self::PREFIX.'Chevrolet Onix 1.0 LT',
            'brand' => 'Chevrolet', 'model' => 'Onix', 'version' => '1.0 LT',
            'manufacture_year' => 2022, 'model_year' => 2023, 'category' => 'car', 'plate' => self::ACTIVE_PLATE,
            'color' => 'Prata', 'fuel' => 'flex', 'odometer' => 52_000,
            'acquisition_date' => $today->subMonths(30)->toDateString(), 'acquisition_odometer' => 12_000,
            'purchase_price_cents' => 7_500_000, 'status' => VehicleStatus::Active,
            // Acompanhamento começou depois da compra: gera aviso de histórico incompleto nos períodos antigos.
            'tracking_start_date' => $today->subMonths(14)->toDateString(),
            'notes' => 'Veículo de demonstração. Remova com `php artisan mycar:demo clear`.',
        ]);

        $this->expenses($vehicle, [
            [$today->subMonths(28)->toDateString(), 'documentacao-e-licenciamento', 35_000, 'Transferência e vistoria'],
            [$today->subMonths(20)->toDateString(), 'pneus-e-alinhamento', 220_000, 'Jogo de pneus'],
            [$today->subMonths(13)->toDateString(), 'seguro', 360_000, 'Seguro anual'],
            [$today->subMonths(9)->toDateString(), 'ipva', 190_000, 'IPVA'],
            [$today->subMonths(6)->toDateString(), 'freios', 48_000, 'Pastilhas dianteiras'],
            [$today->subMonths(3)->toDateString(), 'lavagem-e-estetica', 9_000, 'Lavagem completa'],
            [$today->subDays(25)->toDateString(), 'estacionamento', 4_500, 'Estacionamento no centro'],
        ]);

        foreach ([[70, 51_000, 40, '6.000'], [55, 51_450, 35, '6.100'], [40, 51_900, 38, '6.050'], [25, 52_350, 36, '6.000'], [10, 52_800, 37, '6.100']] as [$daysAgo, $odometer, $liters, $price]) {
            $this->fuelings->store([
                'vehicle_id' => $vehicle->id, 'fueled_on' => $today->subDays($daysAgo)->toDateString(), 'odometer' => $odometer,
                'liters' => (string) $liters, 'price_per_liter' => $price, 'fuel_type' => 'flex', 'full_tank' => true,
            ]);
        }

        $this->maintenances->create([
            'vehicle_id' => $vehicle->id, 'expense_category_id' => $this->category('troca-de-oleo-e-filtros'),
            'description' => 'Troca de óleo e filtros', 'amount_cents' => 38_000, 'occurred_on' => $today->subMonths(4)->toDateString(),
            'type' => 'preventive', 'odometer' => 50_000, 'next_due_on' => $today->addDays(20)->toDateString(), 'next_due_odometer' => 60_000,
        ]);

        $insurance = $this->recurring->create([
            'vehicle_id' => $vehicle->id, 'expense_category_id' => $this->category('seguro'), 'description' => 'Seguro anual',
            'expected_amount_cents' => 380_000, 'frequency' => 'annual', 'start_date' => $today->startOfMonth()->toDateString(), 'due_day' => 15,
        ]);
        $this->recurring->confirmPayment($insurance->occurrences()->orderBy('competence_date')->first(), $today->toDateString(), 372_000);

        $this->recurring->create([
            'vehicle_id' => $vehicle->id, 'expense_category_id' => $this->category('estacionamento'), 'description' => 'Estacionamento mensal',
            'expected_amount_cents' => 15_000, 'frequency' => 'monthly', 'start_date' => $today->startOfMonth()->toDateString(), 'due_day' => 25,
        ]);

        $this->fipe($vehicle, [[$today->subDays(40), 6_700_000, $today->subDays(40)->format('Y-m')], [$today->subDays(5), 6_550_000, $today->format('Y-m')]]);
    }

    private function seedSold(CarbonImmutable $today): void
    {
        $vehicle = Vehicle::create([
            'name' => self::PREFIX.'Honda Fit 1.5 LX',
            'brand' => 'Honda', 'model' => 'Fit', 'version' => '1.5 LX',
            'manufacture_year' => 2016, 'model_year' => 2016, 'category' => 'car', 'plate' => self::SOLD_PLATE,
            'color' => 'Branco', 'fuel' => 'flex', 'odometer' => 98_000,
            'acquisition_date' => $today->subYears(5)->toDateString(), 'acquisition_odometer' => 40_000,
            'purchase_price_cents' => 4_800_000, 'status' => VehicleStatus::Sold,
            'sale_date' => $today->subMonths(10)->toDateString(), 'sale_price_cents' => 4_100_000,
            'tracking_start_date' => $today->subYears(5)->toDateString(),
            'notes' => 'Veículo de demonstração (vendido). Remova com `php artisan mycar:demo clear`.',
        ]);

        $this->expenses($vehicle, [
            [$today->subYears(4)->toDateString(), 'seguro', 280_000, 'Seguro anual'],
            [$today->subYears(3)->toDateString(), 'manutencao-corretiva', 160_000, 'Embreagem'],
            [$today->subYears(2)->toDateString(), 'pneus-e-alinhamento', 190_000, 'Jogo de pneus'],
            [$today->subYears(1)->subMonths(2)->toDateString(), 'ipva', 120_000, 'IPVA'],
            [$today->subMonths(11)->toDateString(), 'manutencao-preventiva', 70_000, 'Revisão antes da venda'],
        ]);
    }

    /**
     * @param  list<array{0: CarbonImmutable, 1: int, 2: string}>  $quotes  [consulta, centavos, mês de referência], do mais antigo ao mais novo
     */
    private function fipe(Vehicle $vehicle, array $quotes): void
    {
        foreach ($quotes as [$consultedAt, $cents, $reference]) {
            $vehicle->fipeQuotes()->create([
                'fipe_code' => '004001-0', 'value_cents' => $cents, 'reference_month' => $reference,
                'brand' => 'Chevrolet', 'model' => 'Onix 1.0 LT (demonstração)', 'model_year' => 2023, 'fuel' => 'Flex',
                'consulted_at' => $consultedAt, 'payload' => ['demo' => true],
            ]);

            $vehicle->forceFill([
                'fipe_code' => '004001-0', 'fipe_value_cents' => $cents, 'fipe_reference_month' => $reference, 'fipe_consulted_at' => $consultedAt,
            ])->save();
        }
    }
}
