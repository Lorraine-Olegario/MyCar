<?php

namespace App\Domain\Recurring;

use App\Enums\OccurrenceStatus;
use App\Models\Expense;
use App\Models\RecurringExpense;
use App\Models\RecurringExpenseOccurrence;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Regras de negócio das despesas recorrentes: criação/edição com sincronização das
 * previsões futuras, confirmação de pagamento e cancelamento.
 */
class RecurringExpenseService
{
    public const DEFAULT_MONTHS = 12;

    public function __construct(private readonly OccurrenceGenerator $generator) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): RecurringExpense
    {
        return DB::transaction(function () use ($data): RecurringExpense {
            $recurring = RecurringExpense::create($data);
            $this->sync($recurring);

            return $recurring;
        });
    }

    /**
     * Edita o modelo; só as ocorrências previstas futuras são recalculadas.
     * Ocorrências pagas ou canceladas permanecem como histórico.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(RecurringExpense $recurring, array $data): RecurringExpense
    {
        return DB::transaction(function () use ($recurring, $data): RecurringExpense {
            $recurring->update($data);
            $this->sync($recurring);

            return $recurring;
        });
    }

    /**
     * Alinha as previsões futuras ao modelo atual: remove as que deixaram de caber
     * (frequência, fim, inatividade), atualiza vencimento/valor das demais e gera as faltantes.
     */
    public function sync(RecurringExpense $recurring, ?CarbonInterface $today = null, int $months = self::DEFAULT_MONTHS): void
    {
        $today = CarbonImmutable::instance($today ?? now());
        $currentMonth = $today->startOfMonth();
        $horizon = $today->addMonthsNoOverflow($months);

        $future = $recurring->occurrences()
            ->forecast()
            ->whereDate('competence_date', '>=', $currentMonth->toDateString())
            ->get();

        // Reduzir o horizonte (ex.: --months=6) não apaga previsões válidas já geradas.
        $latest = $future->max('competence_date');
        $upper = $latest !== null && $latest->gt($horizon) ? CarbonImmutable::instance($latest) : $horizon;

        $valid = $recurring->active
            ? array_map(
                fn (CarbonImmutable $competence) => $competence->toDateString(),
                $this->generator->competences($recurring, $currentMonth, $upper->startOfMonth()),
            )
            : [];

        foreach ($future as $occurrence) {
            $competence = $occurrence->competence_date->toDateString();

            if (! in_array($competence, $valid, true)) {
                $occurrence->delete();

                continue;
            }

            $occurrence->update([
                'due_date' => $this->generator->dueDate($occurrence->competence_date, $recurring->due_day)->toDateString(),
                'expected_amount_cents' => $recurring->expected_amount_cents,
            ]);
        }

        $this->generator->generate($recurring, $horizon, $today);
    }

    /**
     * Confirma o pagamento: cria a despesa real e marca a ocorrência como paga, em
     * uma única transação. Confirmar de novo (ou uma previsão cancelada) é bloqueado.
     *
     * @throws DomainException
     */
    public function confirmPayment(RecurringExpenseOccurrence $occurrence, CarbonInterface|string $paidOn, int $paidAmountCents): Expense
    {
        if ($paidAmountCents < 1) {
            throw new DomainException('O valor pago deve ser maior que zero.');
        }

        return DB::transaction(function () use ($occurrence, $paidOn, $paidAmountCents): Expense {
            $locked = RecurringExpenseOccurrence::query()->lockForUpdate()->findOrFail($occurrence->getKey());

            if ($locked->status === OccurrenceStatus::Paid) {
                throw new DomainException('Esta ocorrência já teve o pagamento confirmado.');
            }

            if ($locked->status === OccurrenceStatus::Canceled) {
                throw new DomainException('Esta ocorrência foi cancelada e não pode ser paga.');
            }

            $recurring = $locked->recurringExpense;
            $date = CarbonImmutable::parse($paidOn)->toDateString();

            $expense = Expense::create([
                'vehicle_id' => $recurring->vehicle_id,
                'expense_category_id' => $recurring->expense_category_id,
                'description' => $recurring->description,
                'amount_cents' => $paidAmountCents,
                'occurred_on' => $date,
                'recurring_expense_id' => $recurring->getKey(),
            ]);

            $locked->update([
                'status' => OccurrenceStatus::Paid,
                'paid_at' => $date,
                'paid_amount_cents' => $paidAmountCents,
                'expense_id' => $expense->getKey(),
            ]);

            $occurrence->setRawAttributes($locked->getAttributes(), true);

            return $expense;
        });
    }

    /**
     * Cancela uma previsão ainda não paga.
     *
     * @throws DomainException
     */
    public function cancel(RecurringExpenseOccurrence $occurrence): void
    {
        DB::transaction(function () use ($occurrence): void {
            $locked = RecurringExpenseOccurrence::query()->lockForUpdate()->findOrFail($occurrence->getKey());

            if ($locked->status !== OccurrenceStatus::Forecast) {
                throw new DomainException('Somente previsões em aberto podem ser canceladas.');
            }

            $locked->update(['status' => OccurrenceStatus::Canceled]);
            $occurrence->setRawAttributes($locked->getAttributes(), true);
        });
    }

    /**
     * Próximos vencimentos (previsões em aberto), incluindo as já vencidas, do mais
     * antigo para o mais novo. Reutilizável pelo dashboard e pela página do veículo.
     *
     * @return list<array<string, mixed>>
     */
    public function upcoming(int $withinDays = 90, ?int $vehicleId = null, int $limit = 10, ?CarbonInterface $today = null): array
    {
        $today = CarbonImmutable::instance($today ?? now())->startOfDay();

        return RecurringExpenseOccurrence::query()
            ->forecast()
            ->whereDate('due_date', '<=', $today->addDays($withinDays)->toDateString())
            ->whereHas('recurringExpense', fn ($query) => $query
                ->where('active', true)
                ->when($vehicleId, fn ($query) => $query->where('vehicle_id', $vehicleId)))
            ->with('recurringExpense.vehicle:id,name,brand,model,version,deleted_at')
            ->orderBy('due_date')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(fn (RecurringExpenseOccurrence $occurrence) => [
                'id' => $occurrence->id,
                'description' => $occurrence->recurringExpense->description,
                'vehicle_id' => $occurrence->recurringExpense->vehicle_id,
                'vehicle_name' => $occurrence->recurringExpense->vehicle?->name,
                'due_date' => $occurrence->due_date->toDateString(),
                'expected_amount_cents' => $occurrence->expected_amount_cents,
                'days_until_due' => (int) $today->diffInDays($occurrence->due_date->startOfDay(), false),
                'is_overdue' => $occurrence->due_date->startOfDay()->lt($today),
            ])
            ->values()
            ->all();
    }

    /**
     * Resumo do mês, com os dois valores sempre separados: a estimativa proporcional
     * (ex.: anual ÷ 12) e o que foi efetivamente pago no mês. Previsões não entram no pago.
     *
     * @return array{estimated_cents: int, paid_cents: int, forecast_cents: int}
     */
    public function monthSummary(CarbonInterface $month, ?int $vehicleId = null): array
    {
        $start = CarbonImmutable::instance($month)->startOfMonth();
        $end = $start->endOfMonth();

        $estimated = RecurringExpense::query()
            ->active()
            ->when($vehicleId, fn ($query) => $query->where('vehicle_id', $vehicleId))
            ->whereDate('start_date', '<=', $end->toDateString())
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $start->toDateString()))
            ->get()
            ->sum('monthly_estimate_cents');

        $occurrences = RecurringExpenseOccurrence::query()
            ->when($vehicleId, fn ($query) => $query->whereHas('recurringExpense', fn ($q) => $q->where('vehicle_id', $vehicleId)));

        return [
            'estimated_cents' => (int) $estimated,
            'paid_cents' => (int) (clone $occurrences)
                ->where('status', OccurrenceStatus::Paid)
                ->whereBetween('paid_at', [$start->toDateString(), $end->toDateString()])
                ->sum('paid_amount_cents'),
            'forecast_cents' => (int) (clone $occurrences)
                ->forecast()
                ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
                ->sum('expected_amount_cents'),
        ];
    }
}
