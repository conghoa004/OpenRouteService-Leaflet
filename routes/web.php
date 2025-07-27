<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MapController;

Route::get('/', [MapController::class, 'index']);


// Nạp tuyến đường xử lý api
require __DIR__ . '/api.php';