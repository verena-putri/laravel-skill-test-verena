<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_can_get_active_posts()
    {
        Post::create([
            'user_id' => $this->user->id,
            'title' => 'Published Post',
            'content' => 'Published content',
            'is_draft' => false,
            'published_at' => now()->subDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get('/posts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta',
                'links',
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Published Post', $response->json('data.0.title'));
    }

    public function test_unauthenticated_user_cannot_create_post()
    {
        $response = $this->post('/posts', [
            'title' => 'Test Post',
            'content' => 'Test Content',
            'is_draft' => false,
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_create_post()
    {
        $this->actingAs($this->user);

        $response = $this->post('/posts', [
            'title' => 'New Post',
            'content' => 'New Post Content',
            'is_draft' => false,
        ]);

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);
    }

    public function test_can_create_draft_post()
    {
        $this->actingAs($this->user);

        $response = $this->post('/posts', [
            'title' => 'Draft Post',
            'content' => 'Draft Content',
            'is_draft' => true,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('posts', [
            'title' => 'Draft Post',
            'is_draft' => true,
            'published_at' => null,
        ]);
    }

    public function test_can_view_published_post()
    {
        $post = Post::create([
            'user_id' => $this->user->id,
            'title' => 'Published Post',
            'content' => 'Published content',
            'is_draft' => false,
            'published_at' => now()->subDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get("/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'title' => 'Published Post',
                    'content' => 'Published content',
                ],
            ]);
    }

    public function test_cannot_view_draft_post()
    {
        $post = Post::create([
            'user_id' => $this->user->id,
            'title' => 'Draft Post',
            'content' => 'Draft content',
            'is_draft' => true,
            'published_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get("/posts/{$post->id}");

        $response->assertStatus(404);
    }

    public function test_cannot_view_scheduled_post()
    {
        $post = Post::create([
            'user_id' => $this->user->id,
            'title' => 'Scheduled Post',
            'content' => 'Scheduled content',
            'is_draft' => false,
            'published_at' => now()->addDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get("/posts/{$post->id}");

        $response->assertStatus(404);
    }

    public function test_unauthorized_user_cannot_update_post()
    {
        $post = Post::create([
            'user_id' => $this->user->id,
            'title' => 'Original Title',
            'content' => 'Original content',
            'is_draft' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($otherUser);

        $response = $this->put("/posts/{$post->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(403);
    }

    public function test_authorized_user_can_update_post()
    {
        $post = Post::create([
            'user_id' => $this->user->id,
            'title' => 'Original Title',
            'content' => 'Original content',
            'is_draft' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->user);

        $response = $this->put("/posts/{$post->id}", [
            'title' => 'Updated Title',
            'content' => 'Updated content',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
            'content' => 'Updated content',
        ]);

        $response->assertJson(['success' => true]);
    }

    public function test_unauthorized_user_cannot_delete_post()
    {
        $post = Post::create([
            'user_id' => $this->user->id,
            'title' => 'Post to Delete',
            'content' => 'Content',
            'is_draft' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($otherUser);

        $response = $this->delete("/posts/{$post->id}");

        $response->assertStatus(403);
    }

    public function test_authorized_user_can_delete_post()
    {
        $post = Post::create([
            'user_id' => $this->user->id,
            'title' => 'Post to Delete',
            'content' => 'Content',
            'is_draft' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->user);

        $response = $this->delete("/posts/{$post->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('posts', [
            'id' => $post->id,
        ]);

        $response->assertJson(['success' => true]);
    }

    public function test_store_validation()
    {
        $this->actingAs($this->user);

        $response = $this->post('/posts', []);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['title', 'content']);

        $response = $this->post('/posts', [
            'title' => str_repeat('a', 256),
            'content' => 'Valid content',
        ]);

        $response->assertSessionHasErrors(['title']);
    }

    public function test_pagination_works()
    {
        for ($i = 0; $i < 25; $i++) {
            Post::create([
                'user_id' => $this->user->id,
                'title' => "Post {$i}",
                'content' => "Content {$i}",
                'is_draft' => false,
                'published_at' => now()->subDays($i),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $response = $this->get('/posts');

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('links', $data);

        $this->assertCount(20, $data['data']);
        $this->assertEquals(2, $data['meta']['last_page']);
    }

    public function test_create_route_returns_string()
    {

        $this->actingAs($this->user);

        $response = $this->get('/post/create/');

        $response->assertSee('posts.create');
    }
}
