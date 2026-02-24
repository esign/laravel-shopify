<?php

namespace Esign\LaravelShopify\Inputs;

/**
 * A collection of metafield inputs.
 *
 * This is a convenience type for passing arrays of MetafieldInput
 * to mutations that accept multiple metafields.
 */
class MetafieldsInput
{
    /**
     * @param array<MetafieldInput> $metafields
     */
    public function __construct(
        public array $metafields = [],
    ) {}
}
