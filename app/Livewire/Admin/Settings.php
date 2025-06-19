<?php
// app/Livewire/Admin/Settings.php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Settings as SettingsModel;

class Settings extends Component
{
    public $settings;

    // Settings fields
    public $day_1_date;
    public $day_2_date;
    public $day_3_date;
    public $day_4_date;
    public $wristband_color_day_1;
    public $wristband_color_day_2;
    public $wristband_color_day_3;
    public $wristband_color_day_4;
    public $wristband_color_day_all;
    public $year;
    public $latest_arrival_time_minutes = 60;

    // Voucher Settings
    public $voucher_issuance_rule = 'current_day_only';
    public $voucher_output_mode = 'all_available';
    public $voucher_purchase_mode = 'both';

    // Day Labels
    public $day_1_label = 'Tag 1';
    public $day_2_label = 'Tag 2';
    public $day_3_label = 'Tag 3';
    public $day_4_label = 'Tag 4';

    // Area Labels
    public $voucher_label = 'Voucher/Bons';
    public $backstage_label = 'Backstage-Berechtigung';

    public function mount()
    {
        $currentYear = now()->year;
        $this->year = $currentYear;

        $this->settings = SettingsModel::where('year', $currentYear)->first();

        if ($this->settings) {
            // Bestehende Felder
            $this->day_1_date = $this->settings->day_1_date?->format('Y-m-d');
            $this->day_2_date = $this->settings->day_2_date?->format('Y-m-d');
            $this->day_3_date = $this->settings->day_3_date?->format('Y-m-d');
            $this->day_4_date = $this->settings->day_4_date?->format('Y-m-d');

            $this->wristband_color_day_1 = $this->settings->wristband_color_day_1;
            $this->wristband_color_day_2 = $this->settings->wristband_color_day_2;
            $this->wristband_color_day_3 = $this->settings->wristband_color_day_3;
            $this->wristband_color_day_4 = $this->settings->wristband_color_day_4;
            $this->wristband_color_day_all = $this->settings->wristband_color_day_all;

            // Neue Ankunftszeit
            $this->latest_arrival_time_minutes = $this->settings->latest_arrival_time_minutes ?? 60;

            // Voucher Settings mit Fallback-Werten
            $this->voucher_issuance_rule = $this->settings->voucher_issuance_rule ?? 'current_day_only';
            $this->voucher_output_mode = $this->settings->voucher_output_mode ?? 'all_available';
            $this->voucher_purchase_mode = $this->settings->voucher_purchase_mode ?? 'both';

            // Day Labels mit Fallback-Werten
            $this->day_1_label = $this->settings->day_1_label ?? 'Tag 1';
            $this->day_2_label = $this->settings->day_2_label ?? 'Tag 2';
            $this->day_3_label = $this->settings->day_3_label ?? 'Tag 3';
            $this->day_4_label = $this->settings->day_4_label ?? 'Tag 4';

            $this->voucher_label = $this->settings->voucher_label ?? 'Voucher/Bons';
            $this->backstage_label = $this->settings->backstage_label ?? 'Backstage-Berechtigung';
        }
    }

    public function saveSettings()
    {
        $this->validate([
            'day_1_date' => 'required|date',
            'day_2_date' => 'required|date',
            'day_3_date' => 'required|date',
            'day_4_date' => 'required|date',
            'wristband_color_day_1' => 'required',
            'wristband_color_day_2' => 'required',
            'wristband_color_day_3' => 'required',
            'wristband_color_day_4' => 'required',
            'wristband_color_day_all' => 'nullable',
            'latest_arrival_time_minutes' => 'required|integer|min:1|max:480',
            'voucher_issuance_rule' => 'required|in:current_day_only,current_and_past,all_days',
            'voucher_output_mode' => 'required|in:single,all_available',
            'voucher_purchase_mode' => 'required|in:stage_only,person_only,both',
        ]);

        SettingsModel::updateOrCreate(
            ['year' => $this->year],
            [
                'day_1_date' => $this->day_1_date,
                'day_2_date' => $this->day_2_date,
                'day_3_date' => $this->day_3_date,
                'day_4_date' => $this->day_4_date,
                'wristband_color_day_1' => $this->wristband_color_day_1,
                'wristband_color_day_2' => $this->wristband_color_day_2,
                'wristband_color_day_3' => $this->wristband_color_day_3,
                'wristband_color_day_4' => $this->wristband_color_day_4,
                'wristband_color_day_all' => $this->wristband_color_day_all,
                'latest_arrival_time_minutes' => $this->latest_arrival_time_minutes,
                'voucher_issuance_rule' => $this->voucher_issuance_rule,
                'voucher_output_mode' => $this->voucher_output_mode,
                'voucher_purchase_mode' => $this->voucher_purchase_mode,
            ]
        );

        session()->flash('success', 'Einstellungen gespeichert!');
    }

    public function saveDayLabels()
    {
        $this->validate([
            'day_1_label' => 'nullable|string|max:50',
            'day_2_label' => 'nullable|string|max:50',
            'day_3_label' => 'nullable|string|max:50',
            'day_4_label' => 'nullable|string|max:50',
            'voucher_label' => 'nullable|string|max:100',
            'backstage_label' => 'nullable|string|max:100',
        ]);

        SettingsModel::updateOrCreate(
            ['year' => $this->year],
            [
                'day_1_label' => $this->day_1_label ?: 'Tag 1',
                'day_2_label' => $this->day_2_label ?: 'Tag 2',
                'day_3_label' => $this->day_3_label ?: 'Tag 3',
                'day_4_label' => $this->day_4_label ?: 'Tag 4',
                'voucher_label' => $this->voucher_label ?: 'Voucher/Bons',
                'backstage_label' => $this->backstage_label ?: 'Backstage-Berechtigung',
            ]
        );

        session()->flash('success', 'Tag-Labels gespeichert!');
    }

    // Computed property für formatierte Anzeige
    public function getFormattedArrivalTimeProperty()
    {
        $minutes = $this->latest_arrival_time_minutes;

        if ($minutes >= 60) {
            $hours = intval($minutes / 60);
            $remainingMinutes = $minutes % 60;

            if ($remainingMinutes > 0) {
                return $hours . 'h ' . $remainingMinutes . 'min';
            }
            return $hours . 'h';
        }

        return $minutes . 'min';
    }

    public function render()
    {
        return view('livewire.admin.settings');
    }
}
