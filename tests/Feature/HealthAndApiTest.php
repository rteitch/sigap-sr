<?php

namespace Tests\Feature;

use App\Models\School;
use App\Services\MetricsService;
use App\Support\CircuitBreaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class HealthAndApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_liveness_and_readiness_endpoints_work(): void
    {
        $this->getJson('/health/live')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertHeader('X-Request-ID');

        $this->getJson('/health/ready')
            ->assertOk()
            ->assertJsonPath('status', 'ready')
            ->assertJsonPath('checks.database.status', 'ok');
    }

    public function test_heartbeat_reports_stale_until_scheduler_runs(): void
    {
        app(MetricsService::class)->reset();

        $this->getJson('/health/heartbeat')->assertStatus(503)->assertJsonPath('status', 'stale');

        $this->artisan('sigap:heartbeat')->assertSuccessful();

        $this->getJson('/health/heartbeat')->assertOk()->assertJsonPath('status', 'ok');
    }

    public function test_metrics_require_configured_token(): void
    {
        config(['sigap.health.metrics_token' => 'metrics-secret']);

        $this->get('/health/metrics')->assertForbidden();

        $this->withHeader('Authorization', 'Bearer metrics-secret')
            ->get('/health/metrics')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; version=0.0.4; charset=UTF-8')
            ->assertSee('sigap_http_requests_total');
    }

    public function test_request_telemetry_records_metrics(): void
    {
        app(MetricsService::class)->reset();
        config(['sigap.health.metrics_token' => 'metrics-secret']);

        $this->get('/login')->assertOk();

        $response = $this->withHeader('X-Metrics-Token', 'metrics-secret')->get('/health/metrics');
        $response->assertOk();

        $this->assertMatchesRegularExpression('/sigap_http_requests_total [1-9][0-9]*/', $response->getContent());
        $this->assertStringContainsString('sigap_http_responses_2xx_total', $response->getContent());
    }

    public function test_security_headers_are_present(): void
    {
        $this->get('/login')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_api_metadata_and_docs_are_public(): void
    {
        $this->getJson('/api/v1/meta')
            ->assertOk()
            ->assertJsonPath('data.api_version', 'v1')
            ->assertJsonPath('data.license', 'MIT');

        $this->get('/api/docs')->assertOk()->assertSee('Dokumentasi API');
        $this->getJson('/api/openapi.json')->assertOk()->assertJsonPath('openapi', '3.0.3');
    }

    public function test_master_api_requires_a_valid_key(): void
    {
        $school = School::query()->firstOrFail();
        config(['sigap.api.key' => 'test-api-key', 'sigap.api.school_id' => $school->id]);

        $this->getJson('/api/v1/levels')->assertUnauthorized();

        $this->withHeader('X-API-Key', 'wrong-key')
            ->getJson('/api/v1/levels')
            ->assertUnauthorized();

        $this->withHeader('X-API-Key', 'test-api-key')
            ->getJson('/api/v1/levels')
            ->assertOk()
            ->assertJsonPath('meta.count', 3)
            ->assertJsonStructure([
                'data' => [['id', 'code', 'name', 'sort_order', 'is_active']],
                'meta' => ['count', 'generated_at'],
            ]);
    }

    public function test_all_master_api_endpoints_are_scoped_and_read_only(): void
    {
        $school = School::query()->firstOrFail();
        config(['sigap.api.key' => 'test-api-key', 'sigap.api.school_id' => $school->id]);

        foreach ([
            'academic-years',
            'classrooms',
            'dorms',
            'incident-categories',
            'incident-locations',
        ] as $endpoint) {
            $this->withHeader('Authorization', 'Bearer test-api-key')
                ->getJson('/api/v1/'.$endpoint)
                ->assertOk()
                ->assertJsonStructure(['data', 'meta' => ['count', 'generated_at']]);
        }
    }

    public function test_circuit_breaker_opens_and_recovers(): void
    {
        $breaker = new CircuitBreaker('test-breaker', 2, 60);

        $breaker->recordFailure();
        $this->assertFalse($breaker->isOpen());

        $breaker->recordFailure();
        $this->assertTrue($breaker->isOpen());

        $result = $breaker->attempt(
            fn () => 'should-not-run',
            fn (RuntimeException $exception) => 'fallback:'.$exception->getMessage(),
        );

        $this->assertSame('fallback:Circuit breaker is open.', $result);

        $breaker->reset();
        $this->assertFalse($breaker->isOpen());
        $this->assertSame('ok', $breaker->attempt(fn () => 'ok'));
    }
}
