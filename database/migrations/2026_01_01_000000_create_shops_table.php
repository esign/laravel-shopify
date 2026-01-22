<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique(); // example.myshopify.com
            $table->text('access_token')->nullable(); // Encrypted via model cast, nullable during initial creation

            // Token refresh fields
            $table->string('access_token_expires_at')->nullable(); // ISO 8601 string format from Shopify library
            $table->text('refresh_token')->nullable(); // Encrypted like access_token
            $table->string('refresh_token_expires_at')->nullable(); // ISO 8601 string format

            // Tracking/debugging fields
            $table->timestamp('access_token_last_refreshed_at')->nullable();
            $table->integer('token_refresh_count')->default(0);

            $table->timestamp('installed_at')->nullable();
            $table->timestamp('uninstalled_at')->nullable(); // Business logic timestamp
            $table->json('metadata')->nullable(); // Flexible field for app-specific data
            $table->timestamps();
            $table->softDeletes(); // Laravel soft delete support (deleted_at)

            $table->index('domain');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
