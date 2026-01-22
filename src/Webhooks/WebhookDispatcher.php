<?php

namespace Esign\LaravelShopify\Webhooks;

use Esign\LaravelShopify\Models\Shop;
use Illuminate\Support\Facades\Log;

class WebhookDispatcher
{
    /**
     * Dispatch webhook to configured job.
     */
    public function dispatch(string $topic, Shop $shop, array $webhookData): void
    {
        $config = config("shopify.webhooks.routes.{$topic}");

        if (! $config) {
            Log::warning('No handler configured for webhook topic', [
                'topic' => $topic,
                'shop' => $shop->domain,
            ]);

            return;
        }

        $jobClass = $config['job'];
        $queue = $config['queue'] ?? config('shopify.webhooks.default_queue');

        // Dispatch job to specified queue (pass shop domain, not object)
        dispatch(new $jobClass($shop->domain, $webhookData))
            ->onQueue($queue);

        if (config('shopify.logging.log_webhooks')) {
            Log::info('Webhook dispatched to queue', [
                'topic' => $topic,
                'shop' => $shop->domain,
                'job' => $jobClass,
                'queue' => $queue,
            ]);
        }
    }
}
