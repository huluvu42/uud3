<?php
// app/Models/User.php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'username',
        'password',
        'first_name',
        'last_name',
        'is_admin',
        'can_reset_changes',
        'can_manage' // Neues Feld hinzugefügt
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'is_admin' => 'boolean',
        'can_reset_changes' => 'boolean',
        'can_manage' => 'boolean', // Neues Cast hinzugefügt
    ];

    public function changeLogs()
    {
        return $this->hasMany(ChangeLog::class);
    }

    public function voucherPurchases()
    {
        return $this->hasMany(VoucherPurchase::class);
    }

    /**
     * Prüft ob dieser Benutzer der geschützte Admin-Benutzer ist
     */
    public function isProtectedAdmin(): bool
    {
        return $this->username === 'admin' && $this->is_admin;
    }

    /**
     * Prüft ob dieser Benutzer gelöscht werden kann
     */
    public function canBeDeleted(): bool
    {
        // Admin-Benutzer kann nicht gelöscht werden
        if ($this->isProtectedAdmin()) {
            return false;
        }

        return true;
    }

    /**
     * Überschreibt die delete Methode um Admin zu schützen
     */
    public function delete()
    {
        if (!$this->canBeDeleted()) {
            throw new \Exception('Der Admin-Benutzer kann nicht gelöscht werden.');
        }

        return parent::delete();
    }

    /**
     * Scope für alle löschbaren Benutzer
     */
    public function scopeDeletable($query)
    {
        return $query->where(function ($q) {
            $q->where('username', '!=', 'admin')
                ->orWhere('is_admin', false);
        });
    }

    /**
     * Gibt den Vollnamen zurück
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Prüft ob der Benutzer spezielle Berechtigungen hat
     */
    public function hasSpecialPermissions(): bool
    {
        return $this->is_admin || $this->can_reset_changes || $this->can_manage;
    }

    /**
     * Prüft ob der Benutzer Verwaltungsrechte hat (Admin oder explizit berechtigt)
     */
    public function canManage(): bool
    {
        return $this->is_admin || $this->can_manage;
    }
}
