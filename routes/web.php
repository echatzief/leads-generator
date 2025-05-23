<?php

use App\Http\Controllers\LeadsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LeadsController::class, 'index'])->name('home');

Route::resource('leads', LeadsController::class)->only(['store', 'update', 'destroy']);

