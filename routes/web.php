<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Livewire\BackstageControl;
use App\Livewire\Admin\UserManagement;
use App\Livewire\Admin\Settings;
use App\Livewire\Admin\ChangeLog;
use App\Livewire\Management\GroupManagement;
use App\Livewire\Management\BandManagement;
use App\Livewire\Admin\KnackImport;
use App\Livewire\Admin\KnackObjectsManagement;
use App\Livewire\Management\PersonManagement;
use App\Livewire\Admin\FieldLabel;
use App\Livewire\Admin\DuplicateManagement;
use App\Livewire\Admin\PersonImport;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ===== AUTHENTICATION ROUTES =====

// Login Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])
    ->name('login')
    ->middleware('guest');

Route::post('/login', [AuthController::class, 'login'])
    ->name('login.post')
    ->middleware('guest');

// Logout Route
Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

// Auth Check API Endpoint
Route::get('/auth/check', [AuthController::class, 'checkAuth'])
    ->name('auth.check');

// ===== MAIN APPLICATION ROUTES =====

// Redirect root to backstage control
Route::get('/', function () {
    return redirect()->route('home');
});

// Main Backstage Control Interface
Route::get('/backstage', BackstageControl::class)
    ->name('home')
    ->middleware('auth');

// Alternative route names for flexibility
Route::get('/control', BackstageControl::class)
    ->name('backstage.control')
    ->middleware('auth');

Route::get('/dashboard', BackstageControl::class)
    ->name('dashboard')
    ->middleware('auth');

// ===== USER PROFILE ROUTES =====

// Profile Management (for authenticated users)
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [AuthController::class, 'showProfile'])
        ->name('profile');

    Route::put('/profile', [AuthController::class, 'updateProfile'])
        ->name('profile.update');

    Route::put('/profile/password', [AuthController::class, 'changePassword'])
        ->name('password.change');
});

// ===== MANAGEMENT ROUTES =====

// Management Routes - require authentication (available for all logged-in users)
Route::middleware(['auth'])->prefix('management')->name('management.')->group(function () {

    // Person Management
    Route::get('/persons', PersonManagement::class)
        ->name('persons');

    Route::get('/personen', PersonManagement::class)
        ->name('personen'); // German alias

    // Groups, Subgroups & Stages Management
    Route::get('/groups', GroupManagement::class)
        ->name('groups');

    Route::get('/gruppen', GroupManagement::class)
        ->name('gruppen'); // German alias

    // Band Management
    Route::get('/bands', BandManagement::class)
        ->name('bands');

    Route::get('/baende', BandManagement::class)
        ->name('baende'); // German alias
});



// ===== ADMIN ROUTES =====

// Admin Routes - require authentication and admin privileges
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    // Admin Dashboard (redirect to users for now)
    Route::get('/', function () {
        return redirect()->route('admin.users');
    })->name('dashboard');

    // User Management
    Route::get('/users', UserManagement::class)
        ->name('users');

    Route::get('/benutzer', UserManagement::class)
        ->name('benutzer'); // German alias

    // System Settings
    Route::get('/settings', Settings::class)
        ->name('settings');

    Route::get('/einstellungen', Settings::class)
        ->name('einstellungen'); // German alias

    // Change Log / Activity Log
    Route::get('/changelog', ChangeLog::class)
        ->name('changelog');

    Route::get('/protokoll', ChangeLog::class)
        ->name('protokoll'); // German alias

    Route::get('/log', ChangeLog::class)
        ->name('log'); // Short alias
    Route::get('/person-import', PersonImport::class)
        ->name('person-import');

    Route::get('/personen-import', PersonImport::class)
        ->name('personen-import'); // German alias

    // Knack Import
    Route::get('/knack-import', KnackImport::class)
        ->name('knack-import');

    Route::get('/import', KnackImport::class)
        ->name('import'); // Short alias

    Route::get('/datenimport', KnackImport::class)
        ->name('datenimport'); // German alias

    Route::get('/knack-objects', KnackObjectsManagement::class)
        ->name('knack-objects');

    Route::get('/knack-objekte', KnackObjectsManagement::class)
        ->name('knack-objekte'); // German alias

    Route::get('/duplicates', DuplicateManagement::class)
        ->name('duplicates');
});

// ===== API ROUTES (optional for future extensions) =====

// API Routes for potential mobile app or external integrations
Route::prefix('api')->middleware(['auth:sanctum'])->group(function () {

    // Person lookup API
    Route::get('/persons/search', function (Illuminate\Http\Request $request) {
        $query = $request->get('q');
        if (!$query || strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        $persons = \App\Models\Person::with(['band', 'group'])
            ->where('year', now()->year)
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'ILIKE', '%' . $query . '%')
                    ->orWhere('last_name', 'ILIKE', '%' . $query . '%')
                    ->orWhereHas('band', function ($bandQuery) use ($query) {
                        $bandQuery->where('band_name', 'ILIKE', '%' . $query . '%');
                    });
            })
            ->limit(10)
            ->get();

        return response()->json(['data' => $persons]);
    })->name('api.persons.search');

    // Current settings API
    Route::get('/settings', function () {
        $settings = \App\Models\Settings::current();
        return response()->json(['data' => $settings]);
    })->name('api.settings');

    // Stages API
    Route::get('/stages', function () {
        $stages = \App\Models\Stage::where('year', now()->year)->get();
        return response()->json(['data' => $stages]);
    })->name('api.stages');

    // Groups API
    Route::get('/groups', function () {
        $groups = \App\Models\Group::where('year', now()->year)->with('subgroups')->get();
        return response()->json(['data' => $groups]);
    })->name('api.groups');

    // Bands API
    Route::get('/bands', function () {
        $bands = \App\Models\Band::where('year', now()->year)->with(['stage', 'members'])->get();
        return response()->json(['data' => $bands]);
    })->name('api.bands');
});

// ===== DEVELOPMENT/DEBUG ROUTES =====

// Only available in local/testing environment
if (app()->environment(['local', 'testing'])) {

    // Route to quickly create test data
    Route::get('/dev/seed', function () {
        // Create test user if not exists
        if (!\App\Models\User::where('username', 'test')->exists()) {
            \App\Models\User::create([
                'username' => 'test',
                'password' => \Illuminate\Support\Facades\Hash::make('test123'),
                'first_name' => 'Test',
                'last_name' => 'User',
                'is_admin' => false,
                'can_reset_changes' => false,
            ]);
        }

        // Create settings if not exists
        if (!\App\Models\Settings::where('year', now()->year)->exists()) {
            \App\Models\Settings::create([
                'day_1_date' => now()->addDays(1),
                'day_2_date' => now()->addDays(2),
                'day_3_date' => now()->addDays(3),
                'day_4_date' => now()->addDays(4),
                'wristband_color_day_1' => 'Rot',
                'wristband_color_day_2' => 'Blau',
                'wristband_color_day_3' => 'Grün',
                'wristband_color_day_4' => 'Gelb',
                'year' => now()->year,
            ]);
        }

        // Create sample stages
        if (!\App\Models\Stage::where('year', now()->year)->exists()) {
            \App\Models\Stage::create([
                'name' => 'Hauptbühne',
                'presence_days' => 'all_days',
                'guest_allowed' => true,
                'vouchers_on_performance_day' => 2.0,
                'year' => now()->year,
            ]);

            \App\Models\Stage::create([
                'name' => 'Kleine Bühne',
                'presence_days' => 'performance_day',
                'guest_allowed' => false,
                'vouchers_on_performance_day' => 1.0,
                'year' => now()->year,
            ]);
        }

        // Create sample groups
        if (!\App\Models\Group::where('year', now()->year)->exists()) {
            \App\Models\Group::create([
                'name' => 'VIP',
                'backstage_day_1' => true,
                'backstage_day_2' => true,
                'backstage_day_3' => true,
                'backstage_day_4' => true,
                'voucher_day_1' => 2.0,
                'voucher_day_2' => 2.0,
                'voucher_day_3' => 2.0,
                'voucher_day_4' => 2.0,
                'year' => now()->year,
            ]);

            \App\Models\Group::create([
                'name' => 'Crew',
                'backstage_day_1' => true,
                'backstage_day_2' => true,
                'backstage_day_3' => true,
                'backstage_day_4' => true,
                'voucher_day_1' => 1.0,
                'voucher_day_2' => 1.0,
                'voucher_day_3' => 1.0,
                'voucher_day_4' => 1.0,
                'year' => now()->year,
            ]);
        }

        return 'Test data created successfully! You can now use the system with sample data.';
    })->name('dev.seed');

    // Route to clear all logs
    Route::get('/dev/clear-logs', function () {
        \App\Models\ChangeLog::truncate();
        return 'Change logs cleared!';
    })->name('dev.clear-logs');

    // Route to show app info
    Route::get('/dev/info', function () {
        return [
            'app_name' => config('app.name'),
            'app_env' => config('app.env'),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'users_count' => \App\Models\User::count(),
            'persons_count' => \App\Models\Person::count(),
            'bands_count' => \App\Models\Band::count(),
            'groups_count' => \App\Models\Group::count(),
            'stages_count' => \App\Models\Stage::count(),
            'current_year' => now()->year,
        ];
    })->name('dev.info');

    // Route to reset everything
    Route::get('/dev/reset', function () {
        // Truncate all tables (be careful!)
        \App\Models\ChangeLog::truncate();
        \App\Models\VoucherPurchase::truncate();
        \App\Models\BandGuest::truncate();
        \App\Models\VehiclePlate::truncate();
        \App\Models\Person::truncate();
        \App\Models\Band::truncate();
        \App\Models\Subgroup::truncate();
        \App\Models\Group::truncate();
        \App\Models\Stage::truncate();
        \App\Models\Settings::truncate();
        \App\Models\FieldLabel::truncate();

        // Keep admin user
        \App\Models\User::where('username', '!=', 'admin')->delete();

        return 'Database reset! Only admin user remains.';
    })->name('dev.reset');
}
