<?php

namespace App\Domain\Reporting;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Intervalo de datas inclusivo (início e fim contam), em datas de negócio no fuso da aplicação.
 * Um período "ilimitado" cobre todo o histórico e não gera séries sem dados.
 */
final class Period
{
    public const MIN_DATE = '1900-01-01';

    public const MAX_DATE = '2999-12-31';

    private function __construct(
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
        public readonly bool $unbounded = false,
    ) {
        if ($from->gt($to)) {
            throw new InvalidArgumentException('O início do período não pode ser posterior ao fim.');
        }
    }

    public static function between(CarbonInterface|string $from, CarbonInterface|string $to): self
    {
        return new self(
            CarbonImmutable::parse($from)->startOfDay(),
            CarbonImmutable::parse($to)->startOfDay(),
        );
    }

    public static function ofMonth(int $year, int $month): self
    {
        $start = CarbonImmutable::create($year, $month, 1)->startOfDay();

        return new self($start, $start->endOfMonth()->startOfDay());
    }

    public static function ofYear(int $year): self
    {
        return new self(
            CarbonImmutable::create($year, 1, 1)->startOfDay(),
            CarbonImmutable::create($year, 12, 31)->startOfDay(),
        );
    }

    /**
     * Os últimos N meses civis, incluindo o mês de referência.
     */
    public static function lastMonths(int $months, ?CarbonInterface $today = null): self
    {
        $today = CarbonImmutable::instance($today ?? now())->startOfDay();

        return new self(
            $today->startOfMonth()->subMonthsNoOverflow(max(1, $months) - 1),
            $today->endOfMonth()->startOfDay(),
        );
    }

    public static function all(): self
    {
        return new self(
            CarbonImmutable::parse(self::MIN_DATE)->startOfDay(),
            CarbonImmutable::parse(self::MAX_DATE)->startOfDay(),
            true,
        );
    }

    /**
     * Resolve um período a partir dos presets aceitos pela interface. Valores inválidos
     * caem nos "últimos 12 meses"; em período personalizado, datas invertidas são trocadas.
     */
    public static function fromPreset(string $preset, ?string $from = null, ?string $to = null, ?CarbonInterface $today = null): self
    {
        $today = CarbonImmutable::instance($today ?? now())->startOfDay();

        return match ($preset) {
            'this_month' => self::ofMonth($today->year, $today->month),
            'this_year' => self::ofYear($today->year),
            'last_year' => self::ofYear($today->year - 1),
            'all' => self::all(),
            'custom' => self::tryCustom($from, $to) ?? self::lastMonths(12, $today),
            default => self::lastMonths(12, $today),
        };
    }

    public static function tryCustom(?string $from, ?string $to): ?self
    {
        $parse = function (?string $value): ?CarbonImmutable {
            if ($value === null || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
                return null;
            }

            try {
                $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);
            } catch (\Throwable) {
                return null;
            }

            return $date !== false && $date->format('Y-m-d') === $value ? $date : null;
        };

        $start = $parse($from);
        $end = $parse($to);

        if ($start === null || $end === null) {
            return null;
        }

        return $start->gt($end) ? new self($end, $start) : new self($start, $end);
    }

    public function contains(CarbonInterface|string $date): bool
    {
        $date = CarbonImmutable::parse($date)->startOfDay();

        return $date->gte($this->from) && $date->lte($this->to);
    }

    /**
     * Primeiros dias de cada mês que o período toca, em ordem crescente.
     *
     * @return list<CarbonImmutable>
     */
    public function months(): array
    {
        $months = [];

        for ($month = $this->from->startOfMonth(); $month->lte($this->to); $month = $month->addMonthNoOverflow()) {
            $months[] = $month;
        }

        return $months;
    }

    /**
     * Anos que o período toca, em ordem crescente.
     *
     * @return list<int>
     */
    public function years(): array
    {
        return range($this->from->year, $this->to->year);
    }

    public function key(): string
    {
        return $this->unbounded ? 'all' : $this->from->toDateString().'..'.$this->to->toDateString();
    }
}
