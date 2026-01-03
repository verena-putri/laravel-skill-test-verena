<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing posts
        Post::query()->delete();

        // Get or create users
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        $author = User::firstOrCreate(
            ['email' => 'author@example.com'],
            [
                'name' => 'Author User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        $users = [$admin, $author];

        foreach ($users as $user) {
            for ($i = 7; $i > 0; $i--) {
                Post::factory()->create([
                    'user_id' => $user->id,
                    'published_at' => now()->subDays($i),
                ]);
            }

            Post::factory()->create([
                'user_id' => $user->id,
                'title' => 'This is Scheduled Post by '.$user->name,
                'published_at' => now()->addDays(1),
            ]);

            Post::factory()->create([
                'user_id' => $user->id,
                'title' => 'This is Draft Post by '.$user->name,
                'published_at' => null,
            ]);
        }

        for ($i = 1; $i <= 10; $i++) {
            $user = $i % 2 == 0 ? $admin : $author;

            Post::factory()->create([
                'user_id' => $user->id,
                'title' => 'Additional Post '.$i.' for Pagination',
                'published_at' => now()->subDays($i + 10),
            ]);
        }

    }
}
