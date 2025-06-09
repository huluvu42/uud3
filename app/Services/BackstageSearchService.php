<?php

// app/Services/BackstageSearchService.php

namespace App\Services;

use App\Models\Person;
use App\Models\Band;
use Illuminate\Support\Collection;

class BackstageSearchService
{
    /**
     * Suche nach Personen mit optimierten Queries
     */
    public function searchPersons(string $searchTerm, int $year, int $limit = 15): Collection
    {
        if (strlen($searchTerm) < 2) {
            return collect();
        }

        return Person::with([
            'band:id,band_name,stage_id',
            'band.stage:id,name',
            'group:id,name',
            'vehiclePlates:id,person_id,license_plate',
            'responsiblePerson:id,first_name,last_name',
            'responsibleFor:id,first_name,last_name,responsible_person_id'
        ])
            ->select([
                'id',
                'first_name',
                'last_name',
                'present',
                'band_id',
                'group_id',
                'responsible_person_id',
                'can_have_guests',
                'remarks',
                'year',
                'is_duplicate',
                'voucher_day_1',
                'voucher_day_2',
                'voucher_day_3',
                'voucher_day_4',
                'voucher_issued_day_1',
                'voucher_issued_day_2',
                'voucher_issued_day_3',
                'voucher_issued_day_4',
                'backstage_day_1',
                'backstage_day_2',
                'backstage_day_3',
                'backstage_day_4'
            ])
            ->where('year', $year)
            ->where('is_duplicate', false)
            ->where(function ($query) use ($searchTerm) {
                $query->whereRaw("CONCAT(first_name, ' ', last_name) ILIKE ?", ["%{$searchTerm}%"])
                    ->orWhere('first_name', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('last_name', 'ILIKE', "%{$searchTerm}%")
                    ->orWhereHas('band', fn($bq) => $bq->where('band_name', 'ILIKE', "%{$searchTerm}%"))
                    ->orWhereHas('vehiclePlates', fn($pq) => $pq->where('license_plate', 'ILIKE', "%{$searchTerm}%"));
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Suche nach Bands mit optimierten Queries
     */
    public function searchBands(string $searchTerm, int $year, int $limit = 10): Collection
    {
        if (strlen($searchTerm) < 2) {
            return collect();
        }

        return Band::with([
            'stage:id,name',
            'members' => function ($query) use ($year) {
                $query->select(['id', 'band_id', 'present'])
                    ->where('year', $year)
                    ->where('is_duplicate', false);
            }
        ])
            ->select([
                'id',
                'band_name',
                'stage_id',
                'all_present',
                'year',
                'plays_day_1',
                'plays_day_2',
                'plays_day_3',
                'plays_day_4',
                'performance_time_day_1',
                'performance_time_day_2',
                'performance_time_day_3',
                'performance_time_day_4'
            ])
            ->where('year', $year)
            ->where('band_name', 'ILIKE', "%{$searchTerm}%")
            ->orderBy('band_name')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Personen nach IDs in bestimmter Reihenfolge laden (für Cache)
     */
    public function getPersonsByIds(array $ids, int $year): Collection
    {
        if (empty($ids)) {
            return collect();
        }

        $persons = Person::with([
            'band:id,band_name,stage_id',
            'band.stage:id,name',
            'group:id,name',
            'vehiclePlates:id,person_id,license_plate',
            'responsiblePerson:id,first_name,last_name',
            'responsibleFor:id,first_name,last_name,responsible_person_id'
        ])
            ->where('year', $year)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        // Reihenfolge aus übergebenen IDs beibehalten
        return collect($ids)->map(function ($id) use ($persons) {
            return $persons->get($id);
        })->filter(); // Entfernt null-Werte
    }

    /**
     * Bands nach IDs in bestimmter Reihenfolge laden (für Cache)
     */
    public function getBandsByIds(array $ids, int $year): Collection
    {
        if (empty($ids)) {
            return collect();
        }

        $bands = Band::with([
            'stage:id,name',
            'members' => function ($query) use ($year) {
                $query->select(['id', 'band_id', 'present'])
                    ->where('year', $year)
                    ->where('is_duplicate', false);
            }
        ])
            ->where('year', $year)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        // Reihenfolge beibehalten
        return collect($ids)->map(function ($id) use ($bands) {
            return $bands->get($id);
        })->filter();
    }
}
