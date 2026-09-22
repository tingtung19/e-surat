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

    public function test_director_can_send_disposition_to_multiple_divisions(): void
    {
        $director = User::factory()->create(['role' => 'direktur']);
        $recipients = User::factory()->count(2)->create(['role' => 'divisi', 'is_active' => true]);
        $letter = Letter::factory()->create(['title' => 'Surat disposisi', 'description' => 'Isi surat disposisi', 'type' => 'official', 'created_by' => $director->id]);

        $this->actingAs($director)->post(route('letters.dispositions.store', $letter), [
            'to_user_ids' => $recipients->pluck('id')->all(),
            'note' => 'Mohon ditindaklanjuti.',
        ])->assertRedirect();

        $this->assertDatabaseCount('letter_dispositions', 2);
        $this->assertDatabaseHas('letters', ['id' => $letter->id, 'status' => 'waiting_reply']);
    }

    public function test_director_can_send_cc_to_multiple_divisions_and_reject_inactive_users(): void
    {
        $director = User::factory()->create(['role' => 'direktur']);
        $recipients = User::factory()->count(2)->create(['role' => 'divisi', 'is_active' => true]);
        $inactive = User::factory()->create(['role' => 'divisi', 'is_active' => false]);
        $letter = Letter::factory()->create(['title' => 'Surat tembusan', 'description' => 'Isi surat tembusan', 'type' => 'official', 'created_by' => $director->id]);

        $this->actingAs($director)->post(route('letters.cc.store', $letter), [
            'cc_user_ids' => $recipients->pluck('id')->all(),
            'note' => 'Untuk informasi.',
        ])->assertRedirect();

        $this->assertDatabaseCount('letter_dispositions', 2);
        $this->assertDatabaseHas('letter_dispositions', ['to_user_id' => $recipients->first()->id, 'type' => 'cc', 'is_replied' => true]);

        $this->actingAs($director)->post(route('letters.cc.store', $letter), [
            'cc_user_ids' => [$inactive->id],
        ])->assertStatus(422);
        $this->assertDatabaseCount('letter_dispositions', 2);
    }
}
