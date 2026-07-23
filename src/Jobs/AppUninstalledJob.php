<?php

namespace Esign\LaravelShopify\Jobs;

use Esign\LaravelShopify\Enums\LogCategory;
use Esign\LaravelShopify\Events\AppUninstalledEvent;
use Esign\LaravelShopify\Models\Shop;
use Esign\LaravelShopify\Support\ShopifyLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Handles the app/uninstalled webhook.
 *
 * Soft-deletes the shop record while retaining data for potential
 * reinstallation, and clears all tokens so no stale credentials remain.
 */
class AppUninstalledJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $shopDomain,
        public array $webhookData,
    ) {}

    public function handle(): void
    {
        ShopifyLogger::log(LogCategory::ShopLifecycle)->info('App uninstalled webhook received', [
            'shop' => $this->shopDomain,
            'webhook_topic' => 'app/uninstalled',
        ]);

        // Include soft-deleted shops in case of duplicate webhooks
        $shop = Shop::withTrashed()
            ->where('domain', $this->shopDomain)
            ->first();

        if (! $shop) {
            ShopifyLogger::log(LogCategory::ShopLifecycle)->warning('Shop not found for uninstall webhook', [
                'shop' => $this->shopDomain,
            ]);

            return;
        }

        // If already soft-deleted, nothing to do
        if ($shop->trashed()) {
            ShopifyLogger::log(LogCategory::ShopLifecycle)->info('Shop already marked as uninstalled', [
                'shop' => $this->shopDomain,
            ]);

            return;
        }

        // Clear tokens before soft-deleting so no stale tokens remain
        // if the shop reinstalls later
        $shop->update([
            'access_token' => null,
            'access_token_expires_at' => null,
            'refresh_token' => null,
            'refresh_token_expires_at' => null,
            'access_token_last_refreshed_at' => null,
            'uninstalled_at' => now(),
        ]);

        ShopifyLogger::log(LogCategory::ShopLifecycle)->info('Shop tokens cleared', [
            'shop' => $this->shopDomain,
        ]);

        // Soft delete the shop (marks as uninstalled)
        $shop->delete();

        ShopifyLogger::log(LogCategory::ShopLifecycle)->info('Shop marked as uninstalled (soft deleted)', [
            'shop' => $this->shopDomain,
            'uninstalled_at' => $shop->deleted_at,
        ]);

        AppUninstalledEvent::dispatch($shop);
    }
}
