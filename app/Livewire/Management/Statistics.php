<?php
// app/Livewire/Management/Statistics.php

namespace App\Livewire\Management;

use Livewire\Component;
use App\Models\Person;
use App\Models\Group;
use App\Models\VoucherPurchase;
use App\Models\Settings;
use Illuminate\Support\Facades\DB;

class Statistics extends Component
{
    public $search = '';
    public $selectedGroupId = '';
    public $year;
    public $settings;

    // Filter properties
    public $showOnlyWithVouchers = false;
    public $showOnlyWithPurchases = false;

    // Results
    public $statistics = [];
    public $summary = [];

    public function mount()
    {
        $this->year = now()->year;
        $this->settings = Settings::where('year', $this->year)->first();
        $this->loadStatistics();
    }

    public function updatedSearch()
    {
        $this->loadStatistics();
    }

    public function updatedSelectedGroupId()
    {
        $this->loadStatistics();
    }

    public function updatedShowOnlyWithVouchers()
    {
        $this->loadStatistics();
    }

    public function updatedShowOnlyWithPurchases()
    {
        $this->loadStatistics();
    }

    public function loadStatistics()
    {
        $query = Person::with(['group', 'band'])
            ->where('year', $this->year)
            ->where('is_duplicate', false);

        // Name Filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('first_name', 'ILIKE', '%' . $this->search . '%')
                    ->orWhere('last_name', 'ILIKE', '%' . $this->search . '%');
            });
        }

        // Group Filter
        if ($this->selectedGroupId) {
            $query->where('group_id', $this->selectedGroupId);
        }

        $persons = $query->get();

        // Load voucher purchases for all persons
        $purchaseData = VoucherPurchase::whereIn('person_id', $persons->pluck('id'))
            ->selectRaw('person_id, day, SUM(amount) as total_purchased')
            ->groupBy('person_id', 'day')
            ->get()
            ->groupBy('person_id');

        $this->statistics = $persons->map(function ($person) use ($purchaseData) {
            $personPurchases = $purchaseData->get($person->id, collect());

            $data = [
                'person' => $person,
                'vouchers_issued' => [
                    'day_1' => $person->voucher_issued_day_1 ?? 0,
                    'day_2' => $person->voucher_issued_day_2 ?? 0,
                    'day_3' => $person->voucher_issued_day_3 ?? 0,
                    'day_4' => $person->voucher_issued_day_4 ?? 0,
                ],
                'vouchers_purchased' => [
                    'day_1' => $personPurchases->where('day', 1)->first()->total_purchased ?? 0,
                    'day_2' => $personPurchases->where('day', 2)->first()->total_purchased ?? 0,
                    'day_3' => $personPurchases->where('day', 3)->first()->total_purchased ?? 0,
                    'day_4' => $personPurchases->where('day', 4)->first()->total_purchased ?? 0,
                ],
            ];

            // Calculate totals
            $data['total_issued'] = array_sum($data['vouchers_issued']);
            $data['total_purchased'] = array_sum($data['vouchers_purchased']);

            return $data;
        });

        // Apply filters
        if ($this->showOnlyWithVouchers) {
            $this->statistics = $this->statistics->filter(function ($item) {
                return $item['total_issued'] > 0;
            });
        }

        if ($this->showOnlyWithPurchases) {
            $this->statistics = $this->statistics->filter(function ($item) {
                return $item['total_purchased'] > 0;
            });
        }

        // Sort by name
        $this->statistics = $this->statistics->sortBy(function ($item) {
            return $item['person']->first_name . ' ' . $item['person']->last_name;
        })->values();

        $this->calculateSummary();
    }

    private function calculateSummary()
    {
        $this->summary = [
            'total_persons' => $this->statistics->count(),
            'total_issued' => [
                'day_1' => $this->statistics->sum('vouchers_issued.day_1'),
                'day_2' => $this->statistics->sum('vouchers_issued.day_2'),
                'day_3' => $this->statistics->sum('vouchers_issued.day_3'),
                'day_4' => $this->statistics->sum('vouchers_issued.day_4'),
            ],
            'total_purchased' => [
                'day_1' => $this->statistics->sum('vouchers_purchased.day_1'),
                'day_2' => $this->statistics->sum('vouchers_purchased.day_2'),
                'day_3' => $this->statistics->sum('vouchers_purchased.day_3'),
                'day_4' => $this->statistics->sum('vouchers_purchased.day_4'),
            ],
        ];

        $this->summary['grand_total_issued'] = array_sum($this->summary['total_issued']);
        $this->summary['grand_total_purchased'] = array_sum($this->summary['total_purchased']);

        $this->summary['persons_with_vouchers'] = $this->statistics->filter(function ($item) {
            return $item['total_issued'] > 0;
        })->count();

        $this->summary['persons_with_purchases'] = $this->statistics->filter(function ($item) {
            return $item['total_purchased'] > 0;
        })->count();

        // Stage-basierte Statistiken für gekaufte Vouchers
        $this->calculateStageStatistics();
    }

    private function calculateStageStatistics()
    {
        // Lade Voucher-Käufe gruppiert nach Bühne und Tag
        $stagePurchases = VoucherPurchase::with('stage')
            ->whereHas('person', function ($query) {
                $query->where('year', $this->year);

                // Apply same filters as main statistics
                if ($this->search) {
                    $query->where(function ($q) {
                        $q->where('first_name', 'ILIKE', '%' . $this->search . '%')
                            ->orWhere('last_name', 'ILIKE', '%' . $this->search . '%');
                    });
                }

                if ($this->selectedGroupId) {
                    $query->where('group_id', $this->selectedGroupId);
                }
            })
            ->selectRaw('stage_id, day, SUM(amount) as total_purchased')
            ->groupBy('stage_id', 'day')
            ->get();

        // Gruppiere nach Bühne
        $stageGroups = $stagePurchases->groupBy('stage_id');

        $this->summary['stage_statistics'] = [];

        foreach ($stageGroups as $stageId => $purchases) {
            $stage = $purchases->first()->stage;
            if (!$stage) continue;

            $stageData = [
                'stage' => $stage,
                'total_purchased' => [
                    'day_1' => $purchases->where('day', 1)->first()->total_purchased ?? 0,
                    'day_2' => $purchases->where('day', 2)->first()->total_purchased ?? 0,
                    'day_3' => $purchases->where('day', 3)->first()->total_purchased ?? 0,
                    'day_4' => $purchases->where('day', 4)->first()->total_purchased ?? 0,
                ],
            ];

            $stageData['total'] = array_sum($stageData['total_purchased']);

            // Nur Bühnen mit Käufen anzeigen
            if ($stageData['total'] > 0) {
                $this->summary['stage_statistics'][] = $stageData;
            }
        }

        // Sortiere nach Gesamtkäufen (absteigend)
        usort($this->summary['stage_statistics'], function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });
    }

    public function exportCsv()
    {
        $filename = 'voucher_statistics_' . $this->year . '_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');

            // CSV Header
            $header = [
                'Name',
                'Gruppe',
                'Band',
                $this->settings->day_1_label . ' - Frei',
                $this->settings->day_1_label . ' - Gekauft',
                $this->settings->day_2_label . ' - Frei',
                $this->settings->day_2_label . ' - Gekauft',
                $this->settings->day_3_label . ' - Frei',
                $this->settings->day_3_label . ' - Gekauft',
                $this->settings->day_4_label . ' - Frei',
                $this->settings->day_4_label . ' - Gekauft',
                'Gesamt Frei',
                'Gesamt Gekauft',
            ];
            fputcsv($file, $header);

            // Data rows
            foreach ($this->statistics as $stat) {
                $row = [
                    $stat['person']->full_name,
                    $stat['person']->group->name ?? '',
                    $stat['person']->band->band_name ?? '',
                    $stat['vouchers_issued']['day_1'],
                    $stat['vouchers_purchased']['day_1'],
                    $stat['vouchers_issued']['day_2'],
                    $stat['vouchers_purchased']['day_2'],
                    $stat['vouchers_issued']['day_3'],
                    $stat['vouchers_purchased']['day_3'],
                    $stat['vouchers_issued']['day_4'],
                    $stat['vouchers_purchased']['day_4'],
                    $stat['total_issued'],
                    $stat['total_purchased'],
                ];
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $groups = Group::where('year', $this->year)
            ->orderBy('name')
            ->get();

        return view('livewire.management.statistics', [
            'groups' => $groups
        ]);
    }
}
