<?php

namespace Esign\LaravelShopify\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class MakeWebhookCommand extends GeneratorCommand
{
    /**
     * The console command name.
     */
    protected $name = 'shopify:make-webhook';

    /**
     * The console command description.
     */
    protected $description = 'Create a new Shopify webhook job class';

    /**
     * The type of class being generated.
     */
    protected $type = 'Webhook Job';

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return __DIR__.'/stubs/webhook-job.stub';
    }

    /**
     * Get the default namespace for the class.
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Jobs\Shopify';
    }

    /**
     * Build the class with the given name.
     */
    protected function buildClass($name): string
    {
        $stub = parent::buildClass($name);

        // Replace the topic placeholder
        $topic = $this->option('topic') ?: 'webhook/topic';

        return str_replace('{{ topic }}', $topic, $stub);
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            ['topic', 't', InputOption::VALUE_REQUIRED, 'The Shopify webhook topic (e.g., orders/create)'],
        ];
    }

    /**
     * Execute the console command.
     */
    public function handle(): ?int
    {
        // Validate topic format if provided
        if ($topic = $this->option('topic')) {
            if (! $this->isValidTopicFormat($topic)) {
                $this->error("Invalid webhook topic format. Expected format: 'resource/action' (e.g., 'orders/create')");

                return self::FAILURE;
            }
        }

        $result = parent::handle();

        // Provide helpful next steps
        $this->newLine();
        $this->components->info('Next steps:');
        $this->components->bulletList([
            'Implement webhook logic in the handle() method',
            'Register the webhook in your shopify.app.toml file',
            'Add the job to config/shopify.php webhook routes',
        ]);

        $this->newLine();

        $topic = $this->option('topic') ?: 'your/topic';
        $className = $this->qualifyClass($this->getNameInput());

        $this->components->twoColumnDetail('<fg=gray>shopify.app.toml</>');
        $this->line('  <fg=gray>[[webhooks.subscriptions]]</>');
        $this->line("  <fg=gray>uri = \"/webhooks/$topic\"</>");
        $this->line("  <fg=gray>topics = [\"$topic\"]</>");

        $this->newLine();

        $this->components->twoColumnDetail('<fg=gray>config/shopify.php</>');
        $this->line("  <fg=gray>'$topic' => [</>");
        $this->line("      <fg=gray>'job' => \\$className::class,</>");
        $this->line("      <fg=gray>'queue' => 'webhooks',</>");
        $this->line('  <fg=gray>],</>');

        return $result;
    }

    /**
     * Validate the webhook topic format.
     */
    protected function isValidTopicFormat(string $topic): bool
    {
        // Shopify webhook topics follow the pattern: resource/action
        // Examples: orders/create, products/update, app/uninstalled
        return preg_match('/^[a-z_]+\/[a-z_]+$/', $topic) === 1;
    }
}
