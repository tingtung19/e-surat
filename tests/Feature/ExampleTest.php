<?php

namespace Tests\Feature;

use App\Models\Letter;
use App\Models\LetterCategory;
use App\Models\LetterRead;
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

    public function test_director_can_see_the_letter_creator_as_a_disposition_recipient(): void
    {
        $director = User::factory()->create(['role' => 'direktur']);
        $creator = User::factory()->create([
            'role' => 'divisi',
            'division_name' => 'Divisi Pembuat',
            'is_active' => true,
        ]);
        $letter = Letter::factory()->create([
            'title' => 'Surat pembuat',
            'description' => 'Isi surat pembuat',
            'type' => 'official',
            'created_by' => $creator->id,
            'sender_division_id' => $creator->id,
            'status' => 'sent',
            'number' => '001/TEST/2026',
            'verified_at' => now(),
        ]);

        $this->actingAs($director)
            ->get(route('letters.show', $letter))
            ->assertOk()
            ->assertSee($creator->email);
    }

    public function test_director_cannot_see_unverified_division_letter(): void
    {
        $director = User::factory()->create(['role' => 'direktur']);
        $creator = User::factory()->create(['role' => 'divisi', 'is_active' => true]);
        $letter = Letter::factory()->create([
            'title' => 'Surat belum diverifikasi',
            'description' => 'Isi surat',
            'type' => 'official',
            'created_by' => $creator->id,
            'sender_division_id' => $creator->id,
            'status' => 'waiting_verification',
        ]);

        $this->actingAs($director)->get(route('letters.index'))->assertOk()->assertDontSee('Surat belum diverifikasi');
        $this->actingAs($director)->get(route('letters.show', $letter))->assertForbidden();
    }

    public function test_letter_list_marks_unread_for_user_and_marks_it_read_when_opened(): void
    {
        $director = User::factory()->create(['role' => 'direktur']);
        $creator = User::factory()->create(['role' => 'divisi']);
        $letter = Letter::factory()->create([
            'title' => 'Surat baru direktur',
            'description' => 'Isi surat',
            'type' => 'official',
            'created_by' => $creator->id,
            'status' => 'sent',
            'number' => '002/TEST/2026',
            'verified_at' => now(),
        ]);

        $this->actingAs($director)->getJson(route('letters.index', ['draw' => 1, 'start' => 0, 'length' => 10]))
            ->assertOk()
            ->assertJsonPath('data.0.unread', true);

        $this->actingAs($director)->get(route('letters.show', $letter))->assertOk();
        $this->assertDatabaseHas('letter_reads', ['letter_id' => $letter->id, 'user_id' => $director->id]);

        $this->actingAs($director)->getJson(route('letters.index', ['draw' => 2, 'start' => 0, 'length' => 10]))
            ->assertJsonPath('data.0.unread', false);
    }

    public function test_datatables_request_without_json_header_returns_json(): void
    {
        $director = User::factory()->create(['role' => 'direktur']);
        $creator = User::factory()->create(['role' => 'divisi']);
        Letter::factory()->create([
            'title' => 'Surat browser request',
            'description' => 'Isi surat',
            'type' => 'official',
            'created_by' => $creator->id,
            'status' => 'sent',
            'verified_at' => now(),
        ]);

        $this->actingAs($director)
            ->get(route('letters.index', ['draw' => 1, 'start' => 0, 'length' => 10]), ['Accept' => 'text/html'])
            ->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    }

    public function test_new_disposition_makes_recipient_letter_unread_again(): void
    {
        $director = User::factory()->create(['role' => 'direktur']);
        $recipient = User::factory()->create(['role' => 'divisi']);
        $letter = Letter::factory()->create([
            'title' => 'Surat disposisi baru',
            'description' => 'Isi surat',
            'type' => 'official',
            'created_by' => $recipient->id,
            'sender_division_id' => $recipient->id,
            'status' => 'sent',
            'verified_at' => now(),
        ]);
        LetterRead::create(['letter_id' => $letter->id, 'user_id' => $recipient->id, 'read_at' => now()]);

        $this->actingAs($director)->post(route('letters.dispositions.store', $letter), [
            'to_user_ids' => [$recipient->id],
            'note' => 'Mohon ditindaklanjuti.',
        ])->assertRedirect();

        $this->actingAs($recipient)->getJson(route('letters.index', ['draw' => 1, 'start' => 0, 'length' => 10]))
            ->assertJsonPath('data.0.unread', true);
    }

    public function test_creator_can_edit_and_submit_a_draft(): void
    {
        $creator = User::factory()->create(['role' => 'divisi', 'division_name' => 'Divisi Umum']);
        $draft = Letter::factory()->create([
            'title' => 'Draft lama',
            'description' => 'Isi lama',
            'type' => 'official',
            'created_by' => $creator->id,
            'sender_division_id' => $creator->id,
            'status' => 'draft',
        ]);

        $this->actingAs($creator)->get(route('letters.edit', $draft))->assertOk()->assertSee('Draft lama');
        $this->actingAs($creator)->put(route('letters.update', $draft), [
            'title' => 'Draft diperbarui',
            'description' => 'Isi terbaru',
            'type' => 'official',
            'target_division_id' => $creator->id,
        ])->assertRedirect(route('letters.show', $draft));

        $this->assertDatabaseHas('letters', [
            'id' => $draft->id,
            'title' => 'Draft diperbarui',
            'description' => 'Isi terbaru',
            'status' => 'waiting_verification',
        ]);
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
