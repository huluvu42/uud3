<?php

// app/Services/VoucherService.php

namespace App\Services;

use App\Models\Person;
use App\Models\VoucherPurchase;
use App\Models\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VoucherService
{
    /**
     * Voucher für eine Person ausgeben
     */
    public function issueVouchersForPerson(Person $person, int $day, Settings $settings, int $currentDay): array
    {
        // Validierung
        if (!$settings->canIssueVouchersForDay($day, $currentDay)) {
            return [
                'success' => false,
                'message' => "Voucher für {$settings->getDayLabel($day)} können aktuell nicht ausgegeben werden!"
            ];
        }

        $availableVouchers = $person->{"voucher_day_$day"};
        if ($availableVouchers <= 0) {
            return [
                'success' => false,
                'message' => 'Keine Voucher verfügbar!'
            ];
        }

        // Anzahl der auszugebenden Voucher bestimmen
        $vouchersToIssue = $settings->isSingleVoucherMode() ? 1 : $availableVouchers;

        try {
            DB::beginTransaction();

            // Voucher von verfügbaren abziehen
            $person->{"voucher_day_$day"} -= $vouchersToIssue;

            // Zu heute ausgegebenen hinzufügen
            $currentIssued = $person->{"voucher_issued_day_{$currentDay}"};
            $person->{"voucher_issued_day_{$currentDay}"} = $currentIssued + $vouchersToIssue;

            $person->save();

            DB::commit();

            // Erfolgsmeldung erstellen
            $voucherLabel = $settings->getVoucherLabel();
            $dayLabel = $settings->getDayLabel($day);
            $currentDayLabel = $settings->getDayLabel($currentDay);

            if ($day != $currentDay) {
                $message = "$vouchersToIssue $voucherLabel ($dayLabel) heute ($currentDayLabel) für {$person->full_name} ausgegeben!";
            } else {
                $message = "$vouchersToIssue $voucherLabel für {$person->full_name} ausgegeben!";
            }

            return [
                'success' => true,
                'message' => $message,
                'vouchers_issued' => $vouchersToIssue
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Voucher issuance failed', [
                'person_id' => $person->id,
                'day' => $day,
                'vouchers_to_issue' => $vouchersToIssue,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Fehler beim Ausgeben der Voucher!'
            ];
        }
    }

    /**
     * Voucher kaufen
     */
    public function purchaseVoucher(float $amount, int $stageId, int $currentDay, ?int $personId = null): array
    {
        try {
            DB::beginTransaction();

            $voucher = new VoucherPurchase();
            $voucher->amount = $amount;
            $voucher->day = $currentDay;
            $voucher->purchase_date = now()->format('Y-m-d');
            $voucher->stage_id = $stageId;
            $voucher->user_id = auth()->id();

            if ($personId) {
                $voucher->person_id = $personId;
            }

            $voucher->save();

            DB::commit();

            return [
                'success' => true,
                'voucher' => $voucher
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Voucher purchase failed', [
                'amount' => $amount,
                'stage_id' => $stageId,
                'person_id' => $personId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Fehler beim Kauf: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verkaufte Voucher für eine Bühne abrufen
     */
    public function getSoldVouchersForStage(int $stageId, int $day): float
    {
        return VoucherPurchase::where('stage_id', $stageId)
            ->where('day', $day)
            ->sum('amount');
    }

    /**
     * Nächsten verfügbaren Voucher-Tag für Person ermitteln
     */
    public function getNextAvailableVoucherDay(Person $person, array $allowedDays): ?int
    {
        foreach ($allowedDays as $day) {
            $available = $person->{"voucher_day_$day"};
            if ($available > 0) {
                return $day;
            }
        }

        return null;
    }

    /**
     * Erlaubte Voucher-Tage basierend auf Settings ermitteln
     */
    public function getAllowedVoucherDays(Settings $settings, int $currentDay): array
    {
        switch ($settings->voucher_issuance_rule) {
            case 'current_day_only':
                return [$currentDay];
            case 'current_and_past':
                return range(1, $currentDay);
            case 'all_days':
                return [1, 2, 3, 4];
            default:
                return [$currentDay];
        }
    }
}
