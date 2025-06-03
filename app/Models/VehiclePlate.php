<?php
// app/Models/VehiclePlate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehiclePlate extends Model
{
    protected $fillable = ['license_plate', 'band_id', 'person_id'];

    // Beziehung zu Band (für Rückwärtskompatibilität)
    public function band()
    {
        return $this->belongsTo(Band::class);
    }

    // NEU: Beziehung zu Person
    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    // Scope: Nur Nummernschilder von Personen
    public function scopeForPersons($query)
    {
        return $query->whereNotNull('person_id');
    }

    // Scope: Nur Nummernschilder von Bands (Legacy)
    public function scopeForBands($query)
    {
        return $query->whereNotNull('band_id')->whereNull('person_id');
    }

    // Hilfsmethode: Gehört zu Person oder Band?
    public function belongsToPerson()
    {
        return $this->person_id !== null;
    }

    public function belongsToBand()
    {
        return $this->band_id !== null && $this->person_id === null;
    }

    // Formatiertes Kennzeichen (falls gewünscht)
    public function getFormattedLicensePlateAttribute()
    {
        return strtoupper(str_replace([' ', '-'], '', $this->license_plate));
    }
}
