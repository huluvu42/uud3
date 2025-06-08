<?php
// app/Models/KnackObject.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class KnackObject extends Model
{
    protected $fillable = [
        'name',
        'object_key',
        'app_id',
        'encrypted_api_key',
        'description',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    // ===== API KEY MANAGEMENT =====

    /**
     * Setzt den API Key verschlüsselt
     */
    public function setApiKey(string $apiKey): void
    {
        if (!empty($apiKey)) {
            $this->encrypted_api_key = Crypt::encryptString($apiKey);
        } else {
            $this->encrypted_api_key = null;
        }
    }

    /**
     * Holt den entschlüsselten API Key
     */
    public function getApiKey(): ?string
    {
        if (empty($this->encrypted_api_key)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->encrypted_api_key);
        } catch (DecryptException $e) {
            Log::error('Knack Object API Key decryption failed: ' . $e->getMessage(), [
                'knack_object_id' => $this->id,
                'knack_object_name' => $this->name
            ]);
            return null;
        }
    }

    /**
     * Prüft ob ein API Key vorhanden ist
     */
    public function hasApiKey(): bool
    {
        return !empty($this->encrypted_api_key);
    }

    /**
     * Maskiert den API Key für die Anzeige
     */
    public function getMaskedApiKey(): string
    {
        $apiKey = $this->getApiKey();

        if (!$apiKey) {
            return 'Nicht gesetzt';
        }

        $length = strlen($apiKey);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($apiKey, 0, 4) . str_repeat('*', $length - 8) . substr($apiKey, -4);
    }

    // ===== SCOPES =====

    /**
     * Nur aktive Objects
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Nur Objects mit vollständiger Konfiguration
     */
    public function scopeComplete($query)
    {
        return $query->whereNotNull('app_id')
            ->whereNotNull('encrypted_api_key')
            ->where('active', true);
    }

    // ===== HELPER METHODS =====

    /**
     * Prüft ob das Object vollständig konfiguriert ist
     */
    public function isComplete(): bool
    {
        return !empty($this->app_id) && $this->hasApiKey() && $this->active;
    }

    // ===== ACCESSORS =====

    /**
     * Status Text für die Anzeige
     */
    public function getStatusTextAttribute(): string
    {
        if (!$this->active) {
            return 'Inaktiv';
        }

        if (!$this->app_id) {
            return 'App ID fehlt';
        }

        if (!$this->hasApiKey()) {
            return 'API Key fehlt';
        }

        return 'Bereit';
    }

    /**
     * CSS-Klasse für den Status
     */
    public function getStatusColorAttribute(): string
    {
        if (!$this->active) {
            return 'bg-gray-100 text-gray-800';
        }

        if (!$this->app_id || !$this->hasApiKey()) {
            return 'bg-yellow-100 text-yellow-800';
        }

        return 'bg-green-100 text-green-800';
    }
}
