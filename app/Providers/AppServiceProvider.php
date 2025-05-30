<?php

// app/Providers/AppServiceProvider.php (Boot-Methode erweitern)
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Automatic change logging for models
        $modelsToLog = [
            \App\Models\Person::class,
            \App\Models\Band::class,
            \App\Models\Group::class,
            \App\Models\Stage::class,
            \App\Models\BandGuest::class,
            \App\Models\VoucherPurchase::class,
        ];

        foreach ($modelsToLog as $modelClass) {
            $modelClass::updating(function ($model) {
                foreach ($model->getDirty() as $field => $newValue) {
                    $oldValue = $model->getOriginal($field);
                    if ($oldValue != $newValue) {
                        \App\Models\ChangeLog::logChange($model, $field, $oldValue, $newValue);
                    }
                }
            });

            $modelClass::created(function ($model) {
                \App\Models\ChangeLog::logChange($model, 'created', null, 'Record created', 'create');
            });

            $modelClass::deleted(function ($model) {
                \App\Models\ChangeLog::logChange($model, 'deleted', 'Record existed', null, 'delete');
            });
        }
    }
}
