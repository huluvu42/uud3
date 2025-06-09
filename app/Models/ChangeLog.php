<?php
// app/Models/ChangeLog.php (KORRIGIERT)
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChangeLog extends Model
{
    protected $fillable = [
        'table_name',
        'record_id',
        'field_name',
        'old_value',
        'new_value',
        'action',
        'user_id'
    ];

    // Verwende normale Laravel timestamps
    public $timestamps = true;

    // Aber deaktiviere updated_at (wird nicht benötigt)
    const UPDATED_AT = null;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function logChange($model, $field, $oldValue, $newValue, $action = 'update')
    {
        static::create([
            'table_name' => $model->getTable(),
            'record_id' => $model->id,
            'field_name' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'action' => $action,
            'user_id' => auth()->id(),
            // created_at wird automatisch gesetzt!
        ]);
    }
}
