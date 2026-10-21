<?php

namespace App\Domain\Maintenance;

use App\Models\Maintenance;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Avalia a "próxima troca" de uma manutenção por data e/ou odômetro.
 *
 * Vencimento é um aviso, não uma despesa: nada aqui cria ou soma valores pagos.
 */
class MaintenanceDueEvaluator
{
    public const SOON_DAYS = 30;

    public const SOON_KM = 1_000;

    /**
     * @return array{status: string, days_remaining: ?int, km_remaining: ?int, reason: ?string}
     *                                                                                          status: none (sem previsão), ok, due_soon ou overdue
     */
    public function evaluate(Maintenance $maintenance, ?int $currentOdometer, ?CarbonInterface $today = null): array
    {
        $today = CarbonImmutable::instance($today ?? now())->startOfDay();

        $days = $maintenance->next_due_on
            ? (int) $today->diffInDays($maintenance->next_due_on->startOfDay(), false)
            : null;
        $km = $maintenance->next_due_odometer !== null && $currentOdometer !== null
            ? $maintenance->next_due_odometer - $currentOdometer
            : null;

        if ($days === null && $km === null) {
            return ['status' => 'none', 'days_remaining' => null, 'km_remaining' => null, 'reason' => null];
        }

        if (($days !== null && $days < 0) || ($km !== null && $km < 0)) {
            $reason = ($days !== null && $days < 0) ? 'date' : 'odometer';

            return ['status' => 'overdue', 'days_remaining' => $days, 'km_remaining' => $km, 'reason' => $reason];
        }

        if (($days !== null && $days <= self::SOON_DAYS) || ($km !== null && $km <= self::SOON_KM)) {
            $reason = ($days !== null && $days <= self::SOON_DAYS) ? 'date' : 'odometer';

            return ['status' => 'due_soon', 'days_remaining' => $days, 'km_remaining' => $km, 'reason' => $reason];
        }

        return ['status' => 'ok', 'days_remaining' => $days, 'km_remaining' => $km, 'reason' => null];
    }
}
