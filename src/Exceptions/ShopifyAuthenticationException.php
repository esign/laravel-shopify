<?php

namespace Esign\LaravelShopify\Exceptions;

use Exception;

/**
 * Exception thrown when Shopify authentication fails.
 *
 * This exception provides context about the authentication failure
 * including the request type, shop domain, and failure reason.
 */
class ShopifyAuthenticationException extends Exception
{
    /**
     * The type of request that failed authentication.
     *
     * Possible values: embedded-app, webhook, app-proxy, admin-ui-extension,
     * pos-ui-extension, checkout-ui-extension, customer-account-ui-extension, flow-action
     */
    protected string $requestType;

    /**
     * The shop domain that failed authentication (if available).
     */
    protected ?string $shopDomain;

    /**
     * The reason for authentication failure.
     */
    protected string $reason;

    /**
     * Create a new authentication exception instance.
     */
    public function __construct(
        string $requestType,
        string $reason,
        ?string $shopDomain = null,
        ?\Throwable $previous = null
    ) {
        $this->requestType = $requestType;
        $this->reason = $reason;
        $this->shopDomain = $shopDomain;

        $message = $this->buildMessage();

        parent::__construct($message, 0, $previous);
    }

    /**
     * Build the exception message based on context.
     */
    protected function buildMessage(): string
    {
        $parts = [
            'Shopify authentication failed',
            "Type: {$this->requestType}",
            "Reason: {$this->reason}",
        ];

        if ($this->shopDomain) {
            $parts[] = "Shop: {$this->shopDomain}";
        }

        return implode(' | ', $parts);
    }

    /**
     * Get the request type.
     */
    public function getRequestType(): string
    {
        return $this->requestType;
    }

    /**
     * Get the shop domain.
     */
    public function getShopDomain(): ?string
    {
        return $this->shopDomain;
    }

    /**
     * Get the failure reason.
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Check if this is an embedded app request.
     */
    public function isEmbeddedAppRequest(): bool
    {
        return $this->requestType === 'embedded-app';
    }

    /**
     * Check if this is a webhook request.
     */
    public function isWebhookRequest(): bool
    {
        return $this->requestType === 'webhook';
    }

    /**
     * Check if this is an app proxy request.
     */
    public function isAppProxyRequest(): bool
    {
        return $this->requestType === 'app-proxy';
    }

    /**
     * Check if this is a UI extension request.
     */
    public function isUIExtensionRequest(): bool
    {
        return in_array($this->requestType, [
            'admin-ui-extension',
            'pos-ui-extension',
            'checkout-ui-extension',
            'customer-account-ui-extension',
        ]);
    }
}
