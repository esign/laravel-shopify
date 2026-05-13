<?php

namespace Esign\LaravelShopify\Tests\Unit\Jobs;

use Esign\LaravelShopify\Jobs\CustomersDataRequestJob;
use Esign\LaravelShopify\Support\ShopifyLogger;
use Esign\LaravelShopify\Tests\TestCase;

class CustomersDataRequestJobTest extends TestCase
{
    protected function tearDown(): void
    {
        ShopifyLogger::clearFake();
        parent::tearDown();
    }

    /** @test */
    public function it_logs_gdpr_customer_data_request()
    {
        $logger = ShopifyLogger::fake();

        $shop = $this->createShop(['domain' => 'test-shop.myshopify.com']);

        $job = new CustomersDataRequestJob(
            shopDomain: 'test-shop.myshopify.com',
            webhookData: [
                'customer' => [
                    'id' => 'gid://shopify/Customer/123456',
                    'email' => 'customer@example.com',
                ],
            ]
        );

        $job->handle();

        $logger->shouldHaveReceived('info')
            ->with('GDPR: Customer data request received', [
                'shop' => 'test-shop.myshopify.com',
                'customer_id' => 'gid://shopify/Customer/123456',
                'customer_email' => 'customer@example.com',
                'webhook_topic' => 'customers/data_request',
            ]);

        $logger->shouldHaveReceived('info')
            ->with('GDPR: Customer data request processed', [
                'shop' => 'test-shop.myshopify.com',
                'customer_id' => 'gid://shopify/Customer/123456',
            ]);
    }

    /** @test */
    public function it_handles_shop_not_found()
    {
        $logger = ShopifyLogger::fake();

        $job = new CustomersDataRequestJob(
            shopDomain: 'non-existent.myshopify.com',
            webhookData: [
                'customer' => [
                    'id' => 'gid://shopify/Customer/123',
                    'email' => 'test@example.com',
                ],
            ]
        );

        $job->handle();

        $this->assertTrue(true); // Should not throw exception

        $logger->shouldHaveReceived('info')
            ->once()
            ->with('GDPR: Customer data request received', \Mockery::any());

        $logger->shouldHaveReceived('warning')
            ->once()
            ->with('GDPR: Shop not found for customer data request', [
                'shop' => 'non-existent.myshopify.com',
                'customer_id' => 'gid://shopify/Customer/123',
            ]);
    }

    /** @test */
    public function it_handles_missing_customer_data()
    {
        $logger = ShopifyLogger::fake();

        $shop = $this->createShop(['domain' => 'test-shop.myshopify.com']);

        $job = new CustomersDataRequestJob(
            shopDomain: 'test-shop.myshopify.com',
            webhookData: [] // No customer data
        );

        $job->handle();

        $logger->shouldHaveReceived('info')
            ->with('GDPR: Customer data request received', [
                'shop' => 'test-shop.myshopify.com',
                'customer_id' => null,
                'customer_email' => null,
                'webhook_topic' => 'customers/data_request',
            ]);
    }

    /** @test */
    public function it_extracts_customer_id_and_email_from_webhook_data()
    {
        $logger = ShopifyLogger::fake();

        $shop = $this->createShop(['domain' => 'test-shop.myshopify.com']);

        $customerId = 'gid://shopify/Customer/789';
        $customerEmail = 'test@example.com';

        $job = new CustomersDataRequestJob(
            shopDomain: 'test-shop.myshopify.com',
            webhookData: [
                'customer' => [
                    'id' => $customerId,
                    'email' => $customerEmail,
                ],
                'orders_requested' => [123, 456],
            ]
        );

        $job->handle();

        $logger->shouldHaveReceived('info')
            ->with('GDPR: Customer data request received', \Mockery::on(function ($arg) use ($customerId, $customerEmail) {
                return $arg['customer_id'] === $customerId
                    && $arg['customer_email'] === $customerEmail;
            }));
    }

    /** @test */
    public function it_handles_partial_customer_data()
    {
        $logger = ShopifyLogger::fake();

        $shop = $this->createShop(['domain' => 'test-shop.myshopify.com']);

        // Only customer ID, no email
        $job = new CustomersDataRequestJob(
            shopDomain: 'test-shop.myshopify.com',
            webhookData: [
                'customer' => [
                    'id' => 'gid://shopify/Customer/999',
                ],
            ]
        );

        $job->handle();

        $logger->shouldHaveReceived('info')
            ->with('GDPR: Customer data request received', \Mockery::on(function ($arg) {
                return $arg['customer_id'] === 'gid://shopify/Customer/999'
                    && $arg['customer_email'] === null;
            }));
    }
}
