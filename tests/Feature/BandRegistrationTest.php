<?php

// ============================================================================
// tests/Feature/BandRegistrationTest.php
// Basis-Tests für das Registration System
// ============================================================================

namespace Tests\Feature;

use App\Models\Band;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BandRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Test-Daten erstellen
        $stage = Stage::create([
            'name' => 'Main Stage',
            'year' => now()->year,
        ]);

        $this->band = Band::create([
            'band_name' => 'Test Band',
            'stage_id' => $stage->id,
            'year' => now()->year,
            'manager_first_name' => 'Max',
            'manager_last_name' => 'Mustermann',
            'manager_email' => 'max@testband.com',
        ]);
    }

    public function test_can_generate_registration_token()
    {
        $token = $this->band->generateRegistrationToken();

        $this->assertNotNull($token);
        $this->assertEquals(64, strlen($token));
        $this->assertNotNull($this->band->registration_token_expires_at);
        $this->assertFalse($this->band->registration_completed);
    }

    public function test_can_access_registration_form_with_valid_token()
    {
        $token = $this->band->generateRegistrationToken();

        $response = $this->get(route('band.register', ['token' => $token]));

        $response->assertStatus(200);
        $response->assertSee($this->band->band_name);
    }

    public function test_cannot_access_registration_form_with_invalid_token()
    {
        $response = $this->get(route('band.register', ['token' => 'invalid-token']));

        $response->assertStatus(404);
    }

    public function test_can_submit_registration_form()
    {
        Mail::fake();

        $token = $this->band->generateRegistrationToken();

        $data = [
            'travel_party' => 4,
            'members' => [
                ['first_name' => 'John', 'last_name' => 'Doe'],
                ['first_name' => 'Jane', 'last_name' => 'Smith'],
                ['first_name' => 'Bob', 'last_name' => 'Wilson'],
                ['first_name' => 'Alice', 'last_name' => 'Johnson'],
            ],
            'vehicle_plates' => ['B-MW 1234', 'HH-AB 5678'],
            'emergency_contact' => 'Emergency Contact +49 123 456789',
            'special_requirements' => 'Vegetarian catering needed',
        ];

        $response = $this->post(route('band.register.store', ['token' => $token]), $data);

        $response->assertRedirect(route('band.register.success', ['token' => $token]));

        $this->band->refresh();
        $this->assertTrue($this->band->registration_completed);
        $this->assertEquals(4, $this->band->travel_party);
        $this->assertEquals(4, $this->band->persons()->count());
        $this->assertEquals(2, $this->band->vehiclePlates()->count());
    }

    public function test_registration_form_validation()
    {
        $token = $this->band->generateRegistrationToken();

        $response = $this->post(route('band.register.store', ['token' => $token]), []);

        $response->assertSessionHasErrors(['travel_party', 'members']);
    }

    public function test_admin_can_view_registration_links()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.band-registration-links'));

        $response->assertStatus(200);
    }
}
