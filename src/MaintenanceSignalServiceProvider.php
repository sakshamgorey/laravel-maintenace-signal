<?php

declare(strict_types=1);

namespace Saksham\MaintenanceSignal;

use Illuminate\Support\ServiceProvider;

final class MaintenanceSignalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/maintenance-signal.php', 'maintenance-signal');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/maintenance-signal.php' => config_path('maintenance-signal.php'),
        ], 'maintenance-signal-config');
    }
}
