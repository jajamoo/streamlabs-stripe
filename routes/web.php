<?php

use App\Http\Controllers\StripeController;
use Illuminate\Support\Facades\Route;

Route::get('/totals', [StripeController::class, 'getTotals']);
