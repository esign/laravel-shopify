<?php

namespace Esign\LaravelShopify\Console\Commands;

use Esign\LaravelShopify\Models\Shop;
use Illuminate\Console\Command;

class CleanupUninstalledShopsCommand extends Command
{
    protected $signature = 'shopify:cleanup-uninstalled-shops 
                            {--days=90 : Number of days after uninstall to permanently delete}
                            {--force : Skip confirmation}';

    protected $description = 'Permanently delete shops that have been uninstalled for X days (GDPR compliance)';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoffDate = now()->subDays($days);

        $shops = Shop::onlyTrashed()
            ->where('deleted_at', '<=', $cutoffDate)
            ->get();

        if ($shops->isEmpty()) {
            $this->info('No shops to clean up.');

            return self::SUCCESS;
        }

        $this->table(
            ['Domain', 'Uninstalled At', 'Days Since Uninstall'],
            $shops->map(fn ($shop) => [
                $shop->domain,
                $shop->deleted_at->toDateTimeString(),
                $shop->deleted_at->diffInDays(now()),
            ])
        );

        if (! $this->option('force') && ! $this->confirm(
            "Permanently delete {$shops->count()} shops uninstalled over {$days} days ago?"
        )) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $shops->each->forceDelete();

        $this->info("Permanently deleted {$shops->count()} shops.");

        return self::SUCCESS;
    }
}
