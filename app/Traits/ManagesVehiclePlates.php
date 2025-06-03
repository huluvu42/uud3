<?php
// app/Traits/ManagesVehiclePlates.php

namespace App\Traits;

use App\Models\VehiclePlate;

trait ManagesVehiclePlates
{
    // Modal State
    public $showVehiclePlatesModal = false;
    public $selectedPersonForPlates = null;
    public $newLicensePlate = '';
    public $editingPlateId = null;

    // Modal öffnen
    public function showVehiclePlates($personId)
    {
        $this->selectedPersonForPlates = \App\Models\Person::with('vehiclePlates')->findOrFail($personId);
        $this->showVehiclePlatesModal = true;
        $this->resetPlateForm();
    }

    // Modal schließen
    public function closeVehiclePlatesModal()
    {
        $this->showVehiclePlatesModal = false;
        $this->selectedPersonForPlates = null;
        $this->resetPlateForm();
    }

    // Neues Kennzeichen hinzufügen
    public function addVehiclePlate()
    {
        $this->validate([
            'newLicensePlate' => 'required|string|max:20|regex:/^[A-Za-zÄÖÜäöüß0-9\s\-]+$/',
        ], [
            'newLicensePlate.required' => 'Kennzeichen ist erforderlich',
            'newLicensePlate.regex' => 'Kennzeichen enthält ungültige Zeichen (nur Buchstaben, Zahlen, Leerzeichen und Bindestriche erlaubt)',
            'newLicensePlate.max' => 'Kennzeichen ist zu lang (max. 20 Zeichen)',
        ]);

        try {
            // Formatiertes Kennzeichen
            $formattedPlate = strtoupper(trim($this->newLicensePlate));

            // Prüfen ob Kennzeichen bereits existiert
            $existingPlate = VehiclePlate::where('license_plate', $formattedPlate)
                ->where('person_id', $this->selectedPersonForPlates->id)
                ->first();

            if ($existingPlate) {
                session()->flash('error', 'Dieses Kennzeichen ist bereits für diese Person eingetragen!');
                return;
            }

            // Limit von 3 Kennzeichen pro Person prüfen
            $currentPlatesCount = $this->selectedPersonForPlates->vehiclePlates()->count();
            if ($currentPlatesCount >= 3) {
                session()->flash('error', 'Eine Person kann maximal 3 Kennzeichen haben!');
                return;
            }

            // Kennzeichen erstellen - Alternative Methode (funktioniert besser als create())
            $plate = new VehiclePlate();
            $plate->license_plate = $formattedPlate;
            $plate->person_id = $this->selectedPersonForPlates->id;
            $plate->save();

            $this->refreshSelectedPerson();
            $this->newLicensePlate = '';
            session()->flash('success', 'Kennzeichen wurde hinzugefügt!');
        } catch (\Exception $e) {
            session()->flash('error', 'Fehler beim Speichern: ' . $e->getMessage());
        }
    }

    // Kennzeichen bearbeiten vorbereiten
    public function editVehiclePlate($plateId)
    {
        $plate = VehiclePlate::findOrFail($plateId);
        $this->editingPlateId = $plateId;
        $this->newLicensePlate = $plate->license_plate;
    }

    // Kennzeichen aktualisieren
    public function updateVehiclePlate()
    {
        $this->validate([
            'newLicensePlate' => 'required|string|max:20|regex:/^[A-Za-zÄÖÜäöüß0-9\s\-]+$/',
        ], [
            'newLicensePlate.required' => 'Kennzeichen ist erforderlich',
            'newLicensePlate.regex' => 'Kennzeichen enthält ungültige Zeichen (nur Buchstaben, Zahlen, Leerzeichen und Bindestriche erlaubt)',
            'newLicensePlate.max' => 'Kennzeichen ist zu lang (max. 20 Zeichen)',
        ]);

        $plate = VehiclePlate::findOrFail($this->editingPlateId);

        // Prüfen ob neues Kennzeichen bereits existiert (außer das aktuelle)
        $existingPlate = VehiclePlate::where('license_plate', strtoupper(trim($this->newLicensePlate)))
            ->where('person_id', $this->selectedPersonForPlates->id)
            ->where('id', '!=', $this->editingPlateId)
            ->first();

        if ($existingPlate) {
            session()->flash('error', 'Dieses Kennzeichen ist bereits für diese Person eingetragen!');
            return;
        }

        $plate->update([
            'license_plate' => strtoupper(trim($this->newLicensePlate))
        ]);

        $this->refreshSelectedPerson();
        $this->resetPlateForm();
        session()->flash('success', 'Kennzeichen wurde aktualisiert!');
    }

    // Kennzeichen löschen
    public function deleteVehiclePlate($plateId)
    {
        $plate = VehiclePlate::findOrFail($plateId);
        $licensePlate = $plate->license_plate;
        $plate->delete();

        $this->refreshSelectedPerson();
        session()->flash('success', "Kennzeichen '{$licensePlate}' wurde entfernt!");
    }

    // Bearbeitung abbrechen
    public function cancelPlateEdit()
    {
        $this->resetPlateForm();
    }

    // Form zurücksetzen
    private function resetPlateForm()
    {
        $this->newLicensePlate = '';
        $this->editingPlateId = null;
    }

    // Person mit aktuellen Kennzeichen neu laden
    private function refreshSelectedPerson()
    {
        if ($this->selectedPersonForPlates) {
            $this->selectedPersonForPlates = \App\Models\Person::with('vehiclePlates')
                ->findOrFail($this->selectedPersonForPlates->id);
        }
    }
}
