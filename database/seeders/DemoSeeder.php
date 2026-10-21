<?php

namespace Database\Seeders;

use App\Domain\Demo\DemoData;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Cria os dados de demonstração, mas só com MYCAR_DEMO=true: um `db:seed` comum
     * nunca polui dados reais.
     */
    public function run(DemoData $demo): void
    {
        if (! config('mycar.demo')) {
            $this->command?->warn('Dados de demonstração ignorados. Defina MYCAR_DEMO=true para criá-los.');

            return;
        }

        $result = $demo->seed();

        $this->command?->info('Demonstração: '.count($result['created']).' veículo(s) criado(s), '.count($result['skipped']).' já existia(m).');
    }
}
