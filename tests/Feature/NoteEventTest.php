<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Note;
use App\Events\NoteCreated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Artisan;

class NoteEventTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Install Passport for testing
        Artisan::call('passport:install');

        // Create authenticated user
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('auth_token')->accessToken;
    }

    /** @test */
    public function note_created_event_is_dispatched_when_note_is_created()
    {
        Event::fake([NoteCreated::class]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/notes', [
            'title' => 'Test Note',
            'content' => 'Test content',
            'priority' => 'high',
        ]);

        $response->assertStatus(201);

        // Assert the event was dispatched
        Event::assertDispatched(NoteCreated::class, function ($event) {
            return $event->note->title === 'Test Note';
        });
    }

    /** @test */
    public function admins_are_notified_when_note_is_created()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Note created successfully', \Mockery::any());

        // Create admin users
        $admin1 = User::factory()->create(['is_admin' => true, 'email' => 'admin1@example.com']);
        $admin2 = User::factory()->create(['is_admin' => true, 'email' => 'admin2@example.com']);

        // Create regular user (should not be notified)
        $regularUser = User::factory()->create(['is_admin' => false]);

        // Expect log entries for each admin
        Log::shouldReceive('info')
            ->twice()
            ->with('Notification sent to admin', \Mockery::on(function ($context) use ($admin1, $admin2) {
                return in_array($context['admin_email'], [$admin1->email, $admin2->email]);
            }));

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/notes', [
            'title' => 'Test Note',
            'content' => 'Test content',
        ]);

        $response->assertStatus(201);
    }
}
