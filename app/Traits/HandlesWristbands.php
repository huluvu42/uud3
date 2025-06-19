<?php
// app/Traits/HandlesWristbands.php - NEUER TRAIT

namespace App\Traits;

trait HandlesWristbands
{
    /**
     * Bändchenfarbe für eine Person ermitteln
     */
    public function getWristbandColorForPerson($person)
    {
        if (!$this->settings) return null;

        $currentDay = $this->currentDay ?? $this->settings->getCurrentDay() ?? 1;

        if (!$person->{"backstage_day_{$currentDay}"}) {
            return null;
        }

        $hasAllRemainingDays = true;
        for ($day = $currentDay; $day <= 4; $day++) {
            if (!$person->{"backstage_day_$day"}) {
                $hasAllRemainingDays = false;
                break;
            }
        }

        if ($hasAllRemainingDays) {
            // NEU: Prüfe ob spezielle Farbe für "alle Tage" definiert ist
            if (method_exists($this->settings, 'getColorForAllDays')) {
                $allDaysColor = $this->settings->getColorForAllDays();
                if ($allDaysColor) {
                    return $allDaysColor;
                }
            }

            // Fallback: Verwende die alte Logik (Tag 4 Farbe)
            return $this->settings->getColorForDay(4);
        }

        return $this->settings->getColorForDay($currentDay);
    }

    /**
     * Prüfen ob Person irgendwelchen Backstage-Zugang hat
     */
    public function hasAnyBackstageAccess($person)
    {
        for ($day = 1; $day <= 4; $day++) {
            if ($person->{"backstage_day_$day"}) {
                return true;
            }
        }
        return false;
    }
}
