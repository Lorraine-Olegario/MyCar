<?php

namespace App\Domain\Recurring;

use App\Models\RecurringExpense;
use App\Models\RecurringExpenseOccurrence;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Calcula e grava, de forma idempotente, as ocorrências previstas de uma recorrência.
 *
 * A competência é sempre o primeiro dia do mês; o vencimento usa `due_day`,
 * ajustado para o último dia em meses mais curtos (ex.: 31 → 28/29 em fevereiro).
 */
class OccurrenceGenerator
{
    /**
     * Gera as ocorrências ainda inexistentes entre o mês corrente (ou o início da
     * recorrência, se posterior) e o horizonte informado. Nunca altera as existentes.
     *
     * @return int quantidade de ocorrências criadas
     */
    public function generate(RecurringExpense $recurring, CarbonInterface $horizon, ?CarbonInterface $today = null): int
    {
        if (! $recurring->active) {
            return 0;
        }

        $today = CarbonImmutable::instance($today ?? now())->startOfMonth();
        $created = 0;

        foreach ($this->competences($recurring, $today, CarbonImmutable::instance($horizon)->startOfMonth()) as $competence) {
            $occurrence = RecurringExpenseOccurrence::query()->firstOrCreate(
                [
                    'recurring_expense_id' => $recurring->getKey(),
                    'competence_date' => $competence->toDateString(),
                ],
                [
                    'due_date' => $this->dueDate($competence, $recurring->due_day)->toDateString(),
                    'expected_amount_cents' => $recurring->expected_amount_cents,
                ],
            );

            if ($occurrence->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * Primeiros dias dos meses em que a recorrência deve ocorrer no intervalo [from, to].
     *
     * @return list<CarbonImmutable>
     */
    public function competences(RecurringExpense $recurring, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $anchor = CarbonImmutable::instance($recurring->start_date)->startOfMonth();
        $end = $recurring->end_date ? CarbonImmutable::instance($recurring->end_date)->startOfMonth() : null;
        $interval = $recurring->frequency->months();

        $competences = [];

        for ($month = $anchor; $month->lte($to); $month = $month->addMonthsNoOverflow($interval)) {
            if ($end !== null && $month->gt($end)) {
                break;
            }

            if ($month->gte($from)) {
                $competences[] = $month;
            }
        }

        return $competences;
    }

    public function dueDate(CarbonInterface $competence, int $dueDay): CarbonImmutable
    {
        $month = CarbonImmutable::instance($competence)->startOfMonth();

        return $month->day(min($dueDay, $month->daysInMonth));
    }
}
