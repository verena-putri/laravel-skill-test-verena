<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::active()
            ->with('user:id,name,email')
            ->orderBy('published_at', 'desc')
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

    public function create()
    {
        return 'posts.create';
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_draft' => 'boolean',
            'published_at' => 'nullable|date',
        ]);

        $post = Auth::user()->posts()->create($validated);

        return response()->json([
            'success' => true,
            'data' => $post,
            'message' => 'Post created successfully.',
        ], 201);
    }

    public function show(string $id)
    {
        $post = Post::active()->with('user:id,name,email')->find($id);

        if (! $post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found or not yet published.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $post,
            'message' => 'Post retrieved successfully.',
        ]);
    }

    public function edit(Post $post)
    {
        if (Auth::id() !== $post->user_id) {
            abort(403, 'Unauthorized action.');
        }

        return 'posts.edit';
    }

    public function update(Request $request, Post $post)
    {
        if (Auth::id() !== $post->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'is_draft' => 'sometimes|boolean',
            'published_at' => 'sometimes|nullable|date',
        ]);

        $post->update($validated);

        return response()->json([
            'success' => true,
            'data' => $post,
            'message' => 'Post updated successfully.',
        ]);
    }

    public function destroy(Post $post)
    {
        if (Auth::id() !== $post->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
            ], 403);
        }

        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully.',
        ]);
    }
}
