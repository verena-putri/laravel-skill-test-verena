<?php

namespace App\Http\Middleware;

use App\Models\Post;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPostAuthor
{
    public function handle(Request $request, Closure $next): Response
    {
        $postId = $request->route('post') ?? $request->route('id');
        $post = Post::findOrFail($postId);

        if (auth()->id() !== $post->user_id) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
