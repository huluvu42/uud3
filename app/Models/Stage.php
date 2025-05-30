<?php
// app/Models/Stage.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'presence_days',
        'guest_allowed',
        'vouchers_on_performance_day',
        'year'
    ];

    protected $casts = [
        'guest_allowed' => 'boolean',
        'vouchers_on_performance_day' => 'decimal:1',
        'year' => 'integer',
    ];

    public function bands()
    {
        return $this->hasMany(Band::class);
    }

    // Helper methods
    public function hasBackstageAllDays()
    {
        return $this->presence_days === 'all_days';
    }

    public function getVoucherAmount()
    {
        return $this->vouchers_on_performance_day;
    }
}