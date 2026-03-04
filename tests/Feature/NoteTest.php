<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Note;
use Artisan;

class NoteTest extends TestCase
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
    public function authenticated_user_can_create_note()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/notes', [
            'title' => 'Buy groceries',
            'content' => 'Milk, eggs, bread',
            'priority' => 'high',
            'due_date' => '2024-12-31',
            'tags' => ['shopping', 'urgent'],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'title',
                'content',
                'priority',
                'completed',
                'due_date',
                'tags',
                'created_at',
                'updated_at',
            ])
            ->assertJson([
                'title' => 'Buy groceries',
                'content' => 'Milk, eggs, bread',
                'priority' => 'high',
            ]);

        $this->assertDatabaseHas('notes', [
            'user_id' => $this->user->id,
            'title' => 'Buy groceries',
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_create_note()
    {
        $response = $this->postJson('/api/notes', [
            'title' => 'Buy groceries',
            'content' => 'Milk, eggs, bread',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function note_creation_requires_title_and_content()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/notes', [
            'priority' => 'high',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'content']);
    }

    /** @test */
    public function user_can_view_single_note()
    {
        $note = Note::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Note',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/notes/{$note->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $note->id,
                'title' => 'Test Note',
            ]);
    }

    /** @test */
    public function user_can_update_their_note()
    {
        $note = Note::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Original Title',
            'completed' => false,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/notes/{$note->id}", [
            'title' => 'Updated Title',
            'content' => 'Updated content',
            'completed' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'title' => 'Updated Title',
                'completed' => true,
            ]);

        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'title' => 'Updated Title',
            'completed' => true,
        ]);
    }

    /** @test */
    public function user_cannot_update_other_users_note()
    {
        $otherUser = User::factory()->create();
        $note = Note::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/notes/{$note->id}", [
            'title' => 'Hacked Title',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_delete_their_note()
    {
        $note = Note::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/notes/{$note->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Note deleted successfully',
            ]);

        $this->assertSoftDeleted('notes', [
            'id' => $note->id,
        ]);
    }

    /** @test */
    public function user_cannot_delete_other_users_note()
    {
        $otherUser = User::factory()->create();
        $note = Note::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/notes/{$note->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function note_not_found_returns_404()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/notes/99999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Not found',
            ]);
    }
}
