<?php

use Esign\LaravelShopify\Http\Controllers\AppController;
use Esign\LaravelShopify\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Shopify Embedded App Routes
|--------------------------------------------------------------------------
|
| These routes handle session token authentication for embedded Shopify apps.
| No OAuth flow needed - Shopify manages installation via shopify.app.toml.
|
*/

Route::prefix('shopify')
    ->name('shopify.')
    ->group(function () {

        // Token refresh bounce page (no authentication)
        Route::get('/auth/token-refresh', [AuthController::class, 'tokenRefresh'])
            ->name('auth.token-refresh');

        // Error page (no authentication)
        Route::get('/auth/error', [AuthController::class, 'error'])
            ->name('auth.error');
    });

// Embedded App Home (requires session token authentication)
Route::middleware(['shopify.verify.embedded-app'])
    ->group(function () {
        Route::get('/', [AppController::class, 'home'])
            ->name('app.home');
    });
