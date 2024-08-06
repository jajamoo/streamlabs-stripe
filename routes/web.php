<?php

use App\Http\Controllers\StripeController;
use Illuminate\Support\Facades\Route;

Route::post('/subscribe', [StripeController::class, 'createSubscription']);
Route::post('/upgrade', [StripeController::class, 'upgradeSubscription']);
