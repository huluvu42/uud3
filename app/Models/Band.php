<?php
// app/Models/Band.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Band extends Model
{
    protected $fillable = [
        'band_name',
        'plays_day_1',
        'plays_day_2',
        'plays_day_3',
        'plays_day_4',
        'stage_id',
        'all_present',
        'travel_costs',
        'year',
        'performance_time', // Legacy field - kann später entfernt werden
        'performance_duration', // Legacy field - kann später entfernt werden
        'performance_time_day_1',
        'performance_time_day_2',
        'performance_time_day_3',
        'performance_time_day_4',
        'performance_duration_day_1',
        'performance_duration_day_2',
        'performance_duration_day_3',
        'performance_duration_day_4',
        'hotel',
        'comment',
        'travel_costs_comment',
        'travel_party',
        'manager_first_name',
        'manager_last_name',
        'manager_email',
        'manager_phone',
        'registration_token',
        'registration_token_expires_at',
        'registration_completed',
        'registration_link_sent_at',
        'registration_reminder_sent_at'
    ];

    protected $casts = [
        'plays_day_1' => 'boolean',
        'plays_day_2' => 'boolean',
        'plays_day_3' => 'boolean',
        'plays_day_4' => 'boolean',
        'all_present' => 'boolean',
        'travel_costs' => 'decimal:2',
        'year' => 'integer',
        'performance_time' => 'string', // Legacy
        'performance_duration' => 'integer', // Legacy
        'performance_time_day_1' => 'string',
        'performance_time_day_2' => 'string',
        'performance_time_day_3' => 'string',
        'performance_time_day_4' => 'string',
        'performance_duration_day_1' => 'integer',
        'performance_duration_day_2' => 'integer',
        'performance_duration_day_3' => 'integer',
        'performance_duration_day_4' => 'integer',
        'registration_token_expires_at' => 'datetime',
        'registration_completed' => 'boolean',
        'registration_link_sent_at' => 'datetime',
        'registration_reminder_sent_at' => 'datetime',
    ];

    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }

    public function members()
    {
        return $this->hasMany(Person::class);
    }

    public function vehiclePlates()
    {
        return $this->hasMany(VehiclePlate::class);
    }

    public function updateAllPresentStatus()
    {
        $totalMembers = $this->members()->count();
        $presentMembers = $this->members()->where('present', true)->count();

        $this->update(['all_present' => $totalMembers > 0 && $totalMembers === $presentMembers]);
    }

    public function getPerformanceDaysAttribute()
    {
        $days = [];
        if ($this->plays_day_1) $days[] = 1;
        if ($this->plays_day_2) $days[] = 2;
        if ($this->plays_day_3) $days[] = 3;
        if ($this->plays_day_4) $days[] = 4;
        return $days;
    }

    // Neue Accessors für Performance Times pro Tag
    public function getPerformanceTimeForDay($day)
    {
        $field = "performance_time_day_{$day}";
        return $this->$field;
    }

    public function getPerformanceDurationForDay($day)
    {
        $field = "performance_duration_day_{$day}";
        return $this->$field;
    }

    // Formatted Performance Times
    public function getFormattedPerformanceTimeForDay($day)
    {
        $time = $this->getPerformanceTimeForDay($day);
        if (!$time) return null;

        // Wenn es schon im HH:MM Format ist, direkt zurückgeben
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time;
        }

        // Wenn es im HH:MM:SS Format ist, kürzen
        if (preg_match('/^(\d{2}:\d{2}):\d{2}$/', $time, $matches)) {
            return $matches[1];
        }

        return $time;
    }

    // Formatted Performance Duration
    public function getFormattedPerformanceDurationForDay($day)
    {
        $duration = $this->getPerformanceDurationForDay($day);
        if (!$duration) return null;

        $hours = intval($duration / 60);
        $minutes = $duration % 60;

        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'min';
        }
        return $minutes . 'min';
    }

    // Legacy Accessors (für Backward Compatibility)
    public function getFormattedPerformanceTimeAttribute()
    {
        return $this->performance_time ? $this->performance_time : null;
    }

    public function getFormattedPerformanceDurationAttribute()
    {
        if (!$this->performance_duration) return null;

        $hours = intval($this->performance_duration / 60);
        $minutes = $this->performance_duration % 60;

        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'min';
        }
        return $minutes . 'min';
    }

    // Helper: Get all performance times as array
    public function getAllPerformanceTimes()
    {
        return [
            1 => $this->getFormattedPerformanceTimeForDay(1),
            2 => $this->getFormattedPerformanceTimeForDay(2),
            3 => $this->getFormattedPerformanceTimeForDay(3),
            4 => $this->getFormattedPerformanceTimeForDay(4),
        ];
    }

    // Helper: Get all performance durations as array
    public function getAllPerformanceDurations()
    {
        return [
            1 => $this->getFormattedPerformanceDurationForDay(1),
            2 => $this->getFormattedPerformanceDurationForDay(2),
            3 => $this->getFormattedPerformanceDurationForDay(3),
            4 => $this->getFormattedPerformanceDurationForDay(4),
        ];
    }

    // Token validieren
    public function isTokenValid($token)
    {
        return $this->registration_token === $token
            && $this->registration_token_expires_at > now()
            && !$this->registration_completed;
    }

    // Registration URL generieren
    public function getRegistrationUrlAttribute()
    {
        return route('band.register', ['token' => $this->registration_token]);
    }

    // Manager Vollname
    public function getManagerFullNameAttribute()
    {
        return trim($this->manager_first_name . ' ' . $this->manager_last_name);
    }

    // Prüfen ob Manager-Email vorhanden
    public function hasManagerContact()
    {
        return !empty($this->manager_email);
    }

    // Automatischen Email-Versand prüfen
    public function canSendRegistrationEmail()
    {
        return $this->hasManagerContact()
            && $this->registration_token
            && !$this->registration_completed;
    }

    // Reminder-Email erforderlich prüfen
    public function needsReminder()
    {
        return $this->registration_token
            && !$this->registration_completed
            && $this->registration_link_sent_at
            && $this->registration_link_sent_at->addDays(7)->isPast()
            && (!$this->registration_reminder_sent_at || $this->registration_reminder_sent_at->addDays(7)->isPast());
    }
}
