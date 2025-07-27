<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    /**
     * Test basic health check endpoint
     */
    public function test_basic_health_check(): void
    {
        $response = $this->get('/health');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'timestamp',
                    'environment',
                    'version',
                ])
                ->assertJson([
                    'status' => 'ok',
                ]);
    }

    /**
     * Test detailed health check endpoint
     */
    public function test_detailed_health_check(): void
    {
        $response = $this->get('/health/detailed');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'timestamp',
                    'environment',
                    'version',
                    'checks' => [
                        'database' => ['status'],
                        'cache' => ['status'],
                        'queue' => ['status'],
                        'storage' => ['status'],
                    ],
                ]);
    }

    /**
     * Test that health check includes environment information
     */
    public function test_health_check_includes_environment(): void
    {
        $response = $this->get('/health');

        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertArrayHasKey('environment', $data);
        $this->assertEquals('testing', $data['environment']);
    }

    /**
     * Test that detailed health check validates all services
     */
    public function test_detailed_health_check_validates_services(): void
    {
        $response = $this->get('/health/detailed');

        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertArrayHasKey('checks', $data);
        
        $checks = $data['checks'];
        $this->assertArrayHasKey('database', $checks);
        $this->assertArrayHasKey('cache', $checks);
        $this->assertArrayHasKey('queue', $checks);
        $this->assertArrayHasKey('storage', $checks);
        
        // All checks should have a status
        foreach ($checks as $service => $check) {
            $this->assertArrayHasKey('status', $check, "Service {$service} should have a status");
        }
    }
}