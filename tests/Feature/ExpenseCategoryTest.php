<?php

namespace Tests\Feature;

use App\Models\ExpenseCategory;
use Database\Seeders\ExpenseCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExpenseCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_seeder_creates_the_17_categories_in_order_and_is_idempotent(): void
    {
        $this->seed(ExpenseCategorySeeder::class);
        $this->seed(ExpenseCategorySeeder::class);

        $categories = ExpenseCategory::query()->ordered()->get();

        $this->assertCount(17, $categories);
        $this->assertSame('Combustível', $categories->first()->name);
        $this->assertSame('combustivel', $categories->first()->slug);
        $this->assertSame('Outros gastos', $categories->last()->name);
        $this->assertSame(range(1, 17), $categories->pluck('sort_order')->all());
    }

    public function test_database_seeder_creates_categories_even_without_model_events(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->assertSame(17, ExpenseCategory::count());
        $this->assertNotNull(ExpenseCategory::first()->slug);
    }

    public function test_seeder_preserves_user_adjustments(): void
    {
        $this->seed(ExpenseCategorySeeder::class);
        ExpenseCategory::where('name', 'Seguro')->update(['active' => false, 'color' => '#000000']);

        $this->seed(ExpenseCategorySeeder::class);

        $seguro = ExpenseCategory::where('name', 'Seguro')->first();
        $this->assertFalse($seguro->active);
        $this->assertSame('#000000', $seguro->color);
    }

    public function test_index_lists_categories_ordered(): void
    {
        ExpenseCategory::factory()->create(['name' => 'B', 'sort_order' => 2]);
        ExpenseCategory::factory()->create(['name' => 'A', 'sort_order' => 1]);

        $this->get('/categories')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Categories/Index')
                ->where('categories.0.name', 'A')
                ->where('categories.1.name', 'B'));
    }

    public function test_category_can_be_created_at_the_end_of_the_order(): void
    {
        ExpenseCategory::factory()->create(['sort_order' => 5]);

        $this->post('/categories', ['name' => 'Rastreador', 'color' => '#112233'])
            ->assertRedirect('/categories');

        $category = ExpenseCategory::where('name', 'Rastreador')->first();
        $this->assertSame('rastreador', $category->slug);
        $this->assertSame(6, $category->sort_order);
        $this->assertTrue($category->active);
    }

    public function test_name_is_required_and_unique_ignoring_case(): void
    {
        ExpenseCategory::factory()->create(['name' => 'Combustível']);

        $this->post('/categories', ['name' => ''])->assertSessionHasErrors('name');
        $this->post('/categories', ['name' => 'combustível'])
            ->assertSessionHasErrors(['name' => 'Já existe uma categoria com este nome.']);
        $this->post('/categories', ['name' => 'Rastreador', 'color' => 'azul'])->assertSessionHasErrors('color');
    }

    public function test_update_allows_keeping_the_own_name_and_regenerates_slug(): void
    {
        $category = ExpenseCategory::factory()->create(['name' => 'Seguro']);

        $this->put("/categories/{$category->id}", ['name' => 'Seguro', 'color' => '#ffffff'])
            ->assertRedirect('/categories');
        $this->put("/categories/{$category->id}", ['name' => 'Seguro auto'])
            ->assertRedirect('/categories');

        $this->assertSame('seguro-auto', $category->fresh()->slug);
    }

    public function test_slug_collisions_get_a_numeric_suffix(): void
    {
        $first = ExpenseCategory::factory()->create(['name' => 'Outros gastos']);
        $second = ExpenseCategory::factory()->create(['name' => 'Outros-gastos']);

        $this->assertSame('outros-gastos', $first->slug);
        $this->assertSame('outros-gastos-2', $second->slug);
    }

    public function test_category_can_be_deactivated_and_reactivated(): void
    {
        $category = ExpenseCategory::factory()->create();

        $this->patch("/categories/{$category->id}/toggle")->assertRedirect('/categories');
        $this->assertFalse($category->fresh()->active);

        $this->patch("/categories/{$category->id}/toggle");
        $this->assertTrue($category->fresh()->active);
    }

    public function test_categories_can_be_reordered(): void
    {
        $a = ExpenseCategory::factory()->create(['sort_order' => 1]);
        $b = ExpenseCategory::factory()->create(['sort_order' => 2]);
        $c = ExpenseCategory::factory()->create(['sort_order' => 3]);

        $this->patch("/categories/{$c->id}/move", ['direction' => 'up'])->assertRedirect('/categories');
        $this->assertSame([$a->id, $c->id, $b->id], ExpenseCategory::ordered()->pluck('id')->all());

        $this->patch("/categories/{$a->id}/move", ['direction' => 'up']);
        $this->assertSame([$a->id, $c->id, $b->id], ExpenseCategory::ordered()->pluck('id')->all());

        $this->patch("/categories/{$a->id}/move", ['direction' => 'sideways'])->assertSessionHasErrors('direction');
    }

    public function test_unused_category_can_be_deleted(): void
    {
        $category = ExpenseCategory::factory()->create();

        $this->delete("/categories/{$category->id}")->assertRedirect('/categories');

        $this->assertModelMissing($category);
    }

    public function test_category_in_use_is_not_deleted(): void
    {
        $category = ExpenseCategory::factory()->create();

        Schema::create('expenses', function ($table) {
            $table->id();
            $table->unsignedBigInteger('expense_category_id');
        });
        DB::table('expenses')->insert(['expense_category_id' => $category->id]);

        $this->delete("/categories/{$category->id}")
            ->assertRedirect('/categories')
            ->assertSessionHas('error');

        $this->assertModelExists($category);
        $this->assertTrue($category->fresh()->in_use);
    }
}
