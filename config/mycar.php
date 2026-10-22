<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dados de demonstração
    |--------------------------------------------------------------------------
    |
    | Os dados de exemplo só são criados quando MYCAR_DEMO=true (ou pelo comando
    | explícito `php artisan mycar:demo seed`). Ficam identificados por "[DEMO]" no
    | nome do veículo e podem ser removidos sem tocar nos dados reais.
    |
    */

    'demo' => (bool) env('MYCAR_DEMO', false),

];
