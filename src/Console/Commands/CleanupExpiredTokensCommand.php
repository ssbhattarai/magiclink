<?php

namespace Ssbhattarai\MagicLink\Console\Commands;

use Illuminate\Console\Command;
use Ssbhattarai\MagicLink\Services\MagicLinkService;

class CleanupExpiredTokensCommand extends Command
{
    protected $signature = 'magiclink:cleanup';

    protected $description = 'Clean up expired magic link tokens';

    protected $magicLinkService;

    public function __construct(MagicLinkService $magicLinkService)
    {
        parent::__construct();
        $this->magicLinkService = $magicLinkService;
    }

    public function handle()
    {
        $this->info('Cleaning up expired magic link tokens...');

        $deletedCount = $this->magicLinkService->cleanupExpiredTokens();

        $this->info("✅ Cleaned up {$deletedCount} expired tokens.");

        return Command::SUCCESS;
    }
}
