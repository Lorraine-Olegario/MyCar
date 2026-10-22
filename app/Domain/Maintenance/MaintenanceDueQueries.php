<?php

namespace App\Domain\Maintenance;

use App\Models\Maintenance;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Próximas trocas por veículo, reutilizável pelo dashboard e pela página do veículo.
 * Só vale a manutenção mais recente de cada categoria por veículo; as anteriores são histórico.
 * São avisos: nenhum valor daqui entra em totais de despesas pagas.
 */
class MaintenanceDueQueries
{
    public function __construct(private readonly MaintenanceDueEvaluator $evaluator) {}

    /**
     * Manutenções com próxima troca vencida ou próxima (status overdue / due_soon).
     *
     * @param  list<int>|null  $vehicleIds  restringe aos veículos informados (null = todos)
     * @return list<array{kind: string, id: int, description: string, vehicle_id: int, vehicle_name: ?string, due_date: ?string, due_odometer: ?int, days_until_due: ?int, km_remaining: ?int, is_overdue: bool, status: string}>
     */
    public function attention(?array $vehicleIds = null, ?CarbonInterface $today = null, bool $includeOk = false): array
    {
        $today = CarbonImmutable::instance($today ?? now())->startOfDay();

        $items = [];

        foreach ($this->current($vehicleIds) as $maintenance) {
            $due = $this->evaluator->evaluate($maintenance, $maintenance->vehicle?->odometer, $today);

            if ($due['status'] === 'none' || ($due['status'] === 'ok' && ! $includeOk)) {
                continue;
            }

            $items[] = [
                'kind' => 'maintenance',
                'id' => $maintenance->id,
                'description' => $maintenance->expense->description,
                'vehicle_id' => $maintenance->vehicle_id,
                'vehicle_name' => $maintenance->vehicle?->name,
                'due_date' => $maintenance->next_due_on?->toDateString(),
                'due_odometer' => $maintenance->next_due_odometer,
                'days_until_due' => $due['days_remaining'],
                'km_remaining' => $due['km_remaining'],
                'is_overdue' => $due['status'] === 'overdue',
                'status' => $due['status'],
            ];
        }

        usort($items, fn (array $a, array $b) => [$a['due_date'] ?? '9999-12-31', $a['id']] <=> [$b['due_date'] ?? '9999-12-31', $b['id']]);

        return $items;
    }

    /**
     * A manutenção mais recente de cada (veículo, categoria), com despesa e veículo carregados.
     *
     * @param  list<int>|null  $vehicleIds
     * @return Collection<int, Maintenance>
     */
    public function current(?array $vehicleIds = null): Collection
    {
        return Maintenance::query()
            ->with(['expense', 'vehicle:id,name,odometer,deleted_at'])
            ->when($vehicleIds !== null, fn ($query) => $query->whereIn('vehicle_id', $vehicleIds))
            ->get()
            ->groupBy(fn (Maintenance $maintenance) => $maintenance->vehicle_id.':'.$maintenance->expense->expense_category_id)
            ->map(fn ($group) => $group->sortByDesc(fn (Maintenance $maintenance) => [$maintenance->expense->occurred_on->timestamp, $maintenance->id])->first())
            ->values();
    }
}
