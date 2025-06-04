<?php
// app/Services/KnackApiService.php

namespace App\Services;

use App\Models\KnackObject;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;

class KnackApiService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'verify' => true
        ]);
    }

    /**
     * Validiert einen API Key
     */
    public function validateApiKey(string $apiKey): bool
    {
        // Basis-Validierung
        if (empty($apiKey) || strlen($apiKey) < 10) {
            return false;
        }

        // Knack API Keys haben normalerweise ein bestimmtes Format
        return preg_match('/^[a-zA-Z0-9\-_]{20,}$/', $apiKey);
    }

    /**
     * Testet die API-Verbindung für ein Knack Object
     */
    public function testConnection(KnackObject $knackObject): array
    {
        $apiKey = $knackObject->getApiKey();

        if (!$apiKey) {
            return ['success' => false, 'message' => 'Kein API Key gesetzt'];
        }

        if (!$knackObject->app_id) {
            return ['success' => false, 'message' => 'Keine App ID gesetzt'];
        }

        try {
            $response = $this->client->get("https://api.knack.com/v1/objects/{$knackObject->object_key}/records", [
                'headers' => [
                    'X-Knack-Application-Id' => $knackObject->app_id,
                    'X-Knack-REST-API-Key' => $apiKey,
                ],
                'query' => [
                    'page' => 1,
                    'rows_per_page' => 1 // Nur einen Datensatz zum Testen
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true);
                $totalRecords = $data['total_records'] ?? 0;

                return [
                    'success' => true,
                    'message' => "Verbindung erfolgreich! {$totalRecords} Datensätze gefunden."
                ];
            }
        } catch (ClientException $e) {
            $status = $e->getResponse()->getStatusCode();
            switch ($status) {
                case 401:
                    return ['success' => false, 'message' => 'Ungültiger API Key oder App ID'];
                case 404:
                    return ['success' => false, 'message' => 'Object Key nicht gefunden'];
                default:
                    return ['success' => false, 'message' => "API Fehler ({$status}): " . $e->getMessage()];
            }
        } catch (\Exception $e) {
            Log::error('Knack API connection test failed', [
                'knack_object_id' => $knackObject->id,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'message' => 'Verbindungsfehler: ' . $e->getMessage()];
        }

        return ['success' => false, 'message' => 'Unbekannter Fehler'];
    }

    /**
     * Holt alle Datensätze von einem Knack Object mit Pagination
     */
    public function getAllRecords(KnackObject $knackObject): array
    {
        $apiKey = $knackObject->getApiKey();

        if (!$apiKey || !$knackObject->app_id) {
            throw new \Exception('Knack Object ist nicht vollständig konfiguriert');
        }

        $allRecords = [];
        $page = 1;
        $rowsPerPage = 1000; // Maximum pro Request

        do {
            Log::info("Loading page $page from Knack API for object {$knackObject->name}");

            $response = $this->client->get("https://api.knack.com/v1/objects/{$knackObject->object_key}/records", [
                'headers' => [
                    'X-Knack-Application-Id' => $knackObject->app_id,
                    'X-Knack-REST-API-Key' => $apiKey,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'page' => $page,
                    'rows_per_page' => $rowsPerPage
                ]
            ]);

            if (!$response->getStatusCode() === 200) {
                throw new \Exception("API Request failed with status: " . $response->getStatusCode());
            }

            $data = json_decode($response->getBody(), true);
            $records = $data['records'] ?? [];
            $totalRecords = $data['total_records'] ?? 0;
            $totalPages = $data['total_pages'] ?? 1;

            Log::info("Page $page loaded: " . count($records) . " records, Total: $totalRecords, Pages: $totalPages");

            // Records zur Gesamtliste hinzufügen
            $allRecords = array_merge($allRecords, $records);
            $page++;
        } while ($page <= $totalPages && count($records) > 0);

        Log::info("Loaded total of " . count($allRecords) . " records from Knack API");

        return $allRecords;
    }

    /**
     * Holt die verfügbaren Felder eines Knack Objects
     */
    public function getAvailableFields(KnackObject $knackObject): array
    {
        $apiKey = $knackObject->getApiKey();

        if (!$apiKey || !$knackObject->app_id) {
            return [];
        }

        try {
            // Hole Object-Schema von Knack API
            $response = $this->client->get("https://api.knack.com/v1/objects/{$knackObject->object_key}", [
                'headers' => [
                    'X-Knack-Application-Id' => $knackObject->app_id,
                    'X-Knack-REST-API-Key' => $apiKey,
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true);
                $fields = $data['object']['fields'] ?? [];

                $fieldList = [];
                foreach ($fields as $field) {
                    $fieldList[] = [
                        'key' => $field['key'] ?? '',
                        'label' => $field['label'] ?? '',
                        'type' => $field['type'] ?? '',
                    ];
                }

                return $fieldList;
            }
        } catch (\Exception $e) {
            Log::error('Failed to get Knack object fields', [
                'knack_object_id' => $knackObject->id,
                'error' => $e->getMessage()
            ]);
        }

        return [];
    }

    /**
     * Behandelt API-Fehler und gibt benutzerfreundliche Nachrichten zurück
     */
    public function handleApiError(\Exception $e): string
    {
        if ($e instanceof ClientException) {
            $status = $e->getResponse()->getStatusCode();
            switch ($status) {
                case 401:
                    return 'Ungültiger API Key oder App ID';
                case 404:
                    return 'Object Key nicht gefunden';
                case 429:
                    return 'API Rate-Limit erreicht. Bitte versuchen Sie es später erneut.';
                default:
                    return "API Fehler ({$status}): " . $e->getMessage();
            }
        }

        return 'Verbindungsfehler: ' . $e->getMessage();
    }
}
