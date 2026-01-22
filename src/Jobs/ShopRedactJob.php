<?php

namespace Esign\LaravelShopify\Jobs;

use Esign\LaravelShopify\Models\Shop;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ShopRedactJob implements ShouldQueue
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
     * GDPR Compliance: Shop Data Erasure
     *
     * When a store owner requests complete data deletion (typically after uninstalling), you must:
     * 1. Delete all shop data your app has stored
     * 2. Remove merchant information and all associated data
     * 3. Keep only minimal data required for legal/accounting purposes (48 hours minimum)
     *
     * This webhook is sent 48 hours after a shop uninstalls your app.
     *
     * Reference: https://shopify.dev/docs/apps/build/privacy-law-compliance
     */
    public function handle(): void
    {
        $shopId = $this->webhookData['shop_id'] ?? null;

        Log::info('GDPR: Shop redaction request received', [
            'shop' => $this->shopDomain,
            'shop_id' => $shopId,
            'webhook_topic' => 'shop/redact',
        ]);

        // Find the shop (including soft-deleted)
        $shop = Shop::withTrashed()
            ->where('domain', $this->shopDomain)
            ->first();

        if (! $shop) {
            Log::warning('GDPR: Shop not found for redaction', [
                'shop' => $this->shopDomain,
                'shop_id' => $shopId,
            ]);

            return;
        }

        // Check if shop was uninstalled at least 48 hours ago
        if ($shop->deleted_at && $shop->deleted_at->addHours(48)->isFuture()) {
            Log::warning('GDPR: Shop redaction requested before 48-hour minimum retention', [
                'shop' => $this->shopDomain,
                'uninstalled_at' => $shop->deleted_at,
            ]);
            // Still process the redaction as Shopify is requesting it
        }

        // TODO: Implement your shop data deletion logic here
        // Example:
        // $this->redactShopData($shop);

        // Permanently delete the shop record (force delete)
        if ($shop->trashed()) {
            $shop->forceDelete();
        } else {
            $shop->delete(); // Soft delete first
            $shop->forceDelete(); // Then force delete
        }

        Log::info('GDPR: Shop data permanently deleted', [
            'shop' => $this->shopDomain,
            'shop_id' => $shopId,
        ]);
    }

    /**
     * Example: Redact all shop-related data before permanent deletion.
     */
    protected function redactShopData(Shop $shop): void
    {
        // Example implementation:

        // 1. Delete all customer data associated with this shop
        // CustomerPreference::where('shop_id', $shop->id)->delete();
        // CustomerAnalytics::where('shop_id', $shop->id)->delete();

        // 2. Delete all order/transaction data
        // Order::where('shop_id', $shop->id)->delete();
        // Transaction::where('shop_id', $shop->id)->delete();

        // 3. Delete product data
        // Product::where('shop_id', $shop->id)->delete();

        // 4. Delete analytics and logs
        // ShopAnalytics::where('shop_id', $shop->id)->delete();
        // ActivityLog::where('shop_id', $shop->id)->delete();

        // 5. Delete shop settings/preferences
        // ShopSetting::where('shop_id', $shop->id)->delete();

        // 6. Clear shop-specific cache
        // Cache::tags("shop_{$shop->id}")->flush();

        // 7. Delete uploaded files/media
        // Storage::deleteDirectory("shops/{$shop->id}");

        Log::info('GDPR: All shop-related data deleted', [
            'shop' => $shop->domain,
            'shop_id' => $shop->id,
        ]);
    }

    /**
     * Optional: Archive shop data before deletion for legal/accounting purposes.
     */
    protected function archiveShopData(Shop $shop): void
    {
        // Example: Create an anonymized archive for financial records
        // $archive = [
        //     'shop_id_hash' => hash('sha256', $shop->id),
        //     'total_revenue' => $shop->total_revenue,
        //     'subscription_months' => $shop->subscription_months,
        //     'archived_at' => now()->toIso8601String(),
        // ];
        //
        // Storage::put("archives/shop_{$archive['shop_id_hash']}.json", json_encode($archive));

        Log::info('GDPR: Shop data archived', [
            'shop' => $shop->domain,
        ]);
    }
}
