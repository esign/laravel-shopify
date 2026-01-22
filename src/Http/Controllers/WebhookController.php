<?php

namespace Esign\LaravelShopify\Http\Controllers;

use Esign\LaravelShopify\Models\Shop;
use Esign\LaravelShopify\Webhooks\WebhookDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            ->where('domain', $shop)
            ->first();

        if (! $shopModel) {
            Log::warning('Webhook received for unknown shop', [
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
