<?php

namespace Ssbhattarai\MagicLink\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class CheckMagicLinkCommand extends Command
{
    protected $signature = 'magiclink:check';

    protected $description = 'Check if MagicLink package is properly installed';

    public function handle()
    {
        $this->info('Checking MagicLink package installation...');

        // Check if routes are registered
        $routes = collect(Route::getRoutes())->filter(function ($route) {
            return str_contains($route->getName() ?? '', 'magiclink');
        });

        if ($routes->count() > 0) {
            $this->info('Routes are registered:');
            foreach ($routes as $route) {
                $this->line("   {$route->methods()[0]} {$route->uri()} ({$route->getName()})");
            }
        } else {
            $this->error('No MagicLink routes found');
            $this->line('Try running: php artisan route:clear');
        }

        // Check config
        if (config('magiclink.link_expiration')) {
            $this->info('Configuration loaded');
        } else {
            $this->error('Configuration not loaded');
            $this->line('Try running: php artisan config:clear');
        }

        // Check user model
        $userModel = config('auth.providers.users.model', 'App\\Models\\User');
        if (class_exists($userModel)) {
            $this->info("User model found: {$userModel}");
        } else {
            $this->error("User model not found: {$userModel}");
        }

        // Check database table
        if (Schema::hasTable('passwordless_tokens')) {
            $this->info('Database table "passwordless_tokens" exists');

            // Check token count
            $tokenCount = DB::table('passwordless_tokens')->count();
            $expiredCount = DB::table('passwordless_tokens')
                ->where('expires_at', '<', now())
                ->count();

            $this->line("   Total tokens: {$tokenCount}");
            $this->line("   Expired tokens: {$expiredCount}");
        } else {
            $this->error('Database table "passwordless_tokens" not found');
            $this->line('Run: php artisan migrate');
        }

        // Check queue configuration
        if (config('queue.default') !== 'sync') {
            $this->info('Queue driver configured: '.config('queue.default'));
        } else {
            $this->warn('Using sync queue driver (emails sent immediately)');
            $this->line('Consider configuring a proper queue driver for production');
        }

        return Command::SUCCESS;
    }
}
