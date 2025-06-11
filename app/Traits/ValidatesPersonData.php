<?php

namespace App\Traits;

// Neue Trait: app/Traits/ValidatesPersonData.php
trait ValidatesPersonData
{
    protected function getPersonValidationRules(): array
    {
        return [
            'first_name' => 'required|string|max:255|min:2',
            'last_name' => 'required|string|max:255|min:2',
            'group_id' => 'nullable|exists:groups,id',
            'subgroup_id' => 'nullable|exists:subgroups,id',
            'band_id' => 'nullable|exists:bands,id',
            'responsible_person_id' => 'nullable|exists:persons,id',
            'voucher_day_1' => 'nullable|numeric|between:0,999.9',
            'voucher_day_2' => 'nullable|numeric|between:0,999.9',
            'voucher_day_3' => 'nullable|numeric|between:0,999.9',
            'voucher_day_4' => 'nullable|numeric|between:0,999.9',
            'remarks' => 'nullable|string|max:1000',
        ];
    }

    protected function validateBusinessRules(): void
    {
        if ($this->group_id && $this->band_id) {
            throw ValidationException::withMessages([
                'band_id' => 'Eine Person kann nicht gleichzeitig einer Gruppe und einer Band angehören.'
            ]);
        }

        if ($this->responsible_person_id) {
            $responsiblePerson = Person::find($this->responsible_person_id);
            if (!$responsiblePerson || !$responsiblePerson->can_have_guests) {
                throw ValidationException::withMessages([
                    'responsible_person_id' => 'Die ausgewählte Person kann keine Gäste haben.'
                ]);
            }
        }
    }

    protected function validationAttributes(): array
    {
        return [
            'first_name' => 'Vorname',
            'last_name' => 'Nachname',
            'group_id' => 'Gruppe',
            'band_id' => 'Band',
            'responsible_person_id' => 'Verantwortliche Person',
            'voucher_day_1' => 'Voucher Tag 1',
            'voucher_day_2' => 'Voucher Tag 2',
            'voucher_day_3' => 'Voucher Tag 3',
            'voucher_day_4' => 'Voucher Tag 4',
            'remarks' => 'Bemerkungen',
        ];
    }
}
