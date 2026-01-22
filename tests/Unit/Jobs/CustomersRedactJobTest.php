<?php

namespace Esign\LaravelShopify\Tests\Unit\Jobs;

use Esign\LaravelShopify\Jobs\CustomersRedactJob;
use Esign\LaravelShopify\Tests\TestCase;
use Illuminate\Support\Facades\Log;

class CustomersRedactJobTest extends TestCase
{
    /** @test */
    public function it_logs_gdpr_customer_redaction_request()
    {
        $shop = $this->createShop(['domain' => 'test-shop.myshopify.com']);

        Log::shouldReceive('info')
            ->once()
            ->with('GDPR: Customer redaction request received', [
                'shop' => 'test-shop.myshopify.com',
                'customer_id' => 'gid://shopify/Customer/123456',
                'customer_email' => 'customer@example.com',
                'orders_count' => 2,
                'webhook_topic' => 'customers/redact',
            ]);

        Log::shouldReceive('info')
            ->once()
            ->with('GDPR: Customer data redacted', [
                'shop' => 'test-shop.myshopify.com',
                'customer_id' => 'gid://shopify/Customer/123456',
            ]);

        $job = new CustomersRedactJob(
            shopDomain: 'test-shop.myshopify.com',
            webhookData: [
                'customer' => [
                    'id' => 'gid://shopify/Customer/123456',
                    'email' => 'customer@example.com',
                ],
                'orders_to_redact' => [100, 101],
            ]
        );

        $job->handle();
    }

    /** @test */
    public function it_handles_shop_not_found()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('GDPR: Customer redaction request received', \Mockery::any());

        Log::shouldReceive('warning')
            ->once()
            ->with('GDPR: Shop not found for customer redaction', [
                'shop' => 'non-existent.myshopify.com',
                'customer_id' => 'gid://shopify/Customer/123',
            ]);

        $job = new CustomersRedactJob(
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
    }

    /** @test */
    public function it_handles_empty_orders_to_redact()
    {
        $shop = $this->createShop(['domain' => 'test-shop.myshopify.com']);

        Log::shouldReceive('info')->twice();

        $job = new CustomersRedactJob(
            shopDomain: 'test-shop.myshopify.com',
            webhookData: [
                'customer' => [
                    'id' => 'gid://shopify/Customer/456',
                    'email' => 'test@example.com',
                ],
                'orders_to_redact' => [], // Empty orders array
            ]
        );

        $job->handle();

        Log::shouldHaveReceived('info')
            ->with('GDPR: Customer redaction request received', \Mockery::on(function ($arg) {
                return $arg['orders_count'] === 0;
            }));
    }

    /** @test */
    public function it_handles_missing_customer_data()
    {
        $shop = $this->createShop(['domain' => 'test-shop.myshopify.com']);

        Log::shouldReceive('info')->twice();

        $job = new CustomersRedactJob(
            shopDomain: 'test-shop.myshopify.com',
            webhookData: [] // No customer data
        );

        $job->handle();

        Log::shouldHaveReceived('info')
            ->with('GDPR: Customer redaction request received', [
                'shop' => 'test-shop.myshopify.com',
                'customer_id' => null,
                'customer_email' => null,
                'orders_count' => 0,
                'webhook_topic' => 'customers/redact',
            ]);
    }

    /** @test */
    public function it_extracts_customer_data_and_orders_from_webhook()
    {
        $shop = $this->createShop(['domain' => 'test-shop.myshopify.com']);

        $customerId = 'gid://shopify/Customer/789';
        $customerEmail = 'redact@example.com';
        $orders = [200, 201, 202];

        Log::shouldReceive('info')->twice();

        $job = new CustomersRedactJob(
            shopDomain: 'test-shop.myshopify.com',
            webhookData: [
                'customer' => [
                    'id' => $customerId,
                    'email' => $customerEmail,
                ],
                'orders_to_redact' => $orders,
            ]
        );

        $job->handle();

        Log::shouldHaveReceived('info')
            ->with('GDPR: Customer redaction request received', \Mockery::on(function ($arg) use ($customerId, $customerEmail) {
                return $arg['customer_id'] === $customerId
                    && $arg['customer_email'] === $customerEmail
                    && $arg['orders_count'] === 3;
            }));
    }

    /** @test */
    public function it_handles_missing_orders_to_redact_field()
    {
        $shop = $this->createShop(['domain' => 'test-shop.myshopify.com']);

        Log::shouldReceive('info')->twice();

        $job = new CustomersRedactJob(
            shopDomain: 'test-shop.myshopify.com',
            webhookData: [
                'customer' => [
                    'id' => 'gid://shopify/Customer/100',
                    'email' => 'test@example.com',
                ],
                // No orders_to_redact field
            ]
        );

        $job->handle();

        Log::shouldHaveReceived('info')
            ->with('GDPR: Customer redaction request received', \Mockery::on(function ($arg) {
                return $arg['orders_count'] === 0;
            }));
    }
}
