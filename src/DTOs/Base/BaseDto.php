<?php

namespace Esign\LaravelShopify\DTOs\Base;

use Spatie\LaravelData\Data;

/**
 * Base Dto class for all Shopify DTOs.
 *
 * This abstract class provides a foundation for all Data Transfer Objects
 * in the package. It extends Spatie's Data class to provide type safety,
 * validation, and extensibility features.
 *
 * All DTOs in this package can be extended in individual Shopify apps
 * to add custom properties or override methods as needed.
 */
abstract class BaseDto extends Data
{
   // 
}
