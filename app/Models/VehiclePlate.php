<?php
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