<?php

namespace App\Traits;

trait ValidatesBandData
{
    protected function getMemberValidationRules(): array
    {
        return [
            'first_name' => 'required|string|max:255|min:2',
            'last_name' => 'required|string|max:255|min:2',
            'voucher_day_1' => 'nullable|numeric|between:0,999.9',
            'voucher_day_2' => 'nullable|numeric|between:0,999.9',
            'voucher_day_3' => 'nullable|numeric|between:0,999.9',
            'voucher_day_4' => 'nullable|numeric|between:0,999.9',
            'remarks' => 'nullable|string|max:1000',
        ];
    }

    protected function getMemberValidationAttributes(): array
    {
        return [
            'first_name' => 'Vorname',
            'last_name' => 'Nachname',
            'voucher_day_1' => 'Voucher Tag 1',
            'voucher_day_2' => 'Voucher Tag 2',
            'voucher_day_3' => 'Voucher Tag 3',
            'voucher_day_4' => 'Voucher Tag 4',
            'remarks' => 'Bemerkungen',
        ];
    }
}
