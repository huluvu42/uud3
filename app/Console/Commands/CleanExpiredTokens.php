<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Band;

class CleanExpiredTokens extends Command
{
    protected $signature = 'registration:clean-expired {--dry-run : Show what would be cleaned without actually cleaning}';
    protected $description = 'Clean up expired registration tokens';

    public function handle()
    {
        $expiredBands = Band::whereNotNull('registration_token')
            ->where('registration_token_expires_at', '<', now())
            ->where('registration_completed', false);

        $count = $expiredBands->count();

        if ($count === 0) {
            $this->info('No expired tokens found.');
            return 0;
        }

        $this->info("Found {$count} expired tokens:");

        foreach ($expiredBands->get() as $band) {
            $this->line("- {$band->band_name} (expired: {$band->registration_token_expires_at})");
        }

        if ($this->option('dry-run')) {
            $this->info('[DRY RUN] Would clean these tokens');
            return 0;
        }

        $cleaned = $expiredBands->update([
            'registration_token' => null,
            'registration_token_expires_at' => null,
        ]);

        $this->info("Cleaned {$cleaned} expired tokens.");
        return 0;
    }
}
