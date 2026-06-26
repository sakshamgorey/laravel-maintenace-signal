<?php

declare(strict_types=1);

namespace Saksham\MaintenanceSignal\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as LaravelPreventRequestsDuringMaintenance;

final class PreventRequestsDuringMaintenance extends LaravelPreventRequestsDuringMaintenance
{
    protected function getHeaders($data)
    {
        $headers = parent::getHeaders($data);

        if (! config('maintenance-signal.enabled')) {
            return $headers;
        }

        $headers[(string) config('maintenance-signal.header')] = (string) config('maintenance-signal.value');

        return $headers;
    }
}
