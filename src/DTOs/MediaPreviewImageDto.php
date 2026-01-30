<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDto;
use Esign\LaravelShopify\Enums\MediaPreviewImageStatus;

/**
 * Represents the preview image for a media.
 *
 * Based on Shopify's MediaPreviewImage GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/MediaPreviewImage
 */
class MediaPreviewImageDto extends BaseDto
{
    public function __construct(
        public ?ImageDto $image = null,
        public ?MediaPreviewImageStatus $status = null,
    ) {}
}
