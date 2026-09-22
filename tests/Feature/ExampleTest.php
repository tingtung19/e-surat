<?php

namespace Tests\Feature;

use App\Models\Letter;
use App\Models\LetterCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/');

        $response->assertStatus(200);
    }

    public function test_users_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create(['email' => 'login@example.com', 'password' => 'password']);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_divisions_cannot_verify_letters(): void
    {
        $user = User::factory()->create(['role' => 'divisi']);
        $letter = Letter::factory()->create([
            'title' => 'Surat uji',
            'description' => 'Isi surat uji',
            'type' => 'official',
            'created_by' => $user->id,
            'sender_division_id' => $user->id,
        ]);

        $this->actingAs($user)->post(route('letters.verify', $letter), [
            'number' => '001/TEST/2026',
            'category_id' => LetterCategory::factory()->create(['name' => 'Uji'])->id,
        ])->assertForbidden();
    }
}
