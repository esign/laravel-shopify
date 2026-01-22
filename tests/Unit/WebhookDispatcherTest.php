<?php

namespace Esign\LaravelShopify\Tests\Unit;

use Esign\LaravelShopify\Tests\TestCase;
use Esign\LaravelShopify\Webhooks\WebhookDispatcher;
use Illuminate\Support\Facades\Queue;

class WebhookDispatcherTest extends TestCase
{
    private WebhookDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = new WebhookDispatcher;
        Queue::fake();
    }

    /** @test */
    public function it_dispatches_configured_webhook_to_job()
    {
        $shop = $this->createShop();
        $topic = 'orders/create';
        $payload = ['id' => 123, 'total' => 100.00];

        // Use the actual AppUninstalledJob for testing since it exists
        config(['shopify.webhooks.routes' => [
            'orders/create' => [
                'job' => \Esign\LaravelShopify\Jobs\AppUninstalledJob::class,
                'queue' => 'webhooks',
            ],
        ]]);

        $this->dispatcher->dispatch($topic, $shop, $payload);

        Queue::assertPushedOn('webhooks', \Esign\LaravelShopify\Jobs\AppUninstalledJob::class);
    }

    /** @test */
    public function it_uses_default_queue_when_not_specified()
    {
        $shop = $this->createShop();
        $topic = 'products/create';
        $payload = ['id' => 456];

        config([
            'shopify.webhooks.default_queue' => 'default',
            'shopify.webhooks.routes' => [
                'products/create' => [
                    'job' => \Esign\LaravelShopify\Jobs\CustomersDataRequestJob::class,
                    // No queue specified
                ],
            ],
        ]);

        $this->dispatcher->dispatch($topic, $shop, $payload);

        Queue::assertPushedOn('default', \Esign\LaravelShopify\Jobs\CustomersDataRequestJob::class);
    }

    /** @test */
    public function it_does_not_dispatch_when_topic_not_configured()
    {
        $shop = $this->createShop();
        $topic = 'unknown/topic';
        $payload = [];

        config(['shopify.webhooks.routes' => []]);

        $this->dispatcher->dispatch($topic, $shop, $payload);

        Queue::assertNothingPushed();
    }

    /** @test */
    public function it_passes_shop_and_payload_to_job()
    {
        $shop = $this->createShop();
        $topic = 'app/uninstalled';
        $payload = ['id' => 789, 'reason' => 'test'];

        config(['shopify.webhooks.routes' => [
            'app/uninstalled' => [
                'job' => \Esign\LaravelShopify\Jobs\AppUninstalledJob::class,
                'queue' => 'webhooks',
            ],
        ]]);

        $this->dispatcher->dispatch($topic, $shop, $payload);

        Queue::assertPushed(\Esign\LaravelShopify\Jobs\AppUninstalledJob::class, function ($job) use ($shop, $payload) {
            return $job->shopDomain === $shop->domain
                && $job->webhookData === $payload;
        });
    }
}
