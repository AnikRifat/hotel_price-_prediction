<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__ . '/../storage/framework/maintenance.php')) {
    require $maintenance;
}

const HOLIDAYS = [
    '2023-01-01',
    '2023-01-24',
    '2023-01-25',
    '2023-02-01',
    '2023-02-22',
    '2023-04-07',
    '2023-04-22',
    '2023-04-23',
    '2023-05-01',
    '2023-05-04',
    '2023-06-02',
    '2023-06-29',
    '2023-07-19',
    '2023-08-31',
    '2023-09-16',
    '2023-09-28',
    '2023-10-29',
    '2023-11-14',
    '2023-12-25',

    '2024-01-01',
    '2024-02-10',
    '2024-02-11',
    '2024-02-01',
    '2024-03-18',
    '2024-03-29',
    '2024-04-10',
    '2024-04-11',
    '2024-05-01',
    '2024-05-23',
    '2024-06-01',
    '2024-06-17',
    '2024-07-07',
    '2024-08-31',
    '2024-09-16',
    '2024-09-17',
    '2024-10-26',
    '2024-11-09',
    '2024-12-25',
    '2025-01-01',
    '2025-01-29',
    '2025-01-30',
    '2025-02-01',
    '2025-02-14',
    '2025-02-21',
    '2025-03-21',
    '2025-04-18',
    '2025-05-01',
    '2025-05-21',
    '2025-06-07',
    '2025-06-28',
    '2025-07-17',
    '2025-08-31',
    '2025-09-16',
    '2025-09-17',
    '2025-10-23',
    '2025-11-08',
    '2025-12-25',
];



// Register the Composer autoloader...
require __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->handleRequest(Request::capture());
