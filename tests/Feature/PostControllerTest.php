<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    // ========== REQUIREMENT 4-1: posts.index ==========

    public function test_it_returns_paginated_list_of_active_posts()
    {
        $user = User::factory()->create();

        Post::factory()->count(25)->create([
            'user_id' => $user->id,
            'published_at' => now()->subDays(1),
        ]);

        Post::factory()->count(3)->create([
            'user_id' => $user->id,
            'published_at' => null,
        ]);

        Post::factory()->count(3)->create([
            'user_id' => $user->id,
            'published_at' => now()->addDays(1),
        ]);

        $response = $this->getJson('/posts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
                'links',
            ])
            ->assertJson([
                'meta' => [
                    'per_page' => 20,
                    'total' => 25,
                    'current_page' => 1,
                ],
            ]);
    }

    public function test_it_excludes_draft_posts_from_index()
    {
        $user = User::factory()->create();

        $publishedPost = Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Published Post Title',
            'published_at' => now()->subDay(),
        ]);

        $draftPost = Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Draft Post Title',
            'published_at' => null,
        ]);

        $response = $this->getJson('/posts');

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Published Post Title'])
            ->assertJsonMissing(['title' => 'Draft Post Title']);
    }

    public function test_it_excludes_scheduled_posts_from_index()
    {
        $user = User::factory()->create();

        $publishedPost = Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Published Post Title',
            'published_at' => now()->subDay(),
        ]);

        $scheduledPost = Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Scheduled Post Title',
            'published_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/posts');

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Published Post Title'])
            ->assertJsonMissing(['title' => 'Scheduled Post Title']);
    }

    public function test_index_includes_author_data()
    {
        $user = User::factory()->create(['name' => 'Test Author']);
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/posts');

        $response->assertStatus(200);

        $responseData = $response->json('data');
        $this->assertNotEmpty($responseData);
        $firstPost = $responseData[0];
        $this->assertArrayHasKey('user', $firstPost);
        $this->assertEquals($user->name, $firstPost['user']['name']);
    }

    // ========== REQUIREMENT 4-2: posts.create ==========

    public function test_authenticated_user_can_access_create_route()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/posts/create');

        $response->assertStatus(200)
            ->assertSee('posts.create');
    }

    public function test_guest_cannot_access_create_route()
    {
        $response = $this->get('/posts/create');

        $response->assertStatus(403);
    }

    // ========== REQUIREMENT 4-3: posts.store ==========

    public function test_authenticated_user_can_create_post()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $postData = [
            'title' => 'New Test Post',
            'content' => 'This is the content of the test post.',
        ];

        $response = $this->postJson('/posts', $postData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'title', 'content', 'user_id'],
                'message',
            ])
            ->assertJson([
                'success' => true,
                'data' => ['title' => 'New Test Post'],
                'message' => 'Post created successfully.',
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'New Test Post',
            'user_id' => $user->id,
        ]);
    }

    public function test_guest_cannot_create_post()
    {
        $postData = [
            'title' => 'New Test Post',
            'content' => 'This is the content of the test post.',
        ];

        $response = $this->postJson('/posts', $postData);

        $response->assertStatus(403);
    }

    public function test_store_validates_required_fields()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/posts', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'content']);
    }

    // ========== REQUIREMENT 4-4: posts.show ==========

    public function test_it_shows_published_post()
    {
        $user = User::factory()->create(['name' => 'Post Author']);
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson("/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'title', 'content', 'published_at', 'user'],
                'message',
            ])
            ->assertJson([
                'success' => true,
                'data' => ['id' => $post->id],
                'message' => 'Post retrieved successfully.',
            ]);
    }

    public function test_it_returns_404_for_draft_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'published_at' => null,
        ]);

        $response = $this->getJson("/posts/{$post->id}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Post not found.',
            ]);
    }

    public function test_it_returns_404_for_scheduled_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'published_at' => now()->addDay(),
        ]);

        $response = $this->getJson("/posts/{$post->id}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Post not found.',
            ]);
    }

    public function test_author_can_view_their_own_draft_post()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'published_at' => null,
        ]);

        $response = $this->getJson("/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['id' => $post->id],
            ]);
    }

    public function test_author_can_view_their_own_scheduled_post()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'published_at' => now()->addDay(),
        ]);

        $response = $this->getJson("/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['id' => $post->id],
            ]);
    }

    // ========== REQUIREMENT 4-5: posts.edit ==========

    public function test_author_can_access_edit_route()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->get("/posts/{$post->id}/edit");

        $response->assertStatus(200)
            ->assertSee('posts.edit');
    }

    public function test_non_author_cannot_access_edit_route()
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $post = Post::factory()->create(['user_id' => $author->id]);

        $response = $this->get("/posts/{$post->id}/edit");

        $response->assertStatus(403);
    }

    // ========== REQUIREMENT 4-6: posts.update ==========

    public function test_author_can_update_post()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Original Title',
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'content' => 'Updated content',
        ];

        $response = $this->putJson("/posts/{$post->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['title' => 'Updated Title'],
                'message' => 'Post updated successfully.',
            ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_non_author_cannot_update_post()
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $post = Post::factory()->create([
            'user_id' => $author->id,
            'title' => 'Original Title',
        ]);

        $response = $this->putJson("/posts/{$post->id}", [
            'title' => 'Hacked Title',
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Original Title',
        ]);
    }

    public function test_update_validates_data()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->putJson("/posts/{$post->id}", [
            'title' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    // ========== REQUIREMENT 4-7: posts.destroy ==========

    public function test_author_can_delete_post()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $post = Post::factory()->create(['user_id' => $user->id]);

        // Verify post exists before delete
        $this->assertNotNull(Post::find($post->id));

        $response = $this->deleteJson("/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Post deleted successfully.',
            ]);

        // **SIMPLE CHECK:**
        // Post should not be found with normal query
        $this->assertNull(Post::find($post->id), 'Post should not be found with normal find()');

        // But should be found with withTrashed()
        $this->assertNotNull(Post::withTrashed()->find($post->id), 'Post should exist with withTrashed()');
        $trashedPost = Post::withTrashed()->find($post->id);
        $this->assertNotNull($trashedPost->deleted_at, 'Deleted post should have deleted_at timestamp');
    }

    public function test_non_author_cannot_delete_post()
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $post = Post::factory()->create(['user_id' => $author->id]);

        $response = $this->deleteJson("/posts/{$post->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
        ]);

        $notDeletedPost = Post::find($post->id);
        $this->assertNotNull($notDeletedPost);
        $this->assertNull($notDeletedPost->deleted_at);
    }
}
