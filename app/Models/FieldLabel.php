<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldLabel extends Model
{
    protected $fillable = ['field_key', 'label'];

    public static function getLabel($key, $default = null)
    {
        return static::where('field_key', $key)->value('label') ?? $default ?? $key;
    }
}