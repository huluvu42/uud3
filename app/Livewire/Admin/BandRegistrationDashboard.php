<?php

// ============================================================================
// app/Livewire/Admin/BandRegistrationDashboard.php
// Dashboard mit Statistiken und Übersicht
// ============================================================================

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Band;
use Illuminate\Support\Facades\DB;

class BandRegistrationDashboard extends Component
{
    public $stats = [];
    public $recentRegistrations = [];
    public $overdueRegistrations = [];
    public $chartData = [];

    public function mount()
    {
        $this->calculateDetailedStats();
        $this->loadRecentActivity();
        $this->prepareChartData();
    }

    private function calculateDetailedStats()
    {
        // Basis-Statistiken
        $totalBands = Band::count();
        $tokensGenerated = Band::whereNotNull('registration_token')->count();
        $completed = Band::where('registration_completed', true)->count();

        $this->stats = [
            'total_bands' => $totalBands,
            'with_manager_data' => Band::whereNotNull('manager_email')->count(),
            'tokens_generated' => $tokensGenerated,
            'links_sent' => Band::whereNotNull('registration_link_sent_at')->count(),
            'completed_total' => $completed,
            'completed_today' => Band::where('registration_completed', true)
                ->whereDate('updated_at', today())->count(),
            'completed_this_week' => Band::where('registration_completed', true)
                ->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
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
            'completion_rate' => $tokensGenerated > 0 ? round($completed / $tokensGenerated * 100, 1) : 0,
            'avg_members_per_band' => round(Band::where('registration_completed', true)->avg('travel_party') ?? 0, 1),
            'total_expected_members' => Band::where('registration_completed', true)->sum('travel_party') ?? 0,
            'total_vehicles' => DB::table('vehicle_plates')->count(),
        ];
    }

    private function loadRecentActivity()
    {
        $this->recentRegistrations = Band::where('registration_completed', true)
            ->with('stage')
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($band) {
                return [
                    'band_name' => $band->band_name,
                    'stage_name' => $band->stage->name ?? 'Keine Bühne',
                    'travel_party' => $band->travel_party,
                    'completed_at' => $band->updated_at,
                    'manager_name' => $band->manager_full_name,
                ];
            });

        $this->overdueRegistrations = Band::whereNotNull('registration_token')
            ->where('registration_completed', false)
            ->where('registration_link_sent_at', '<', now()->subDays(14))
            ->with('stage')
            ->orderBy('registration_link_sent_at')
            ->get()
            ->map(function ($band) {
                return [
                    'band_name' => $band->band_name,
                    'stage_name' => $band->stage->name ?? 'Keine Bühne',
                    'manager_name' => $band->manager_full_name,
                    'manager_email' => $band->manager_email,
                    'sent_at' => $band->registration_link_sent_at,
                    'days_overdue' => now()->diffInDays($band->registration_link_sent_at),
                ];
            });
    }

    private function prepareChartData()
    {
        // Registrierungen der letzten 30 Tage
        $this->chartData = Band::where('registration_completed', true)
            ->where('updated_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(updated_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'count' => $item->count,
                ];
            })
            ->toArray();
    }

    public function render()
    {
        return view('livewire.admin.band-registration-dashboard');
    }
}
