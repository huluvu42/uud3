<?php
// app/Models/Person.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    use HasFactory;

    protected $table = 'persons';

    protected $fillable = [
        'first_name', 'last_name', 'present', 
        'backstage_day_1', 'backstage_day_2', 'backstage_day_3', 'backstage_day_4',
        'voucher_day_1', 'voucher_day_2', 'voucher_day_3', 'voucher_day_4',
        'voucher_issued_day_1', 'voucher_issued_day_2', 'voucher_issued_day_3', 'voucher_issued_day_4',
        'remarks', 'group_id', 'subgroup_id', 'band_id', 'responsible_person_id', 'year', 'knack_id',
    ];
    

    protected $casts = [
        'present' => 'boolean',
        'backstage_day_1' => 'boolean',
        'backstage_day_2' => 'boolean', 
        'backstage_day_3' => 'boolean',
        'backstage_day_4' => 'boolean',
        'voucher_day_1' => 'decimal:1',
        'voucher_day_2' => 'decimal:1',
        'voucher_day_3' => 'decimal:1',
        'voucher_day_4' => 'decimal:1',
        'voucher_issued_day_1' => 'decimal:1',
        'voucher_issued_day_2' => 'decimal:1',
        'voucher_issued_day_3' => 'decimal:1',
        'voucher_issued_day_4' => 'decimal:1',
        'year' => 'integer',
    ];

    // Beziehungen
    public function band()
    {
        return $this->belongsTo(Band::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function subgroup()
    {
        return $this->belongsTo(Subgroup::class);
    }

    public function responsiblePerson()
    {
        return $this->belongsTo(Person::class, 'responsible_person_id');
    }

    // Gast-Beziehung: Ein Mitglied kann einen Gast haben
    public function guest()
    {
        return $this->hasOne(Person::class, 'responsible_person_id');
    }

    // Eine Person kann für mehrere Personen verantwortlich sein
    public function responsibleFor()
    {
        return $this->hasMany(Person::class, 'responsible_person_id');
    }

    // Ist diese Person ein Gast?
    public function isGuest()
    {
        return $this->responsible_person_id !== null;
    }

    // Haupt-Mitglied falls diese Person ein Gast ist
    public function hostMember()
    {
        return $this->belongsTo(Person::class, 'responsible_person_id');
    }

    // Nur echte Band-Mitglieder (keine Gäste)
    public function scopeBandMembers($query)
    {
        return $query->whereNull('responsible_person_id');
    }

    // Nur Gäste
    public function scopeGuests($query)
    {
        return $query->whereNotNull('responsible_person_id');
    }

    // Kann diese Person an einem bestimmten Tag einen Gast haben?
    public function canHaveGuest($day)
    {
        if (!$this->band || !$this->band->stage) return false;
        
        $performanceDay = $this->band->{"plays_day_$day"};
        return $performanceDay && $this->band->stage->guest_allowed;
    }

    // Verfügbare Gutscheine für einen bestimmten Tag
    public function getAvailableVouchersForDay($day)
    {
        $totalVouchers = $this->{"voucher_day_$day"};
        $issuedVouchers = $this->{"voucher_issued_day_$day"};
        return max(0, $totalVouchers - $issuedVouchers);
    }

    // Hat diese Person an einem bestimmten Tag Backstage-Zugang?
    public function hasBackstageAccess($day)
    {
        return $this->{"backstage_day_$day"};
    }

    // Gesamtanzahl verfügbarer Gutscheine
    public function getTotalAvailableVouchers()
    {
        return $this->getAvailableVouchersForDay(1) + 
               $this->getAvailableVouchersForDay(2) + 
               $this->getAvailableVouchersForDay(3) + 
               $this->getAvailableVouchersForDay(4);
    }

    // Vollständiger Name
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    // An welchen Tagen hat diese Person Backstage-Zugang?
    public function getBackstageDaysAttribute()
    {
        $days = [];
        for ($i = 1; $i <= 4; $i++) {
            if ($this->{"backstage_day_$i"}) {
                $days[] = $i;
            }
        }
        return $days;
    }

    // An welchen Tagen hat diese Person Gutscheine?
    public function getVoucherDaysAttribute()
    {
        $days = [];
        for ($i = 1; $i <= 4; $i++) {
            if ($this->{"voucher_day_$i"} > 0) {
                $days[] = $i;
            }
        }
        return $days;
    }

    // Gutschein ausgeben für einen bestimmten Tag
    public function issueVoucher($day, $amount = null)
    {
        $availableVouchers = $this->getAvailableVouchersForDay($day);
        $issueAmount = $amount ?? $availableVouchers;
        
        if ($issueAmount > $availableVouchers) {
            throw new \Exception("Nicht genügend Gutscheine verfügbar");
        }

        $currentIssued = $this->{"voucher_issued_day_$day"};
        $this->update([
            "voucher_issued_day_$day" => $currentIssued + $issueAmount
        ]);

        return $issueAmount;
    }

    // Gutschein-Status für einen Tag
    public function getVoucherStatusForDay($day)
    {
        $total = $this->{"voucher_day_$day"};
        $issued = $this->{"voucher_issued_day_$day"};
        $available = $total - $issued;

        return [
            'total' => $total,
            'issued' => $issued,
            'available' => $available,
            'fully_issued' => $available <= 0 && $total > 0
        ];
    }
}