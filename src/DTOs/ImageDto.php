<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDto;

/**
 * Represents an image resource.
 *
 * Based on Shopify's Image GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/Image
 */
class ImageDto extends BaseDto
{
    public function __construct(
        public ?string $id = null,
        public ?string $altText = null,
        public ?string $url = null,
        public ?int $width = null,
        public ?int $height = null,
        public ?string $thumbhash = null,
    ) {}
}
