<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDto;

/**
 * The original source for an image.
 *
 * Based on Shopify's MediaImageOriginalSource GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/MediaImageOriginalSource
 */
class MediaImageOriginalSourceDto extends BaseDto
{
    public function __construct(
        public ?int $fileSize = null,
        public ?string $url = null,
    ) {}
}
