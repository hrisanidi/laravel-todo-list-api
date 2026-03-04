<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Note;
use Artisan;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $adminToken;
    protected $regularUser;
    protected $regularToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Install Passport for testing
        Artisan::call('passport:install');

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->adminToken = $this->admin->createToken('auth_token')->accessToken;

        $this->regularUser = User::factory()->create(['is_admin' => false]);
        $this->regularToken = $this->regularUser->createToken('auth_token')->accessToken;
    }

    /** @test */
    public function admin_can_view__users()
    {
        User::factory()->count(5)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/admin/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'email',
                    'is_admin',
                    'notes_count',
                    'created_at',
                ]
            ]);

        $response->assertJsonCount(7);
    }

    /** @test */
    public function regular_user_cannot_view_users()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->regularToken,
        ])->getJson('/api/admin/users');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden',
            ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_admin_routes()
    {
        $response = $this->getJson('/api/admin/users');

        $response->assertStatus(401);
    }

    /** @test */
    public function admin_can_view_any_users_notes()
    {
        $targetUser = User::factory()->create();
        Note::factory()->count(5)->create(['user_id' => $targetUser->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson("/api/admin/users/{$targetUser->id}/notes");

        $response->assertStatus(200)
            ->assertJsonCount(5)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'title',
                    'content',
                    'priority',
                    'completed',
                    'due_date',
                    'tags',
                    'created_at',
                    'updated_at',
                ]
            ]);
    }

    /** @test */
    public function regular_user_cannot_view_other_users_notes()
    {
        $targetUser = User::factory()->create();
        Note::factory()->count(5)->create(['user_id' => $targetUser->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->regularToken,
        ])->getJson("/api/admin/users/{$targetUser->id}/notes");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_cannot_view_nonexistent_user_notes()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/admin/users/99999/notes');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Not found',
            ]);
    }

    /** @test */
    public function admin_can_view_their_own_notes()
    {
        Note::factory()->count(3)->create(['user_id' => $this->admin->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/notes');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }
}
