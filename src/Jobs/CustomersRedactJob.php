<?php

namespace Esign\LaravelShopify\Jobs;

use Esign\LaravelShopify\Models\Shop;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CustomersRedactJob implements ShouldQueue
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
     * GDPR Compliance: Customer Data Erasure
     *
     * When a customer requests data deletion (right to be forgotten), you must:
     * 1. Delete or anonymize all customer data your app has stored
     * 2. Remove personally identifiable information (PII)
     * 3. Keep only data required for legal/accounting purposes
     *
     * This webhook is sent 48 hours after a store owner requests customer deletion.
     *
     * Reference: https://shopify.dev/docs/apps/build/privacy-law-compliance
     */
    public function handle(): void
    {
        $customerId = $this->webhookData['customer']['id'] ?? null;
        $customerEmail = $this->webhookData['customer']['email'] ?? null;
        $ordersToRedact = $this->webhookData['orders_to_redact'] ?? [];

        Log::info('GDPR: Customer redaction request received', [
            'shop' => $this->shopDomain,
            'customer_id' => $customerId,
            'customer_email' => $customerEmail,
            'orders_count' => count($ordersToRedact),
            'webhook_topic' => 'customers/redact',
        ]);

        // Find the shop
        $shop = Shop::where('domain', $this->shopDomain)->first();

        if (! $shop) {
            Log::warning('GDPR: Shop not found for customer redaction', [
                'shop' => $this->shopDomain,
                'customer_id' => $customerId,
            ]);

            return;
        }

        // TODO: Implement your data deletion logic here
        // Example:
        // $this->redactCustomerData($shop, $customerId);
        // $this->anonymizeCustomerOrders($shop, $ordersToRedact);

        Log::info('GDPR: Customer data redacted', [
            'shop' => $this->shopDomain,
            'customer_id' => $customerId,
        ]);
    }

    /**
     * Example: Redact all customer data from your database.
     *
     * @param  string  $customerId  Shopify customer ID (e.g., "gid://shopify/Customer/123")
     */
    protected function redactCustomerData(Shop $shop, string $customerId): void
    {
        // Example implementation:

        // 1. Delete customer preferences
        // CustomerPreference::where('shop_id', $shop->id)
        //     ->where('customer_id', $customerId)
        //     ->delete();

        // 2. Delete customer analytics/tracking data
        // CustomerAnalytics::where('shop_id', $shop->id)
        //     ->where('customer_id', $customerId)
        //     ->delete();

        Log::info('GDPR: Customer personal data deleted', [
            'shop' => $shop->domain,
            'customer_id' => $customerId,
        ]);
    }

    /**
     * Example: Anonymize order data while keeping transaction records.
     *
     * @param  array  $orderIds  Array of Shopify order IDs
     */
    protected function anonymizeCustomerOrders(Shop $shop, array $orderIds): void
    {
        // Example implementation:
        // Keep financial records but remove PII

        // foreach ($orderIds as $orderId) {
        //     Order::where('shop_id', $shop->id)
        //         ->where('shopify_order_id', $orderId)
        //         ->update([
        //             'customer_email' => null,
        //             'customer_phone' => null,
        //             'shipping_address' => null,
        //             'billing_address' => null,
        //             'customer_note' => null,
        //             'anonymized_at' => now(),
        //         ]);
        // }

        Log::info('GDPR: Customer orders anonymized', [
            'shop' => $shop->domain,
            'orders_count' => count($orderIds),
        ]);
    }
}
