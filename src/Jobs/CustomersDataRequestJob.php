<?php

namespace Esign\LaravelShopify\Jobs;

use Esign\LaravelShopify\Models\Shop;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CustomersDataRequestJob implements ShouldQueue
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
     * GDPR Compliance: Customer Data Request
     *
     * When a customer requests their data via Shopify, you must:
     * 1. Gather all customer data your app has stored
     * 2. Provide it in a readable format (JSON, CSV, etc.)
     * 3. Send it to the customer or make it available for download
     *
     * Reference: https://shopify.dev/docs/apps/build/privacy-law-compliance
     */
    public function handle(): void
    {
        $customerId = $this->webhookData['customer']['id'] ?? null;
        $customerEmail = $this->webhookData['customer']['email'] ?? null;

        Log::info('GDPR: Customer data request received', [
            'shop' => $this->shopDomain,
            'customer_id' => $customerId,
            'customer_email' => $customerEmail,
            'webhook_topic' => 'customers/data_request',
        ]);

        // Find the shop
        $shop = Shop::where('domain', $this->shopDomain)->first();

        if (! $shop) {
            Log::warning('GDPR: Shop not found for customer data request', [
                'shop' => $this->shopDomain,
                'customer_id' => $customerId,
            ]);

            return;
        }

        // TODO: Implement your data collection logic here
        // Example:
        // $customerData = $this->collectCustomerData($shop, $customerId);
        // $this->sendDataToCustomer($customerEmail, $customerData);

        Log::info('GDPR: Customer data request processed', [
            'shop' => $this->shopDomain,
            'customer_id' => $customerId,
        ]);

        // IMPORTANT: You have 30 days to fulfill this request
        // Consider storing a record of the request and its fulfillment status
    }

    /**
     * Example: Collect all customer data stored by your app.
     *
     * @param  string  $customerId  Shopify customer ID (e.g., "gid://shopify/Customer/123")
     */
    protected function collectCustomerData(Shop $shop, string $customerId): array
    {
        // Example implementation:
        // Gather all data related to this customer from your database

        return [
            'customer_id' => $customerId,
            'shop' => $shop->domain,
            'collected_at' => now()->toIso8601String(),
            'data' => [
                // Example: Orders, preferences, analytics, etc.
                // 'orders' => $this->getCustomerOrders($shop, $customerId),
                // 'preferences' => $this->getCustomerPreferences($shop, $customerId),
                // 'analytics' => $this->getCustomerAnalytics($shop, $customerId),
            ],
        ];
    }

    /**
     * Example: Send collected data to the customer.
     */
    protected function sendDataToCustomer(string $email, array $data): void
    {
        // Example implementation:
        // 1. Generate a secure download link
        // 2. Email the customer with the link
        // 3. Log the fulfillment

        // Mail::to($email)->send(new CustomerDataExport($data));

        Log::info('GDPR: Customer data sent', [
            'email' => $email,
            'data_size' => count($data),
        ]);
    }
}
