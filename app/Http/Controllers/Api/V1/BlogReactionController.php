<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Services\BlogReactionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class BlogReactionController extends Controller
{
    public function __construct(private BlogReactionService $reactionService)
    {
    }

    /**
     * POST /api/v1/blog-posts/{blogPostId}/reactions
     * Toggle reaction on a blog post
     */
    public function toggle(int $blogPostId): JsonResponse
    {
        $blogPost = BlogPost::findOrFail($blogPostId);
        $student = request()->user();

        // Authorize - must be published
        if (!$blogPost->isPublished()) {
            throw new AuthorizationException('Cannot react to a draft blog post.');
        }

        $result = $this->reactionService->toggleReaction($blogPost, $student);

        return response()->json([
            'action' => $result['action'],
            'total_reactions' => $result['total_reactions'],
        ]);
    }
}
