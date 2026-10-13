<?php

use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\MobileFieldController;
use Illuminate\Support\Facades\Route;

/*
| Mobile field app (Flutter). Officers log in once, work offline, and sync queued returns when online.
*/
Route::prefix('mobile')->name('mobile.')->group(function () {
    Route::post('/login', [MobileAuthController::class, 'login'])->middleware('throttle:10,1')->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [MobileAuthController::class, 'me'])->name('me');
        Route::post('/logout', [MobileAuthController::class, 'logout'])->name('logout');
        Route::get('/bootstrap', [MobileFieldController::class, 'bootstrap'])->name('bootstrap');
        Route::post('/returns', [MobileFieldController::class, 'storeReturn'])->name('returns.store');
    });
});
