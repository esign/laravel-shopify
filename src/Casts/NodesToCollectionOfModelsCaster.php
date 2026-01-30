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
        if (!is_array($value)) {
            return collect();
        }

        // Support edges->nodes structure (GraphQL connection pattern)
        if (isset($value['edges']) && is_array($value['edges'])) {
            return collect($value['edges'])->map(function ($edge) {
                if (!isset($edge['node'])) {
                    return null;
                }
                return $this->model::from($edge['node']);
            })->filter();
        }

        // Support direct nodes structure
        if (isset($value['nodes']) && is_array($value['nodes'])) {
            return collect($value['nodes'])->map(function ($item) {
                return $this->model::from($item);
            });
        }

        return collect();
    }
}