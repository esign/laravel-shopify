<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDTO;

/**
 * The original source for an image.
 *
 * Based on Shopify's MediaImageOriginalSource GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/MediaImageOriginalSource
 */
class MediaImageOriginalSourceDTO extends BaseDTO
{
    public function __construct(
        public ?int $fileSize = null,
        public ?string $url = null,
    ) {}
}
