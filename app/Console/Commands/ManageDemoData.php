<?php

namespace App\Console\Commands;

use App\Domain\Demo\DemoData;
use Illuminate\Console\Command;

class ManageDemoData extends Command
{
    protected $signature = 'mycar:demo {action : seed (cria) ou clear (remove)} {--force : confirma sem perguntar (e permite em produção)}';

    protected $description = 'Cria ou remove os dados de demonstração ([DEMO]), sem tocar nos dados reais';

    public function handle(DemoData $demo): int
    {
        $action = (string) $this->argument('action');

        if (! in_array($action, ['seed', 'clear'], true)) {
            $this->error('Use "seed" ou "clear".');

            return self::INVALID;
        }

        if (app()->isProduction() && ! $this->option('force')) {
            $this->error('Ambiente de produção: use --force para confirmar.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm($action === 'seed' ? 'Criar os veículos e lançamentos de demonstração?' : 'Remover os veículos de demonstração e seus lançamentos?', true)) {
            return self::SUCCESS;
        }

        if ($action === 'seed') {
            $result = $demo->seed();
            $this->info('Criados: '.($result['created'] === [] ? 'nenhum' : implode(', ', $result['created'])).'. Já existiam: '.($result['skipped'] === [] ? 'nenhum' : implode(', ', $result['skipped'])).'.');

            return self::SUCCESS;
        }

        $this->info($demo->clear().' veículo(s) de demonstração removido(s).');

        return self::SUCCESS;
    }
}
