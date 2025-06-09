<?php

// app/Services/BandStatusService.php

namespace App\Services;

use App\Models\Band;
use App\Models\Settings;
use Carbon\Carbon;

class BandStatusService
{
    /**
     * Band-Status für bestimmten Tag berechnen
     */
    public function calculateBandStatus(Band $band, int $currentDay, Settings $settings): array
    {
        if (!$settings) {
            return ['status' => 'unknown', 'class' => 'bg-gray-100 text-gray-700', 'text' => 'Unbekannt'];
        }

        // Spielt die Band heute überhaupt?
        if (!$band->{"plays_day_$currentDay"}) {
            return ['status' => 'not_today', 'class' => 'bg-gray-100 text-gray-700', 'text' => 'Spielt nicht heute'];
        }

        // Auftrittszeit für heute holen
        $performanceTime = $band->getFormattedPerformanceTimeForDay($currentDay);
        if (!$performanceTime) {
            return ['status' => 'no_time', 'class' => 'bg-yellow-100 text-yellow-700', 'text' => 'Keine Auftrittszeit'];
        }

        // Späteste Ankunftszeit berechnen
        $arrivalMinutes = $settings->getLatestArrivalTimeMinutes();

        try {
            $performanceDateTime = Carbon::createFromFormat('H:i', $performanceTime);
            $latestArrivalTime = $performanceDateTime->subMinutes($arrivalMinutes);
            $now = Carbon::now();

            // Sind alle Mitglieder anwesend?
            $allPresent = $band->all_present;

            if ($allPresent) {
                return ['status' => 'ready', 'class' => 'bg-green-100 text-green-700 border-green-300', 'text' => '✓ Alle da'];
            } elseif ($now->gt($latestArrivalTime)) {
                return ['status' => 'late', 'class' => 'bg-red-100 text-red-700 border-red-300', 'text' => '⚠ Zu spät!'];
            } else {
                // Noch Zeit, aber nicht alle da
                $timeLeft = $now->diffInMinutes($latestArrivalTime);
                if ($timeLeft <= 15) {
                    return ['status' => 'warning', 'class' => 'bg-orange-100 text-orange-700 border-orange-300', 'text' => "⏰ {$timeLeft}min"];
                } else {
                    return ['status' => 'waiting', 'class' => 'bg-blue-100 text-blue-700', 'text' => 'Warten...'];
                }
            }
        } catch (\Exception $e) {
            return ['status' => 'error', 'class' => 'bg-yellow-100 text-yellow-700', 'text' => 'Zeitfehler'];
        }
    }
}
