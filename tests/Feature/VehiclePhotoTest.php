<?php

namespace Tests\Feature;

use App\Domain\Vehicles\VehiclePhotoStorage;
use App\Enums\FuelType;
use App\Enums\VehicleCategory;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehiclePhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(VehiclePhotoStorage::DISK);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Chevrolet Onix 1.0 LT',
            'brand' => 'Chevrolet',
            'model' => 'Onix',
            'version' => '1.0 LT',
            'manufacture_year' => 2022,
            'model_year' => 2023,
            'category' => VehicleCategory::Car->value,
            'plate' => 'ABC1D23',
            'color' => 'Prata',
            'fuel' => FuelType::Flex->value,
            'odometer' => 35_000,
            'acquisition_date' => '2023-05-10',
            'acquisition_odometer' => 12_000,
            'purchase_price_cents' => 7_500_000,
            'notes' => 'Veículo em bom estado.',
            'status' => VehicleStatus::Active->value,
            'tracking_start_date' => '2023-05-10',
        ], $overrides);
    }

    private function storePhotoFor(Vehicle $vehicle, string $name = 'carro.jpg'): string
    {
        $path = (string) UploadedFile::fake()->image($name)->storeAs(
            'vehicles/'.$vehicle->getKey(),
            $name,
            VehiclePhotoStorage::DISK,
        );

        $vehicle->update(['photo_path' => $path]);

        return $path;
    }

    public function test_photo_can_be_uploaded_when_creating_a_vehicle(): void
    {
        $response = $this->post('/vehicles', $this->payload([
            'photo' => UploadedFile::fake()->image('carro.jpg'),
        ]));

        $vehicle = Vehicle::query()->firstOrFail();

        $response->assertRedirect(route('vehicles.show', $vehicle));
        $response->assertSessionHasNoErrors();

        $this->assertNotNull($vehicle->photo_path);
        $this->assertStringStartsWith('vehicles/'.$vehicle->id.'/', $vehicle->photo_path);
        Storage::disk(VehiclePhotoStorage::DISK)->assertExists($vehicle->photo_path);
    }

    public function test_photo_accepts_png_and_webp(): void
    {
        foreach (['carro.png', 'carro.webp'] as $name) {
            $this->post('/vehicles', $this->payload([
                'plate' => null,
                'photo' => UploadedFile::fake()->image($name),
            ]))->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('vehicles', 2);
        $this->assertCount(2, Storage::disk(VehiclePhotoStorage::DISK)->allFiles());
    }

    public function test_photo_larger_than_four_megabytes_is_rejected(): void
    {
        $this->post('/vehicles', $this->payload([
            'photo' => UploadedFile::fake()->image('grande.jpg')->size(4097),
        ]))->assertSessionHasErrors('photo');

        $this->assertDatabaseCount('vehicles', 0);
        $this->assertSame([], Storage::disk(VehiclePhotoStorage::DISK)->allFiles());
    }

    public function test_file_whose_content_is_not_an_image_is_rejected(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'mycar').'.jpg';
        file_put_contents($path, 'isto não é uma imagem');

        $this->post('/vehicles', $this->payload([
            'photo' => new UploadedFile($path, 'foto.jpg', 'image/jpeg', null, true),
        ]))->assertSessionHasErrors('photo');

        $this->assertDatabaseCount('vehicles', 0);
        $this->assertSame([], Storage::disk(VehiclePhotoStorage::DISK)->allFiles());

        unlink($path);
    }

    public function test_photo_path_cannot_be_set_directly_by_the_client(): void
    {
        $this->post('/vehicles', $this->payload([
            'photo_path' => 'vehicles/1/invasao.jpg',
        ]))->assertSessionHasNoErrors();

        $this->assertNull(Vehicle::query()->firstOrFail()->photo_path);
        $this->assertSame([], Storage::disk(VehiclePhotoStorage::DISK)->allFiles());
    }

    public function test_replacing_the_photo_removes_the_old_file(): void
    {
        $this->post('/vehicles', $this->payload([
            'photo' => UploadedFile::fake()->image('antiga.jpg'),
        ]));

        $vehicle = Vehicle::query()->firstOrFail();
        $oldPath = $vehicle->photo_path;

        $response = $this->put("/vehicles/{$vehicle->id}", $this->payload([
            'photo' => UploadedFile::fake()->image('nova.jpg'),
        ]));

        $vehicle->refresh();

        $response->assertRedirect(route('vehicles.show', $vehicle));
        $this->assertNotSame($oldPath, $vehicle->photo_path);
        Storage::disk(VehiclePhotoStorage::DISK)->assertMissing($oldPath);
        Storage::disk(VehiclePhotoStorage::DISK)->assertExists($vehicle->photo_path);
        $this->assertCount(1, Storage::disk(VehiclePhotoStorage::DISK)->allFiles());
    }

    public function test_photo_can_be_removed_on_update(): void
    {
        $vehicle = Vehicle::factory()->create();
        $oldPath = $this->storePhotoFor($vehicle);

        $this->put("/vehicles/{$vehicle->id}", $this->payload([
            'remove_photo' => true,
        ]))->assertSessionHasNoErrors();

        $this->assertNull($vehicle->fresh()->photo_path);
        Storage::disk(VehiclePhotoStorage::DISK)->assertMissing($oldPath);
        $this->assertSame([], Storage::disk(VehiclePhotoStorage::DISK)->allFiles());
    }

    public function test_update_without_photo_keeps_the_current_one(): void
    {
        $vehicle = Vehicle::factory()->create();
        $path = $this->storePhotoFor($vehicle);

        $this->put("/vehicles/{$vehicle->id}", $this->payload([
            'plate' => null,
            'color' => 'Preto',
        ]))->assertSessionHasNoErrors();

        $this->assertSame('Preto', $vehicle->fresh()->color);
        $this->assertSame($path, $vehicle->fresh()->photo_path);
        Storage::disk(VehiclePhotoStorage::DISK)->assertExists($path);
    }

    public function test_vehicle_can_be_created_and_updated_without_photo(): void
    {
        $this->post('/vehicles', $this->payload())->assertSessionHasNoErrors();

        $vehicle = Vehicle::query()->firstOrFail();

        $this->assertNull($vehicle->photo_path);

        $this->put("/vehicles/{$vehicle->id}", $this->payload(['color' => 'Preto']))
            ->assertSessionHasNoErrors();

        $this->assertNull($vehicle->fresh()->photo_path);
    }

    public function test_photo_route_serves_the_private_file(): void
    {
        $vehicle = Vehicle::factory()->create();
        $path = $this->storePhotoFor($vehicle);

        $response = $this->get("/vehicles/{$vehicle->id}/photo");

        $response->assertOk();
        $this->assertStringStartsWith('image/', (string) $response->headers->get('content-type'));
        $this->assertNotEmpty($response->streamedContent());
        $this->assertStringEndsWith(basename($path), (string) $response->headers->get('content-disposition'));
    }

    public function test_photo_route_returns_not_found_when_there_is_no_photo(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->get("/vehicles/{$vehicle->id}/photo")->assertNotFound();
    }

    public function test_archiving_keeps_the_photo_available(): void
    {
        $vehicle = Vehicle::factory()->create();
        $path = $this->storePhotoFor($vehicle);

        $this->delete("/vehicles/{$vehicle->id}")->assertRedirect(route('vehicles.index'));

        $this->assertSoftDeleted($vehicle);
        Storage::disk(VehiclePhotoStorage::DISK)->assertExists($path);
        $this->assertSame($path, Vehicle::withTrashed()->findOrFail($vehicle->id)->photo_path);

        $this->get("/vehicles/{$vehicle->id}/photo")->assertOk();
    }

    public function test_photo_is_not_reachable_through_a_public_url(): void
    {
        $this->post('/vehicles', $this->payload([
            'photo' => UploadedFile::fake()->image('carro.jpg'),
        ]));

        $vehicle = Vehicle::query()->firstOrFail();
        $path = (string) $vehicle->photo_path;

        $this->assertFileDoesNotExist(public_path($path));

        // O disco `local` é privado: /storage exige URL assinada e a rota
        // /storage/... nega o acesso direto (403 em dev, 404 em produção).
        $this->assertContains($this->get('/storage/'.$path)->getStatusCode(), [403, 404]);
    }
}
