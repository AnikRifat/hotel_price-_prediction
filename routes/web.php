<?php

use App\Http\Controllers\PriceProjectionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/price-projection', [PriceProjectionController::class, 'getProjectionDashboard']);
