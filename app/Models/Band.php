<?php

// app/Models/Band.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Band extends Model
{
    protected $fillable = [
        'band_name', 'plays_day_1', 'plays_day_2', 
        'plays_day_3', 'plays_day_4', 'stage_id',
        'all_present', 'travel_costs', 'year'
    ];

    protected $casts = [
        'plays_day_1' => 'boolean',
        'plays_day_2' => 'boolean',
        'plays_day_3' => 'boolean',
        'plays_day_4' => 'boolean',
        'all_present' => 'boolean',
        'travel_costs' => 'decimal:2',
        'year' => 'integer',
    ];

    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }

    public function members()
    {
        return $this->hasMany(Person::class);
    }

    public function vehiclePlates()
    {
        return $this->hasMany(VehiclePlate::class);
    }

    public function updateAllPresentStatus()
    {
        $totalMembers = $this->members()->count();
        $presentMembers = $this->members()->where('present', true)->count();
        
        $this->update(['all_present' => $totalMembers > 0 && $totalMembers === $presentMembers]);
    }

    public function getPerformanceDaysAttribute()
    {
        $days = [];
        if ($this->plays_day_1) $days[] = 1;
        if ($this->plays_day_2) $days[] = 2;
        if ($this->plays_day_3) $days[] = 3;
        if ($this->plays_day_4) $days[] = 4;
        return $days;
    }
}

// app/Models/VehiclePlate.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehiclePlate extends Model
{
    protected $fillable = ['license_plate', 'band_id'];

    public function band()
    {
        return $this->belongsTo(Band::class);
    }
}