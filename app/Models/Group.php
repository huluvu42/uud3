<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = [
        'name', 'backstage_day_1', 'backstage_day_2', 
        'backstage_day_3', 'backstage_day_4',
        'voucher_day_1', 'voucher_day_2', 
        'voucher_day_3', 'voucher_day_4', 
        'remarks', 'can_have_guests', 'year'
    ];

    protected $casts = [
        'backstage_day_1' => 'boolean',
        'backstage_day_2' => 'boolean',
        'backstage_day_3' => 'boolean',
        'backstage_day_4' => 'boolean',
        'voucher_day_1' => 'decimal:1',
        'voucher_day_2' => 'decimal:1',
        'voucher_day_3' => 'decimal:1',
        'voucher_day_4' => 'decimal:1',
        'can_have_guests' => 'boolean', 
        'year' => 'integer',
    ];

    public function subgroups()
    {
        return $this->hasMany(Subgroup::class);
    }

    public function persons()
    {
        return $this->hasMany(Person::class);
    }
}