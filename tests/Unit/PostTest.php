<?php

namespace Tests\Unit;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_determine_post_status()
    {
        // Test draft
        $draft = Post::factory()->make(['published_at' => null]);
        $this->assertTrue($draft->isDraft());
        $this->assertFalse($draft->isPublished());
        $this->assertFalse($draft->isScheduled());

        // Test published
        $published = Post::factory()->make(['published_at' => now()->subDay()]);
        $this->assertFalse($published->isDraft());
        $this->assertTrue($published->isPublished());
        $this->assertFalse($published->isScheduled());

        // Test scheduled
        $scheduled = Post::factory()->make(['published_at' => now()->addDay()]);
        $this->assertFalse($scheduled->isDraft());
        $this->assertFalse($scheduled->isPublished());
        $this->assertTrue($scheduled->isScheduled());
    }

    public function test_published_scope_includes_only_published_posts()
    {
        $user = User::factory()->create();

        Post::factory()->create([
            'user_id' => $user->id,
            'published_at' => now()->subDay(),
        ]); // Published

        Post::factory()->create([
            'user_id' => $user->id,
            'published_at' => null,
        ]); // Draft

        Post::factory()->create([
            'user_id' => $user->id,
            'published_at' => now()->addDay(),
        ]); // Scheduled

        $publishedPosts = Post::published()->get();
        $this->assertCount(1, $publishedPosts);
        $this->assertTrue($publishedPosts->first()->isPublished());
    }

    public function test_it_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $post->user);
        $this->assertEquals($user->id, $post->user->id);
    }

    public function test_it_has_fillable_attributes()
    {
        $post = new Post;

        $this->assertEquals([
            'title',
            'content',
            'published_at',
            'user_id',
        ], $post->getFillable());
    }
}
