<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TechnicianController;

Route::post('/api/technicians', [TechnicianController::class, 'findNearest']);