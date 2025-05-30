<?php
// app/Models/Settings.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Settings extends Model
{
    protected $fillable = [
        'day_1_date', 'day_2_date', 'day_3_date', 'day_4_date',
        'wristband_color_day_1', 'wristband_color_day_2', 
        'wristband_color_day_3', 'wristband_color_day_4', 'year',
        'day_1_label', 'day_2_label', 'day_3_label', 'day_4_label',
        'voucher_label', 'backstage_label',
        'voucher_issuance_rule', 'voucher_output_mode'
    ];

    protected $casts = [
        'day_1_date' => 'date',
        'day_2_date' => 'date',
        'day_3_date' => 'date',
        'day_4_date' => 'date',
        'year' => 'integer',
    ];

    public static function current()
    {
        return static::where('year', now()->year)->first();
    }

    // Aktuellen Festival-Tag basierend auf Datum ermitteln
    public function getCurrentDay()
    {
        $today = Carbon::today();
        
        for ($day = 1; $day <= 4; $day++) {
            $dayField = "day_{$day}_date";
            if ($this->{$dayField} && $today->isSameDay($this->{$dayField})) {
                return $day;
            }
        }
        
        // Fallback: ersten verfügbaren Tag zurückgeben
        return 1;
    }

    // Datum für einen bestimmten Tag abrufen
    public function getDateForDay($day)
    {
        $dayField = "day_{$day}_date";
        return $this->{$dayField};
    }

    // Farbe für einen bestimmten Tag abrufen
    public function getColorForDay($day)
    {
        $colorField = "wristband_color_day_{$day}";
        return $this->{$colorField};
    }

    // Prüfen ob ein bestimmtes Datum ein Festival-Tag ist
    public function isFestivalDay($date)
    {
        $checkDate = Carbon::parse($date);
        
        for ($day = 1; $day <= 4; $day++) {
            $dayField = "day_{$day}_date";
            if ($this->{$dayField} && $checkDate->isSameDay($this->{$dayField})) {
                return $day;
            }
        }
        
        return false;
    }

    // Alle Festival-Tage als Array zurückgeben
    public function getAllFestivalDays()
    {
        $days = [];
        for ($day = 1; $day <= 4; $day++) {
            $dayField = "day_{$day}_date";
            if ($this->{$dayField}) {
                $days[$day] = $this->{$dayField};
            }
        }
        return $days;
    }

    // Nächsten Festival-Tag ermitteln
    public function getNextFestivalDay()
    {
        $today = Carbon::today();
        
        for ($day = 1; $day <= 4; $day++) {
            $dayField = "day_{$day}_date";
            if ($this->{$dayField} && Carbon::parse($this->{$dayField})->gte($today)) {
                return $day;
            }
        }
        
        return 1; // Fallback
    }

    public function getDayLabel($day)
    {
        return $this->{"day_{$day}_label"} ?? "Tag $day";
    }

    public function getVoucherLabel()
    {
        return $this->voucher_label ?? 'Voucher/Bons';
    }

    public function getBackstageLabel()
    {
        return $this->backstage_label ?? 'Backstage-Berechtigung';
    }

    public function canIssueVouchersForDay($requestedDay, $currentDay)
    {
        switch ($this->voucher_issuance_rule) {
            case 'current_day_only':
                return $requestedDay == $currentDay;
            case 'current_and_past':
                return $requestedDay <= $currentDay;
            case 'all_days':
                return true;
            default:
                return $requestedDay == $currentDay;
        }
    }

    public function isSingleVoucherMode()
    {
        return $this->voucher_output_mode === 'single';
    }
}