<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function pages(): array
    {
        return [
            'dashboard' => ['/', 'Dashboard'],
            'vehicles' => ['/vehicles', 'Vehicles/Index'],
            'expenses' => ['/expenses', 'Expenses/Index'],
            'fuelings' => ['/fuelings', 'Fuelings/Index'],
            'reports' => ['/reports', 'Reports/Index'],
            'categories' => ['/categories', 'Categories/Index'],
        ];
    }

    #[DataProvider('pages')]
    public function test_provisional_pages_render(string $uri, string $component): void
    {
        $this->get($uri)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component($component));
    }

    public function test_unknown_route_renders_the_inertia_error_page(): void
    {
        $this->get('/rota-inexistente')
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Error')
                ->where('status', 404));
    }
}
