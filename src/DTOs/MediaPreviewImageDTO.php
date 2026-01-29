<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDTO;
use Esign\LaravelShopify\Enums\MediaPreviewImageStatus;

/**
 * Represents the preview image for a media.
 *
 * Based on Shopify's MediaPreviewImage GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/MediaPreviewImage
 */
class MediaPreviewImageDTO extends BaseDTO
{
    public function __construct(
        public ?ImageDTO $image = null,
        public MediaPreviewImageStatus $status,
    ) {}
}
