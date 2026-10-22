<?php

namespace Tests\Feature;

use App\Domain\Fipe\FipeClient;
use App\Domain\Fipe\FipeException;
use App\Domain\Fipe\FipeVehicleType;
use App\Enums\VehicleCategory;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FipeClientTest extends TestCase
{
    private const BASE = 'https://fipe.test/api/v2';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.fipe.base_url' => self::BASE,
            'services.fipe.retries' => 2,
            'services.fipe.retry_sleep_ms' => 0,
            'services.fipe.token' => null,
        ]);
        Cache::flush();
        Http::preventStrayRequests();
    }

    private function client(): FipeClient
    {
        return app(FipeClient::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function quotePayload(array $overrides = []): array
    {
        return array_merge([
            'brand' => 'Chevrolet',
            'codeFipe' => '004001-0',
            'fuel' => 'Flex',
            'fuelAcronym' => 'G',
            'model' => 'Onix 1.0 LT',
            'modelYear' => 2023,
            'price' => 'R$ 68.450,90',
            'referenceMonth' => 'março de 2026',
            'vehicleType' => 1,
        ], $overrides);
    }

    public function test_catalog_endpoints_return_typed_options(): void
    {
        Http::fake([
            self::BASE.'/cars/brands' => Http::response([['code' => '23', 'name' => 'GM - Chevrolet'], ['code' => 59, 'name' => 'VW']]),
            self::BASE.'/cars/brands/23/models' => Http::response([['code' => '5940', 'name' => 'Onix 1.0 LT']]),
            self::BASE.'/cars/brands/23/models/5940/years' => Http::response([['code' => '2023-1', 'name' => '2023 Gasolina']]),
        ]);

        $brands = $this->client()->brands(FipeVehicleType::Cars);
        $this->assertSame('23', $brands[0]->code);
        $this->assertSame('GM - Chevrolet', $brands[0]->name);
        $this->assertSame('59', $brands[1]->code);
        $this->assertSame('Onix 1.0 LT', $this->client()->models(FipeVehicleType::Cars, '23')[0]->name);
        $this->assertSame('2023-1', $this->client()->years(FipeVehicleType::Cars, '23', '5940')[0]->code);
    }

    public function test_quote_is_parsed_with_value_in_cents_and_reference_month_from_the_source(): void
    {
        Http::fake([self::BASE.'/cars/brands/23/models/5940/years/2023-1' => Http::response($this->quotePayload())]);

        $quote = $this->client()->quote(FipeVehicleType::Cars, '23', '5940', '2023-1');

        $this->assertSame(6_845_090, $quote->valueCents);
        $this->assertSame('2026-03', $quote->referenceMonth);
        $this->assertSame('004001-0', $quote->codeFipe);
        $this->assertSame(2023, $quote->modelYear);
        $this->assertSame('Flex', $quote->fuel);
        $this->assertSame('Chevrolet', $quote->brand);
        $this->assertSame('cars', $quote->vehicleType);
        $this->assertSame('R$ 68.450,90', $quote->payload['price']);
    }

    public function test_category_maps_to_fipe_vehicle_type_and_other_requires_a_choice(): void
    {
        $this->assertSame(FipeVehicleType::Cars, FipeVehicleType::forCategory(VehicleCategory::Car));
        $this->assertSame(FipeVehicleType::Motorcycles, FipeVehicleType::forCategory(VehicleCategory::Motorcycle));
        $this->assertNull(FipeVehicleType::forCategory(VehicleCategory::Other));
        $this->assertSame('trucks', FipeVehicleType::Trucks->value);
    }

    public function test_not_found_is_mapped_without_retrying(): void
    {
        Http::fake([self::BASE.'/*' => Http::response(['error' => 'not found'], 404)]);

        try {
            $this->client()->quote(FipeVehicleType::Cars, '1', '2', '3-1');
            $this->fail('Deveria lançar FipeException.');
        } catch (FipeException $exception) {
            $this->assertSame(FipeException::NOT_FOUND, $exception->reason);
            $this->assertSame('A FIPE não encontrou essa combinação de marca, modelo e ano.', $exception->getMessage());
        }

        Http::assertSentCount(1);
    }

    public function test_server_errors_are_retried_and_then_reported_as_unavailable(): void
    {
        Http::fake([self::BASE.'/*' => Http::response('erro', 503)]);

        try {
            $this->client()->brands(FipeVehicleType::Cars);
            $this->fail('Deveria lançar FipeException.');
        } catch (FipeException $exception) {
            $this->assertSame(FipeException::UNAVAILABLE, $exception->reason);
            $this->assertStringContainsString('indisponível', $exception->getMessage());
        }

        Http::assertSentCount(2);
    }

    public function test_a_transient_failure_is_recovered_by_retry(): void
    {
        Http::fake([self::BASE.'/cars/brands' => Http::sequence()
            ->push('erro', 500)
            ->push([['code' => '23', 'name' => 'Chevrolet']])]);

        $this->assertSame('Chevrolet', $this->client()->brands(FipeVehicleType::Cars)[0]->name);
        Http::assertSentCount(2);
    }

    public function test_timeout_and_connection_failures_are_typed(): void
    {
        Http::fake([self::BASE.'/*' => fn () => throw new ConnectionException('cURL error 28: Operation timed out after 5000 milliseconds')]);

        try {
            $this->client()->brands(FipeVehicleType::Cars);
            $this->fail('Deveria lançar FipeException.');
        } catch (FipeException $exception) {
            $this->assertSame(FipeException::TIMEOUT, $exception->reason);
            $this->assertStringContainsString('demorou demais', $exception->getMessage());
        }

        Http::fake([self::BASE.'/*' => fn () => throw new ConnectionException('Could not resolve host')]);

        $this->expectException(FipeException::class);
        $this->client()->models(FipeVehicleType::Cars, '23');
    }

    public function test_malformed_responses_are_rejected(): void
    {
        Http::fake([
            self::BASE.'/cars/brands' => Http::response('<html>manutenção</html>', 200),
            self::BASE.'/cars/brands/1/models' => Http::response([['code' => '1']]),
            self::BASE.'/cars/brands/1/models/2/years/3-1' => Http::response($this->quotePayload(['price' => 'grátis'])),
            self::BASE.'/cars/brands/1/models/2/years/4-1' => Http::response($this->quotePayload(['referenceMonth' => 'mês qualquer'])),
            self::BASE.'/cars/brands/1/models/2/years/5-1' => Http::response(['brand' => 'x']),
            self::BASE.'/cars/brands/1/models/2/years/6-1' => Http::response($this->quotePayload(['price' => 'R$ 0,00'])),
        ]);

        $attempts = [
            fn () => $this->client()->brands(FipeVehicleType::Cars),
            fn () => $this->client()->models(FipeVehicleType::Cars, '1'),
            fn () => $this->client()->quote(FipeVehicleType::Cars, '1', '2', '3-1'),
            fn () => $this->client()->quote(FipeVehicleType::Cars, '1', '2', '4-1'),
            fn () => $this->client()->quote(FipeVehicleType::Cars, '1', '2', '5-1'),
            fn () => $this->client()->quote(FipeVehicleType::Cars, '1', '2', '6-1'),
        ];

        foreach ($attempts as $index => $attempt) {
            try {
                $attempt();
                $this->fail("Tentativa {$index} deveria falhar.");
            } catch (FipeException $exception) {
                $this->assertSame(FipeException::MALFORMED, $exception->reason, "Tentativa {$index}");
            }
        }
    }

    public function test_catalog_calls_are_cached_but_quotes_never_are(): void
    {
        Http::fake([
            self::BASE.'/cars/brands' => Http::response([['code' => '23', 'name' => 'Chevrolet']]),
            self::BASE.'/cars/brands/23/models/5940/years/2023-1' => Http::response($this->quotePayload()),
        ]);

        $this->client()->brands(FipeVehicleType::Cars);
        $this->client()->brands(FipeVehicleType::Cars);
        Http::assertSentCount(1);

        $this->client()->quote(FipeVehicleType::Cars, '23', '5940', '2023-1');
        $this->client()->quote(FipeVehicleType::Cars, '23', '5940', '2023-1');
        Http::assertSentCount(3);
    }

    public function test_failures_are_not_cached(): void
    {
        Http::fake([self::BASE.'/cars/brands' => Http::sequence()
            ->push('erro', 503)
            ->push('erro', 503)
            ->push([['code' => '23', 'name' => 'Chevrolet']])]);

        try {
            $this->client()->brands(FipeVehicleType::Cars);
        } catch (FipeException) {
        }

        $this->assertSame('Chevrolet', $this->client()->brands(FipeVehicleType::Cars)[0]->name);
    }

    public function test_requests_use_the_configured_base_url_timeout_and_optional_token(): void
    {
        config(['services.fipe.token' => 'abc123']);
        Http::fake([self::BASE.'/*' => Http::response([])]);

        $this->client()->brands(FipeVehicleType::Motorcycles);

        Http::assertSent(fn (Request $request) => $request->url() === self::BASE.'/motorcycles/brands'
            && $request->hasHeader('X-Subscription-Token', 'abc123')
            && $request->hasHeader('Accept', 'application/json'));
    }

    public function test_parsers(): void
    {
        $this->assertSame(100, FipeClient::parsePrice('R$ 1,00'));
        $this->assertSame(123_456_789, FipeClient::parsePrice('R$ 1.234.567,89'));
        $this->assertNull(FipeClient::parsePrice('1234,56'));
        $this->assertSame('2025-12', FipeClient::parseReferenceMonth('Dezembro de 2025'));
        $this->assertSame('2026-03', FipeClient::parseReferenceMonth('março de 2026'));
        $this->assertNull(FipeClient::parseReferenceMonth('2026-03'));
    }
}
