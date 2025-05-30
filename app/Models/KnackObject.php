<?php
// app/Models/KnackObject.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnackObject extends Model
{
    protected $fillable = [
        'name', 
        'object_key', 
        'app_id', 
        'description', 
        'active'
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    // Scope für aktive Objects
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    // Scope für inaktive Objects
    public function scopeInactive($query)
    {
        return $query->where('active', false);
    }

    // Accessor für Display-Name
    public function getDisplayNameAttribute()
    {
        return $this->name . ' (' . $this->object_key . ')';
    }

    // Mutator für Object Key (automatisch lowercase)
    public function setObjectKeyAttribute($value)
    {
        $this->attributes['object_key'] = strtolower($value);
    }
}