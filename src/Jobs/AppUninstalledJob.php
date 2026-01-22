<?php

namespace Esign\LaravelShopify\Jobs;

use Esign\LaravelShopify\Models\Shop;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AppUninstalledJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $shopDomain,
        public array $webhookData,
    ) {}

    /**
     * Execute the job.
     *
     * This job is triggered when a merchant uninstalls your app from their store.
     * It soft-deletes the shop record while retaining data for potential reinstallation.
     */
    public function handle(): void
    {
        Log::info('App uninstalled webhook received', [
            'shop' => $this->shopDomain,
            'webhook_topic' => 'app/uninstalled',
        ]);

        // Find the shop (include soft-deleted in case of duplicate webhooks)
        $shop = Shop::withTrashed()
            ->where('domain', $this->shopDomain)
            ->first();

        if (! $shop) {
            Log::warning('Shop not found for uninstall webhook', [
                'shop' => $this->shopDomain,
            ]);

            return;
        }

        // If already soft-deleted, nothing to do
        if ($shop->trashed()) {
            Log::info('Shop already marked as uninstalled', [
                'shop' => $this->shopDomain,
            ]);

            return;
        }

        // Clear tokens before soft-deleting
        // This ensures no stale tokens remain if the shop reinstalls later
        $shop->update([
            'access_token' => null,
            'access_token_expires_at' => null,
            'refresh_token' => null,
            'refresh_token_expires_at' => null,
            'access_token_last_refreshed_at' => null,
            'token_refresh_count' => 0,
        ]);

        Log::info('Shop tokens cleared', [
            'shop' => $this->shopDomain,
        ]);

        // Soft delete the shop (marks as uninstalled)
        $shop->delete();

        Log::info('Shop marked as uninstalled (soft deleted)', [
            'shop' => $this->shopDomain,
            'uninstalled_at' => $shop->deleted_at,
        ]);

        // Optional: Trigger additional cleanup logic
        // $this->cleanupShopResources($shop);
    }

    /**
     * Optional: Perform additional cleanup when shop is uninstalled.
     *
     * Examples:
     * - Cancel any active subscriptions
     * - Clean up shop-specific cache
     * - Send notifications to your team
     * - Archive shop-specific data
     */
    protected function cleanupShopResources(Shop $shop): void
    {
        // Example: Clear shop-specific cache
        // Cache::forget("shop_data_{$shop->id}");

        // Example: Log to monitoring service
        // app('monitoring')->track('shop_uninstalled', [
        //     'shop_domain' => $shop->domain,
        //     'installed_at' => $shop->installed_at,
        //     'uninstalled_at' => $shop->deleted_at,
        // ]);
    }
}
