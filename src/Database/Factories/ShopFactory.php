<?php

namespace Esign\LaravelShopify\Database\Factories;

use Esign\LaravelShopify\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShopFactory extends Factory
{
    protected $model = Shop::class;

    public function definition(): array
    {
        return [
            'domain' => $this->faker->unique()->slug() . '.myshopify.com',
            'access_token' => 'shpat_' . $this->faker->sha1(),
            'installed_at' => now(),
        ];
    }
}
