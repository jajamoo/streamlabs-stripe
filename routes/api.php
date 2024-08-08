<?php

use App\Http\Controllers\StripeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/subscribe', [StripeController::class, 'createSubscription']);
Route::post('/check', [StripeController::class, 'checkProration']);
