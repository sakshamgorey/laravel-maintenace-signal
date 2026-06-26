<?php

declare(strict_types=1);

return [
    'enabled' => env('MAINTENANCE_SIGNAL_ENABLED', true),

    'header' => env('MAINTENANCE_SIGNAL_HEADER', 'X-Laravel-Maintenance-Mode'),

    'value' => env('MAINTENANCE_SIGNAL_VALUE', 'active'),
];
