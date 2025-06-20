<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Band;

class BandRegistrationStats extends Command
{
    protected $signature = 'registration:stats {--json : Output as JSON}';
    protected $description = 'Show band registration statistics';

    public function handle()
    {
        $stats = [
            'total_bands' => Band::count(),
            'with_manager_email' => Band::whereNotNull('manager_email')->count(),
            'tokens_generated' => Band::whereNotNull('registration_token')->count(),
            'completed' => Band::where('registration_completed', true)->count(),
            'pending' => Band::whereNotNull('registration_token')
                ->where('registration_completed', false)->count(),
            'expired' => Band::whereNotNull('registration_token')
                ->where('registration_token_expires_at', '<', now())
                ->where('registration_completed', false)->count(),
            'needs_reminder' => Band::whereNotNull('registration_token')
                ->where('registration_completed', false)
                ->where('registration_link_sent_at', '<', now()->subDays(7))
                ->whereNull('registration_reminder_sent_at')
                ->count(),
            'total_members' => Band::where('registration_completed', true)->sum('travel_party'),
            'avg_members_per_band' => Band::where('registration_completed', true)->avg('travel_party'),
            'completion_rate' => Band::whereNotNull('registration_token')->count() > 0
                ? round(Band::where('registration_completed', true)->count() / Band::whereNotNull('registration_token')->count() * 100, 1)
                : 0,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($stats, JSON_PRETTY_PRINT));
            return 0;
        }

        $this->info('Band Registration Statistics');
        $this->info('============================');
        $this->line("Total Bands: {$stats['total_bands']}");
        $this->line("With Manager Email: {$stats['with_manager_email']}");
        $this->line("Tokens Generated: {$stats['tokens_generated']}");
        $this->line("Completed: {$stats['completed']}");
        $this->line("Pending: {$stats['pending']}");
        $this->line("Expired: {$stats['expired']}");
        $this->line("Need Reminder: {$stats['needs_reminder']}");
        $this->line("Total Members: {$stats['total_members']}");
        $this->line("Avg Members/Band: " . round($stats['avg_members_per_band'] ?? 0, 1));
        $this->line("Completion Rate: {$stats['completion_rate']}%");

        return 0;
    }
}
