<?php

namespace Esign\LaravelShopify\Enums;

/**
 * The status of a product.
 */
enum ProductStatus: string
{
    case DRAFT = 'DRAFT';
    case PUBLISHED = 'PUBLISHED';
    case ARCHIVED = 'ARCHIVED';
}
