<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BandGuest extends Model
{
    protected $fillable = ['name', 'present', 'band_member_id'];

    protected $casts = [
        'present' => 'boolean',
    ];

    public function bandMember()
    {
        return $this->belongsTo(Person::class, 'band_member_id');
    }
}