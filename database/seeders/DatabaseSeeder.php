<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Idempotente e sem depender de factories/Faker (dev-only), para rodar também em produção.
     */
    public function run(): void
    {
        $this->call(ExpenseCategorySeeder::class);

        // Só cria a demonstração com MYCAR_DEMO=true (veja DemoSeeder).
        if (config('mycar.demo')) {
            $this->call(DemoSeeder::class);
        }

        User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => Hash::make('password')],
        );
    }
}
