<?php
// app/Models/User.php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'username', 'password', 'first_name', 'last_name', 
        'is_admin', 'can_reset_changes'
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'is_admin' => 'boolean',
        'can_reset_changes' => 'boolean',
    ];

    public function changeLogs()
    {
        return $this->hasMany(ChangeLog::class);
    }

    public function voucherPurchases()
    {
        return $this->hasMany(VoucherPurchase::class);
    }
}