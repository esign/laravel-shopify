<?php

namespace Esign\LaravelShopify\Tests\Feature;

use Esign\LaravelShopify\Tests\TestCase;
use Illuminate\Support\Facades\Queue;

class WebhookDispatchTest extends TestCase
{
    /** @test */
    public function it_receives_and_dispatches_webhook_with_valid_hmac()
    {
        Queue::fake();

        $shop = $this->createShop();
        $payload = json_encode(['id' => 123, 'reason' => 'test']);

        config(['shopify.webhooks.routes' => [
            'app/uninstalled' => [
                'job' => \Esign\LaravelShopify\Jobs\AppUninstalledJob::class,
                'queue' => 'webhooks',
            ],
        ]]);

        $hmac = $this->generateWebhookHmac($payload);

        $response = $this->postJson('/webhooks/app/uninstalled', json_decode($payload, true), [
            'X-Shopify-Shop-Domain' => $shop->domain,
            'X-Shopify-Hmac-SHA256' => $hmac,
        ]);

        $response->assertStatus(200);
        Queue::assertPushed(\Esign\LaravelShopify\Jobs\AppUninstalledJob::class);
    }

    /** @test */
    public function it_rejects_webhook_with_invalid_hmac()
    {
        $shop = $this->createShop();
        $payload = ['id' => 123];

        $response = $this->postJson('/webhooks/app/uninstalled', $payload, [
            'X-Shopify-Shop-Domain' => $shop->domain,
            'X-Shopify-Hmac-SHA256' => 'invalid_hmac',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_automatically_soft_deletes_shop_on_uninstall_webhook()
    {
        // Don't fake queue - we need the job to actually run

        $shop = $this->createShop();
        $this->assertTrue($shop->isInstalled());

        $payload = json_encode(['id' => $shop->id]);

        config(['shopify.webhooks.routes' => [
            'app/uninstalled' => [
                'job' => \Esign\LaravelShopify\Jobs\AppUninstalledJob::class,
                'queue' => 'sync', // Use sync queue to run immediately
            ],
        ]]);

        $hmac = $this->generateWebhookHmac($payload);

        $this->postJson('/webhooks/app/uninstalled', json_decode($payload, true), [
            'X-Shopify-Shop-Domain' => $shop->domain,
            'X-Shopify-Hmac-SHA256' => $hmac,
        ]);

        $shop = $shop->fresh();
        $this->assertTrue($shop->trashed());
    }
}
