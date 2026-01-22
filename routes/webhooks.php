<?php

use Esign\LaravelShopify\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('webhooks')
    ->middleware(['shopify.verify.webhook'])
    ->group(function () {
        // Single route handles all webhook topics
        // Example: POST /webhooks/app/uninstalled
        Route::post('/{topic}', [WebhookController::class, 'handle'])
            ->where('topic', '.*') // Match any topic (including slashes)
            ->name('shopify.webhooks.handle');
    });
