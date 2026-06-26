# Laravel Maintenance Signal

Adds a configurable HTTP header to Laravel maintenance mode responses so uptime monitors can distinguish planned maintenance from real outages.

## Why this exists

A plain `503 Service Unavailable` is ambiguous. External monitors cannot reliably know whether the app is intentionally in Laravel maintenance mode or actually broken.

`503` without the header means real outage or unknown failure.

`503` with the header means planned Laravel maintenance.

This package adds the header only on Laravel maintenance responses by replacing Laravel's maintenance middleware with a small subclass.

## Installation

```bash
composer require saksham/laravel-maintenance-signal
```

## Publish config

```bash
php artisan vendor:publish --tag=maintenance-signal-config
```

## Usage

### Laravel 11, 12, and 13

In `bootstrap/app.php`, replace Laravel's default maintenance middleware with the package middleware:

```php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Saksham\MaintenanceSignal\Http\Middleware\PreventRequestsDuringMaintenance as MaintenanceSignalMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->use([
            \Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks::class,
            \Illuminate\Http\Middleware\TrustProxies::class,
            \Illuminate\Http\Middleware\HandleCors::class,
            MaintenanceSignalMiddleware::class,
            \Illuminate\Http\Middleware\ValidatePostSize::class,
            \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);
    })
    ->create();
```

### Laravel 10

In `app/Http/Kernel.php`, replace:

```php
\Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
```

with:

```php
\Saksham\MaintenanceSignal\Http\Middleware\PreventRequestsDuringMaintenance::class,
```

## Configuration

```env
MAINTENANCE_SIGNAL_ENABLED=true
MAINTENANCE_SIGNAL_HEADER=X-Laravel-Maintenance-Mode
MAINTENANCE_SIGNAL_VALUE=active
```

Default config:

```php
return [
    'enabled' => env('MAINTENANCE_SIGNAL_ENABLED', true),
    'header' => env('MAINTENANCE_SIGNAL_HEADER', 'X-Laravel-Maintenance-Mode'),
    'value' => env('MAINTENANCE_SIGNAL_VALUE', 'active'),
];
```

## Example response

```bash
php artisan down --retry=60 --refresh=15
```

```http
HTTP/1.1 503 Service Unavailable
Retry-After: 60
Refresh: 15
X-Laravel-Maintenance-Mode: active
```

## Uptime monitor examples

Generic logic:

```text
if status == 503 and header X-Laravel-Maintenance-Mode == active:
    suppress alert
else if status >= 500:
    alert
```

Better Stack:

```text
Treat a 503 response with X-Laravel-Maintenance-Mode: active as planned maintenance.
Alert on 5xx responses missing that header.
```

UptimeRobot:

```text
Use a keyword/header monitor where available, or route webhook alerts through the generic logic above.
```

Pingdom:

```text
Use a custom check or alert integration that inspects response headers before notifying.
```

AI agents:

```text
Agents that triage incidents can treat X-Laravel-Maintenance-Mode: active as planned maintenance instead of an unknown outage.
```

Generic curl:

```bash
curl -I https://example.com
```

Expected:

```http
HTTP/2 503
x-laravel-maintenance-mode: active
```

## Security note

This header only says the application is intentionally in maintenance mode. It does not expose secrets, paths, exception details, or server internals.

## Testing

```bash
composer test
```

## License

MIT
