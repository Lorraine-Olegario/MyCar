<?php

namespace Tests\Feature;

use App\Domain\Expenses\ExpenseReceiptStorage;
use App\Domain\Vehicles\VehiclePhotoStorage;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Vehicle;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('local');
    }

    private function expensePayload(array $overrides = []): array
    {
        return array_merge([
            'vehicle_id' => Vehicle::factory()->create()->id,
            'expense_category_id' => ExpenseCategory::factory()->create()->id,
            'description' => 'Nota', 'amount_cents' => 1_000, 'occurred_on' => '2025-01-10',
        ], $overrides);
    }

    public function test_private_disk_is_not_served_publicly(): void
    {
        $this->assertSame(storage_path('app/private'), config('filesystems.disks.local.root'));
        $this->assertFalse(config('filesystems.disks.local.serve'));
        $this->assertFileDoesNotExist(public_path('storage'));
        $this->assertFileDoesNotExist(public_path('.env'));
        $this->assertSame([], glob(public_path('*.sqlite')) ?: []);
    }

    public function test_uploaded_files_land_in_the_private_disk_with_generated_names_never_in_public(): void
    {
        $this->post('/expenses', $this->expensePayload(['receipt' => UploadedFile::fake()->create('../../public/evil name.pdf', 20, 'application/pdf')]))
            ->assertSessionHasNoErrors();

        $expense = Expense::firstOrFail();
        $path = $expense->getRawOriginal('receipt_path');

        $this->assertMatchesRegularExpression('#^expenses/'.$expense->id.'/[0-9a-f-]{36}\.pdf$#', $path);
        $this->assertStringNotContainsString('evil', $path);
        $this->assertStringNotContainsString('..', $path);
        Storage::disk(ExpenseReceiptStorage::DISK)->assertExists($path);
        $this->assertFileDoesNotExist(public_path($path));
    }

    public function test_vehicle_photo_name_is_generated_and_served_only_by_the_controlled_route(): void
    {
        $this->post('/vehicles', [
            'name' => 'X', 'brand' => 'Fiat', 'model' => 'Uno', 'manufacture_year' => 2020, 'model_year' => 2020, 'category' => 'car',
            'fuel' => 'flex', 'odometer' => 1, 'acquisition_date' => '2021-01-01', 'status' => 'active',
            'photo' => UploadedFile::fake()->image('../../etc/passwd.jpg'),
        ])->assertSessionHasNoErrors();

        $vehicle = Vehicle::firstOrFail();
        $path = $vehicle->getRawOriginal('photo_path');

        $this->assertMatchesRegularExpression('#^vehicles/'.$vehicle->id.'/[0-9a-f-]{36}\.(jpg|jpeg)$#', $path);
        Storage::disk(VehiclePhotoStorage::DISK)->assertExists($path);
        $this->get("/vehicles/{$vehicle->id}/photo")->assertOk();
        $this->get("/storage/{$path}")->assertNotFound();
        $this->get('/vehicles/'.$vehicle->id.'/photo/../../.env')->assertNotFound();
    }

    /**
     * Arquivo real (não o falso do Laravel, que decide o tipo pela extensão), para o tipo ser
     * detectado pelo conteúdo como acontece em um upload de verdade.
     */
    private function realUpload(string $name, string $content, string $clientMime): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'up');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, $clientMime, null, true);
    }

    public function test_uploads_validate_real_mime_and_size(): void
    {
        $payload = fn (UploadedFile $file) => $this->expensePayload(['receipt' => $file]);

        // Conteúdo PHP disfarçado de PDF/imagem: o tipo real é detectado e o envio é recusado.
        $this->post('/expenses', $payload($this->realUpload('shell.pdf', '<?php system($_GET["c"]);', 'application/pdf')))->assertSessionHasErrors('receipt');
        $this->post('/expenses', $payload($this->realUpload('foto.png', '<?php echo 1;', 'image/png')))->assertSessionHasErrors('receipt');
        $this->post('/expenses', $payload(UploadedFile::fake()->create('script.php', 10, 'application/x-php')))->assertSessionHasErrors('receipt');
        $this->post('/expenses', $payload(UploadedFile::fake()->create('grande.pdf', 5_121, 'application/pdf')))->assertSessionHasErrors('receipt');
        $this->assertSame(0, Expense::count());

        $this->post('/expenses', $payload(UploadedFile::fake()->image('ok.png')))->assertSessionHasNoErrors();
        $this->assertSame(1, Expense::count());

        $vehicle = Vehicle::factory()->create();
        $base = [...$vehicle->only(['name', 'brand', 'model', 'manufacture_year', 'model_year', 'odometer']),
            'category' => 'car', 'fuel' => 'flex', 'acquisition_date' => '2021-01-01', 'status' => 'active'];

        $this->put("/vehicles/{$vehicle->id}", [...$base, 'photo' => $this->realUpload('foto.jpg', '<?php echo 1;', 'image/jpeg')])->assertSessionHasErrors('photo');
        $this->put("/vehicles/{$vehicle->id}", [...$base, 'photo' => UploadedFile::fake()->image('grande.jpg')->size(4_097)])->assertSessionHasErrors('photo');
        $this->assertNull($vehicle->fresh()->getRawOriginal('photo_path'));
    }

    public function test_mass_assignment_cannot_set_protected_fields(): void
    {
        $this->post('/expenses', $this->expensePayload([
            'id' => 999, 'receipt_path' => 'expenses/1/hack.pdf', 'recurring_expense_id' => 42, 'created_at' => '2000-01-01',
        ]))->assertSessionHasNoErrors();

        $expense = Expense::firstOrFail();
        $this->assertNotSame(999, $expense->id);
        $this->assertNull($expense->getRawOriginal('receipt_path'));
        $this->assertNull($expense->recurring_expense_id);
        $this->assertNotSame('2000', $expense->created_at->format('Y'));

        $vehicle = Vehicle::factory()->create();
        $this->put("/vehicles/{$vehicle->id}", [...$vehicle->only(['name', 'brand', 'model', 'manufacture_year', 'model_year', 'odometer']),
            'category' => 'car', 'fuel' => 'flex', 'acquisition_date' => '2021-01-01', 'status' => 'active',
            'fipe_value_cents' => 99_999_999, 'photo_path' => 'vehicles/1/x.jpg', 'deleted_at' => '2020-01-01'])->assertSessionHasNoErrors();

        $fresh = Vehicle::find($vehicle->id);
        $this->assertNotNull($fresh);
        $this->assertNull($fresh->fipe_value_cents);
        $this->assertNull($fresh->getRawOriginal('photo_path'));
    }

    public function test_csrf_protection_is_part_of_the_web_group(): void
    {
        $this->assertContains(PreventRequestForgery::class, app(Kernel::class)->getMiddlewareGroups()['web']);
    }

    public function test_security_headers_are_sent(): void
    {
        $response = $this->get('/');

        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertStringContainsString('camera=()', (string) $response->headers->get('Permissions-Policy'));
    }

    public function test_errors_do_not_expose_internals_when_debug_is_off(): void
    {
        config(['app.debug' => false]);
        Route::get('/__boom', fn () => throw new \RuntimeException('SEGREDO interno: senha=abc123'));

        $response = $this->get('/__boom');

        $response->assertStatus(500);
        $content = $response->getContent();
        $this->assertStringNotContainsString('SEGREDO', $content);
        $this->assertStringNotContainsString('abc123', $content);
        $this->assertStringNotContainsString('RuntimeException', $content);
        $this->assertStringNotContainsString(base_path(), $content);
        $this->assertStringContainsString('Error', $content);
    }

    public function test_unknown_pages_render_the_friendly_page_without_details(): void
    {
        config(['app.debug' => false]);

        $this->get('/nao-existe')->assertNotFound()->assertSee('Error', false);
        $this->get('/vehicles/999999')->assertNotFound();
    }

    public function test_fipe_endpoints_are_rate_limited(): void
    {
        RateLimiter::clear('fipe-quote');
        Cache::flush();
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response('erro', 503)]);
        $vehicle = Vehicle::factory()->create();
        $payload = ['type' => 'cars', 'brand_id' => '1', 'model_id' => '2', 'year_id' => '3-1'];

        for ($i = 0; $i < 10; $i++) {
            $this->post("/vehicles/{$vehicle->id}/fipe", $payload)->assertStatus(302);
        }

        $this->post("/vehicles/{$vehicle->id}/fipe", $payload)->assertStatus(429);
        $this->post("/vehicles/{$vehicle->id}/fipe/refresh")->assertStatus(429);
    }

    public function test_fipe_catalog_endpoints_are_rate_limited_per_minute(): void
    {
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response([['code' => '1', 'name' => 'A']])]);
        $vehicle = Vehicle::factory()->create();

        for ($i = 0; $i < 60; $i++) {
            $this->getJson("/vehicles/{$vehicle->id}/fipe/brands?type=cars")->assertOk();
        }

        $this->getJson("/vehicles/{$vehicle->id}/fipe/brands?type=cars")->assertStatus(429);
    }

    public function test_fipe_identifiers_cannot_inject_paths(): void
    {
        Http::preventStrayRequests();
        $vehicle = Vehicle::factory()->create();

        $this->getJson("/vehicles/{$vehicle->id}/fipe/models?type=cars&brand=1/../../admin")->assertStatus(422);
        $this->getJson("/vehicles/{$vehicle->id}/fipe/years?type=cars&brand=1&model=2%2F..")->assertStatus(422);
        $this->post("/vehicles/{$vehicle->id}/fipe", ['type' => 'cars', 'brand_id' => '1?x=1', 'model_id' => '2', 'year_id' => '3-1'])->assertSessionHasErrors('brand_id');
    }

    public function test_env_example_has_no_secrets_and_documents_production_settings(): void
    {
        $env = file_get_contents(base_path('.env.example'));

        $this->assertMatchesRegularExpression('/^APP_KEY=$/m', $env);
        $this->assertMatchesRegularExpression('/^FIPE_TOKEN=$/m', $env);
        $this->assertDoesNotMatchRegularExpression('/^(DB_PASSWORD|MAIL_PASSWORD|AWS_SECRET_ACCESS_KEY)=(?!null\s*$|\s*$).+/m', $env);
        $this->assertStringContainsString('APP_DEBUG=false', $env);
        $this->assertStringContainsString('SESSION_SECURE_COOKIE', $env);
    }
}
