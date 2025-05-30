<?php
// app/Livewire/Admin/Settings.php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Settings as SettingsModel;
use App\Models\FieldLabel;

class Settings extends Component
{
    public $settings;
    public $fieldLabels;

    // Settings fields
    public $day_1_date;
    public $day_2_date;
    public $day_3_date;
    public $day_4_date;
    public $wristband_color_day_1;
    public $wristband_color_day_2;
    public $wristband_color_day_3;
    public $wristband_color_day_4;
    public $year;

    // Voucher Settings
    public $voucher_issuance_rule = 'current_day_only';
    public $voucher_output_mode = 'all_available';

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

            // Neue Felder mit Fallback-Werten
            $this->voucher_issuance_rule = $this->settings->voucher_issuance_rule ?? 'current_day_only';
            $this->voucher_output_mode = $this->settings->voucher_output_mode ?? 'all_available';

            $this->day_1_label = $this->settings->day_1_label ?? 'Tag 1';
            $this->day_2_label = $this->settings->day_2_label ?? 'Tag 2';
            $this->day_3_label = $this->settings->day_3_label ?? 'Tag 3';
            $this->day_4_label = $this->settings->day_4_label ?? 'Tag 4';

            $this->voucher_label = $this->settings->voucher_label ?? 'Voucher/Bons';
            $this->backstage_label = $this->settings->backstage_label ?? 'Backstage-Berechtigung';
        }

        $this->fieldLabels = FieldLabel::all()->pluck('label', 'field_key')->toArray();
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
            'voucher_issuance_rule' => 'required|in:current_day_only,current_and_past,all_days',
            'voucher_output_mode' => 'required|in:single,all_available',
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
                'voucher_issuance_rule' => $this->voucher_issuance_rule,
                'voucher_output_mode' => $this->voucher_output_mode,
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

    public function saveLabels()
    {
        // Field Labels werden über updateFieldLabel() gespeichert
        session()->flash('success', 'Feld-Labels gespeichert!');
    }

    public function updateFieldLabel($fieldKey, $label)
    {
        FieldLabel::updateOrCreate(
            ['field_key' => $fieldKey],
            ['label' => $label]
        );
        
        // Aktualisiere lokales Array
        $this->fieldLabels[$fieldKey] = $label;
    }

    public function render()
    {
        return view('livewire.admin.settings');
    }
}