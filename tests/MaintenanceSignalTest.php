<?php

declare(strict_types=1);

namespace Saksham\MaintenanceSignal\Tests;

use Illuminate\Contracts\Http\Kernel;
use Orchestra\Testbench\TestCase;
use ReflectionObject;
use Saksham\MaintenanceSignal\Http\Middleware\PreventRequestsDuringMaintenance;
use Saksham\MaintenanceSignal\MaintenanceSignalServiceProvider;

final class MaintenanceSignalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->usePackageMaintenanceMiddleware();
    }

    protected function getPackageProviders($app): array
    {
        return [
            MaintenanceSignalServiceProvider::class,
        ];
    }

    protected function defineRoutes($router): void
    {
        $router->get('/', static fn () => 'ok');
    }

    public function test_it_adds_the_default_header_during_maintenance_mode(): void
    {
        $this->whileDown([], function (): void {
            $this->get('/')
                ->assertStatus(503)
                ->assertHeader('X-Laravel-Maintenance-Mode', 'active');
        });
    }

    public function test_it_preserves_retry_after_header(): void
    {
        $this->whileDown(['--retry' => 60], function (): void {
            $this->get('/')
                ->assertStatus(503)
                ->assertHeader('Retry-After', '60')
                ->assertHeader('X-Laravel-Maintenance-Mode', 'active');
        });
    }

    public function test_it_preserves_refresh_header(): void
    {
        $this->whileDown(['--refresh' => 15], function (): void {
            $this->get('/')
                ->assertStatus(503)
                ->assertHeader('Refresh', '15')
                ->assertHeader('X-Laravel-Maintenance-Mode', 'active');
        });
    }

    public function test_it_does_not_add_the_header_when_disabled(): void
    {
        config()->set('maintenance-signal.enabled', false);

        $this->whileDown([], function (): void {
            $this->get('/')
                ->assertStatus(503)
                ->assertHeaderMissing('X-Laravel-Maintenance-Mode');
        });
    }

    public function test_it_uses_custom_header_configuration(): void
    {
        config()->set('maintenance-signal.header', 'X-App-Maintenance');
        config()->set('maintenance-signal.value', 'yes');

        $this->whileDown([], function (): void {
            $this->get('/')
                ->assertStatus(503)
                ->assertHeader('X-App-Maintenance', 'yes');
        });
    }

    public function test_it_does_not_add_the_header_when_application_is_up(): void
    {
        $this->artisan('up');

        $this->get('/')
            ->assertOk()
            ->assertHeaderMissing('X-Laravel-Maintenance-Mode');
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function whileDown(array $arguments, callable $assert): void
    {
        try {
            $this->artisan('down', $arguments);

            $assert();
        } finally {
            $this->artisan('up');
        }
    }

    private function usePackageMaintenanceMiddleware(): void
    {
        $kernel = $this->app->make(Kernel::class);
        $reflection = new ReflectionObject($kernel);

        while (! $reflection->hasProperty('middleware')) {
            $reflection = $reflection->getParentClass();
        }

        $middleware = $reflection->getProperty('middleware');
        $middleware->setAccessible(true);
        $middleware->setValue($kernel, [
            PreventRequestsDuringMaintenance::class,
        ]);
    }
}
