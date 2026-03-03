<?php

namespace Esign\LaravelShopify\Console;

use Esign\LaravelShopify\Models\Shop;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class DecryptTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'shopify:decrypt-tokens
                            {--dry-run : Preview what would be decrypted without making changes}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     */
    protected $description = 'Decrypt encrypted access_token and refresh_token values in the database';

    /**
     * Statistics for the operation.
     */
    protected int $totalProcessed = 0;

    protected int $successfullyDecrypted = 0;

    protected int $alreadyPlainText = 0;

    protected int $failed = 0;

    protected array $failedShops = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->displayHeader();

        // Validate prerequisites
        if (! $this->validatePrerequisites()) {
            return self::FAILURE;
        }

        // Load shops with tokens
        $shops = $this->loadShops();

        if ($shops->isEmpty()) {
            $this->components->info('No shops with tokens found.');

            return self::SUCCESS;
        }

        // Analyze encryption status
        $encryptedShops = $this->analyzeShops($shops);

        if ($encryptedShops->isEmpty()) {
            $this->components->info('All tokens are already decrypted. Nothing to do.');

            return self::SUCCESS;
        }

        // Show preview
        $this->displayPreview($shops, $encryptedShops);

        if ($this->option('dry-run')) {
            $this->displayDryRunMessage($encryptedShops->count());

            return self::SUCCESS;
        }

        // Confirm with user (unless --force)
        if (! $this->option('force') && ! $this->confirmDecryption($encryptedShops->count())) {
            $this->components->info('Operation cancelled.');

            return self::SUCCESS;
        }

        // Perform decryption
        $this->decryptTokens($encryptedShops);

        // Display summary
        $this->displaySummary();

        return $this->failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Display the command header.
     */
    protected function displayHeader(): void
    {
        $title = $this->option('dry-run')
            ? 'Shopify Token Decryption (Dry Run)'
            : 'Shopify Token Decryption';

        $this->newLine();
        $this->components->twoColumnDetail('<fg=bright-blue>'.str_repeat('═', 70).'</>');
        $this->components->info($title);
        $this->components->twoColumnDetail('<fg=bright-blue>'.str_repeat('═', 70).'</>');
        $this->newLine();
    }

    /**
     * Validate prerequisites before running.
     */
    protected function validatePrerequisites(): bool
    {
        // Check APP_KEY is set
        if (empty(config('app.key'))) {
            $this->components->error('APP_KEY not set in environment');
            $this->newLine();
            $this->line('Cannot decrypt tokens without application key.');
            $this->line('Please ensure APP_KEY is configured in your .env file.');
            $this->newLine();

            return false;
        }

        // Check that Shop model doesn't have encrypted casts
        $shop = new Shop;
        $casts = $shop->getCasts();

        if (isset($casts['access_token']) && $casts['access_token'] === 'encrypted') {
            $this->components->error('Shop model still has encrypted cast for access_token');
            $this->newLine();
            $this->line('Please remove the encrypted casts from the Shop model first:');
            $this->line('  protected $casts = [');
            $this->line('    // Remove these lines:');
            $this->line('    // \'access_token\' => \'encrypted\',');
            $this->line('    // \'refresh_token\' => \'encrypted\',');
            $this->line('  ];');
            $this->newLine();

            return false;
        }

        if (isset($casts['refresh_token']) && $casts['refresh_token'] === 'encrypted') {
            $this->components->error('Shop model still has encrypted cast for refresh_token');
            $this->newLine();
            $this->line('Please remove the encrypted casts from the Shop model first.');
            $this->newLine();

            return false;
        }

        return true;
    }

    /**
     * Load shops that have tokens.
     */
    protected function loadShops()
    {
        $this->components->info('Analyzing shops table...');

        return DB::table('shops')
            ->select('id', 'domain', 'access_token', 'refresh_token')
            ->where(function ($query) {
                $query->whereNotNull('access_token')
                    ->orWhereNotNull('refresh_token');
            })
            ->get();
    }

    /**
     * Analyze which shops have encrypted tokens.
     */
    protected function analyzeShops($shops)
    {
        return $shops->filter(function ($shop) {
            return $this->isTokenEncrypted($shop->access_token)
                || $this->isTokenEncrypted($shop->refresh_token);
        });
    }

    /**
     * Check if a token is encrypted.
     */
    protected function isTokenEncrypted(?string $token): bool
    {
        if ($token === null) {
            return false;
        }

        // Shopify tokens start with 'shpat_', 'shpca_', etc.
        // If the token starts with these, it's plain text
        if (preg_match('/^shp[a-z]{2}_/', $token)) {
            return false;
        }

        // Laravel encrypted strings are typically base64 and contain JSON
        // Try to detect the Laravel encryption format
        try {
            $decoded = base64_decode($token, true);
            if ($decoded === false) {
                return false;
            }

            $payload = json_decode($decoded, true);

            // Laravel encrypted payload has 'iv', 'value', 'mac' keys
            return is_array($payload) && isset($payload['iv'], $payload['value'], $payload['mac']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Display preview of what will be decrypted.
     */
    protected function displayPreview($allShops, $encryptedShops): void
    {
        $this->newLine();
        $this->components->info("Found {$allShops->count()} shops with tokens");
        $this->components->info("Detected {$encryptedShops->count()} shops with encrypted tokens");

        $plainTextCount = $allShops->count() - $encryptedShops->count();
        if ($plainTextCount > 0) {
            $this->components->info("Detected {$plainTextCount} shops with plain text tokens (will skip)");
        }

        $this->newLine();
    }

    /**
     * Display dry-run message.
     */
    protected function displayDryRunMessage(int $count): void
    {
        $this->components->twoColumnDetail('<fg=yellow>Dry Run - No changes will be made</>');
        $this->newLine();
        $this->line("Would decrypt {$count} shop tokens");
        $this->newLine();
        $this->components->info('Run without --dry-run to execute decryption');
        $this->newLine();
    }

    /**
     * Confirm decryption with the user.
     */
    protected function confirmDecryption(int $count): bool
    {
        $this->components->warn('WARNING: This will decrypt tokens and store them as plain text');
        $this->components->warn('Ensure your database has proper security measures in place');
        $this->newLine();

        return $this->confirm("Do you want to decrypt {$count} shop tokens?", false);
    }

    /**
     * Decrypt tokens for the given shops.
     */
    protected function decryptTokens($shops): void
    {
        $this->newLine();
        $this->components->info('Decrypting tokens...');
        $this->newLine();

        $progressBar = $this->output->createProgressBar($shops->count());
        $progressBar->start();

        foreach ($shops as $shop) {
            $this->decryptShopTokens($shop);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
    }

    /**
     * Decrypt tokens for a single shop.
     */
    protected function decryptShopTokens($shop): void
    {
        $this->totalProcessed++;
        $updates = [];
        $hasError = false;

        // Decrypt access_token
        if ($shop->access_token !== null && $this->isTokenEncrypted($shop->access_token)) {
            try {
                $decrypted = $this->decryptToken($shop->access_token);
                $updates['access_token'] = $decrypted;
            } catch (\Exception $e) {
                $hasError = true;

                // Provide more diagnostic information
                $errorMsg = $e->getMessage();
                $tokenPreview = substr($shop->access_token, 0, 50).'...';
                $tokenLength = strlen($shop->access_token);

                $this->failedShops[] = [
                    'id' => $shop->id,
                    'domain' => $shop->domain,
                    'error' => "access_token: {$errorMsg} (length: {$tokenLength}, preview: {$tokenPreview})",
                ];
            }
        }

        // Decrypt refresh_token
        if ($shop->refresh_token !== null && $this->isTokenEncrypted($shop->refresh_token)) {
            try {
                $decrypted = $this->decryptToken($shop->refresh_token);
                $updates['refresh_token'] = $decrypted;
            } catch (\Exception $e) {
                $hasError = true;

                // Provide more diagnostic information
                $errorMsg = $e->getMessage();
                $tokenPreview = substr($shop->refresh_token, 0, 50).'...';
                $tokenLength = strlen($shop->refresh_token);

                $this->failedShops[] = [
                    'id' => $shop->id,
                    'domain' => $shop->domain,
                    'error' => "refresh_token: {$errorMsg} (length: {$tokenLength}, preview: {$tokenPreview})",
                ];
            }
        }

        // Update statistics and database
        if ($hasError) {
            $this->failed++;
        } elseif (! empty($updates)) {
            DB::table('shops')->where('id', $shop->id)->update($updates);
            $this->successfullyDecrypted++;
        } else {
            $this->alreadyPlainText++;
        }
    }

    /**
     * Decrypt a token handling both serialized and non-serialized values.
     */
    protected function decryptToken(string $encrypted): string
    {
        // Try standard decrypt first (handles serialized values)
        try {
            return decrypt($encrypted);
        } catch (\Exception $e) {
            // If unserialize fails, the value was encrypted without serialization
            // Use Crypt::decryptString() which doesn't attempt to unserialize
            if (str_contains($e->getMessage(), 'unserialize')) {
                return Crypt::decryptString($encrypted);
            }

            throw $e;
        }
    }

    /**
     * Display operation summary.
     */
    protected function displaySummary(): void
    {
        $this->components->twoColumnDetail('<fg=bright-blue>Decryption Summary</>');
        $this->newLine();

        $headers = ['Metric', 'Count'];
        $rows = [
            ['Total Shops Processed', $this->totalProcessed],
            ['Successfully Decrypted', $this->successfullyDecrypted],
            ['Already Plain Text', $this->alreadyPlainText],
            ['Failed', $this->failed],
        ];

        $this->table($headers, $rows);
        $this->newLine();

        // Show failed shops if any
        if ($this->failed > 0) {
            $this->components->warn("{$this->failed} shop(s) failed to decrypt:");
            foreach ($this->failedShops as $failedShop) {
                $this->line("  • Shop ID {$failedShop['id']} ({$failedShop['domain']}): {$failedShop['error']}");
            }
            $this->newLine();
        }

        if ($this->successfullyDecrypted > 0) {
            $this->components->info('Decryption complete!');
            $this->newLine();
            $this->line('Next steps:');
            $this->components->bulletList([
                'Run your test suite: php artisan test',
                'Verify API calls work correctly',
                'Check application logs for issues',
            ]);
            $this->newLine();
        }
    }
}
