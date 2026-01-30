<?php

namespace Esign\LaravelShopify\Casts;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

class NodesToCollectionOfModelsCaster implements Cast
{
    public function __construct(protected string $model) {}

    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): Collection
    {
        return collect($value['nodes'])->map(function ($item) {
            return $this->model::from($item);
        });
    }
}