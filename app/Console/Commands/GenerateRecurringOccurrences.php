<?php

namespace App\Console\Commands;

use App\Domain\Recurring\RecurringExpenseService;
use App\Models\RecurringExpense;
use Illuminate\Console\Command;

class GenerateRecurringOccurrences extends Command
{
    protected $signature = 'recurring:generate {--months=12 : Quantidade de meses à frente}';

    protected $description = 'Gera (de forma idempotente) as ocorrências previstas das despesas recorrentes';

    public function handle(RecurringExpenseService $service): int
    {
        $months = max(1, (int) $this->option('months'));
        $recurrences = RecurringExpense::query()->active()->get();

        foreach ($recurrences as $recurring) {
            $service->sync($recurring, months: $months);
        }

        $this->info("Ocorrências sincronizadas para {$recurrences->count()} despesa(s) recorrente(s), {$months} mês(es) à frente.");

        return self::SUCCESS;
    }
}
