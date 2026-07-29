<?php

namespace Esign\LaravelShopify\Http\Controllers;

use Esign\LaravelShopify\Enums\LogCategory;
use Esign\LaravelShopify\Models\Shop;
use Esign\LaravelShopify\Support\ShopifyLogger;
use Esign\LaravelShopify\Webhooks\WebhookDispatcher;
use Illuminate\Http\Request;

class WebhookController
{
    public function __construct(
        protected WebhookDispatcher $dispatcher,
    ) {}

    /**
     * Handle incoming webhook from Shopify.
     *
     * POST /webhooks/{topic}
     *
     * HMAC verification happens in VerifyWebhook middleware.
     */
    public function handle(Request $request, string $topic)
    {
        $shop = $request->header('X-Shopify-Shop-Domain');
        $webhookData = $request->all();

        // Find shop (include soft-deleted for uninstall webhook)
        $shopModel = Shop::withTrashed()
            ->byDomain($shop)
            ->first();

        if (! $shopModel) {
            ShopifyLogger::log(LogCategory::Webhooks)->warning('Webhook received for unknown shop', [
                'topic' => $topic,
                'shop' => $shop,
            ]);

            return response()->json(['status' => 'ignored'], 200);
        }

        // Dispatch to configured job
        $this->dispatcher->dispatch($topic, $shopModel, $webhookData);

        return response()->json(['status' => 'queued'], 200);
    }
}
