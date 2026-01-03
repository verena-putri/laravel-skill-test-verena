<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    // TIDAK ADA constructor dengan middleware

    // ========== REQUIREMENT 4-1: posts.index ==========
    public function index(): JsonResponse
    {
        $posts = Post::whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with('user:id,name')
            ->latest('published_at')
            ->paginate(20);

        return response()->json([
            'data' => $posts->items(),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
            'links' => [
                'first' => $posts->url(1),
                'last' => $posts->url($posts->lastPage()),
                'prev' => $posts->previousPageUrl(),
                'next' => $posts->nextPageUrl(),
            ],
        ]);
    }

    // ========== REQUIREMENT 4-2: posts.create ==========
    public function create(): string
    {
        // Requirement: Only authenticated users can access this route
        if (! Auth::check()) {
            abort(403, 'Unauthorized');
        }

        return 'posts.create';
    }

    // ========== REQUIREMENT 4-3: posts.store ==========
    public function store(Request $request): JsonResponse
    {
        // Requirement: Only authenticated users can create new posts
        if (! Auth::check()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'published_at' => 'nullable|date',
        ]);

        $post = Auth::user()->posts()->create($validated);

        return response()->json([
            'success' => true,
            'data' => $post->load('user:id,name'),
            'message' => 'Post created successfully.',
        ], 201);
    }

    // ========== REQUIREMENT 4-4: posts.show ==========
    public function show(string $id): JsonResponse
    {
        $post = Post::with('user:id,name')->find($id);

        if (! $post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found.',
            ], 404);
        }

        $isPublished = $post->published_at && $post->published_at <= now();
        $isAuthor = Auth::check() && Auth::id() === $post->user_id;

        if ($isPublished || $isAuthor) {
            return response()->json([
                'success' => true,
                'data' => $post,
                'message' => 'Post retrieved successfully.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Post not found.',
        ], 404);
    }

    // ========== REQUIREMENT 4-5: posts.edit ==========
    public function edit(Post $post): string
    {
        // Requirement: Only the post author can access this route
        if (Auth::id() !== $post->user_id) {
            abort(403, 'Unauthorized');
        }

        return 'posts.edit';
    }

    // ========== REQUIREMENT 4-6: posts.update ==========
    public function update(Request $request, Post $post): JsonResponse
    {
        // Requirement: Only the post author can update the post
        if (Auth::id() !== $post->user_id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'published_at' => 'sometimes|nullable|date',
        ]);

        $post->update($validated);

        return response()->json([
            'success' => true,
            'data' => $post->fresh()->load('user:id,name'),
            'message' => 'Post updated successfully.',
        ]);
    }

    // ========== REQUIREMENT 4-7: posts.destroy ==========
    public function destroy(Post $post): JsonResponse
    {
        // Requirement: Only the post author can delete the post
        if (Auth::id() !== $post->user_id) {
            abort(403, 'Unauthorized');
        }

        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully.',
        ]);
    }
}
