<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Categorias iniciais, na ordem do enunciado. Idempotente: não duplica nem
     * sobrescreve ajustes feitos pelo usuário (cor, ordem, ativo).
     *
     * @var array<string, string>
     */
    public const CATEGORIES = [
        'Combustível' => '#f97316',
        'Troca de óleo e filtros' => '#eab308',
        'Manutenção preventiva' => '#22c55e',
        'Manutenção corretiva' => '#ef4444',
        'Pneus e alinhamento' => '#64748b',
        'Freios' => '#dc2626',
        'Bateria e sistema elétrico' => '#facc15',
        'Motor e transmissão' => '#7c3aed',
        'Documentação e licenciamento' => '#0ea5e9',
        'IPVA' => '#2563eb',
        'Seguro' => '#14b8a6',
        'Estacionamento' => '#8b5cf6',
        'Pedágios' => '#06b6d4',
        'Lavagem e estética' => '#38bdf8',
        'Multas' => '#be123c',
        'Acessórios e melhorias' => '#d946ef',
        'Outros gastos' => '#78716c',
    ];

    public function run(): void
    {
        // O DatabaseSeeder desliga os eventos do model; por isso o slug é informado aqui.
        $order = 0;

        foreach (self::CATEGORIES as $name => $color) {
            $order++;

            ExpenseCategory::query()->firstOrCreate(
                ['name' => $name],
                [
                    'slug' => ExpenseCategory::uniqueSlug($name),
                    'color' => $color,
                    'active' => true,
                    'sort_order' => $order,
                ],
            );
        }
    }
}
