<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBlogCommentRequest;
use App\Http\Resources\BlogCommentResource;
use App\Models\BlogComment;
use App\Models\BlogPost;
use App\Services\BlogCommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class BlogCommentController extends Controller
{
    public function __construct(private BlogCommentService $commentService)
    {
    }

    /**
     * GET /api/v1/blog-posts/{blogPostId}/comments
     * Get comments for a blog post
     */
    public function index(int $blogPostId): JsonResponse
    {
        $perPage = request()->input('per_page', 20);
        $comments = $this->commentService->getComments($blogPostId, $perPage);

        return response()->json([
            'data' => BlogCommentResource::collection($comments),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/blog-posts/{blogPostId}/comments
     * Create a comment on a blog post
     */
    public function store(StoreBlogCommentRequest $request, int $blogPostId): JsonResponse
    {
        $blogPost = BlogPost::findOrFail($blogPostId);
        $student = $request->user();

        $comment = $this->commentService->createComment(
            $blogPost,
            $student,
            $request->input('content')
        );

        return response()->json([
            'data' => new BlogCommentResource($comment),
        ], Response::HTTP_CREATED);
    }

    /**
     * DELETE /api/v1/blog-comments/{id}
     * Delete a comment
     */
    public function destroy(int $id): JsonResponse
    {
        $comment = BlogComment::findOrFail($id);

        // Authorize using policy
        $this->authorize('delete', $comment);

        $this->commentService->deleteComment($comment);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
