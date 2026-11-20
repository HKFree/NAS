<?php

declare(strict_types=1);

// Exercise the portal's HTTPS pages over the loopback HTTP test server.
if (!str_starts_with($_SERVER['REQUEST_URI'], '/api/')) {
    $_SERVER['HTTPS'] = 'on';
}

require __DIR__ . '/www/index.php';
