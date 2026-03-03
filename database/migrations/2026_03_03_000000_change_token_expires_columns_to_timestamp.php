<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Changes access_token_expires_at and refresh_token_expires_at
     * from string (ISO 8601) to timestamp columns.
     */
    public function up(): void
    {
        // First, convert existing ISO 8601 strings to timestamps
        $shops = DB::table('shops')
            ->whereNotNull('access_token_expires_at')
            ->orWhereNotNull('refresh_token_expires_at')
            ->get();

        foreach ($shops as $shop) {
            $updates = [];

            // Convert access_token_expires_at if it exists and is a string
            if ($shop->access_token_expires_at !== null) {
                try {
                    $timestamp = \Carbon\Carbon::parse($shop->access_token_expires_at);
                    $updates['access_token_expires_at'] = $timestamp;
                } catch (\Exception $e) {
                    // If parsing fails, set to null
                    $updates['access_token_expires_at'] = null;
                }
            }

            // Convert refresh_token_expires_at if it exists and is a string
            if ($shop->refresh_token_expires_at !== null) {
                try {
                    $timestamp = \Carbon\Carbon::parse($shop->refresh_token_expires_at);
                    $updates['refresh_token_expires_at'] = $timestamp;
                } catch (\Exception $e) {
                    // If parsing fails, set to null
                    $updates['refresh_token_expires_at'] = null;
                }
            }

            if (! empty($updates)) {
                DB::table('shops')->where('id', $shop->id)->update($updates);
            }
        }

        // Now alter the column types
        Schema::table('shops', function (Blueprint $table) {
            $table->timestamp('access_token_expires_at')->nullable()->change();
            $table->timestamp('refresh_token_expires_at')->nullable()->change();
        });
    }
};
