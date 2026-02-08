<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBlogPostRequest;
use App\Http\Requests\UpdateBlogPostRequest;
use App\Http\Resources\BlogPostResource;
use App\Models\BlogPost;
use App\Services\BlogPostService;
use App\Services\ImageUploadService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogPostController extends Controller
{
    use AuthorizesRequests;
    /**
     * Create a new BlogPostController instance
     * 
     * @param BlogPostService $blogPostService
     * @param ImageUploadService $imageUploadService
     */
    public function __construct(
        private BlogPostService $blogPostService,
        private ImageUploadService $imageUploadService
    ) {}

    /**
     * List published blog posts with filters and pagination
     * 
     * GET /api/v1/blog-posts
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Get filters from request
        $filters = [
            'search' => $request->input('search'),
            'category_id' => $request->input('category_id'),
            'tag_id' => $request->input('tag_id'),
        ];

        // Get per_page parameter (default 15, max 100)
        $perPage = min((int) $request->input('per_page', 15), 100);

        // Call service to get published blog posts
        $blogPosts = $this->blogPostService->getPublishedBlogPosts($filters, $perPage);

        // Return BlogPostResource collection with pagination
        return BlogPostResource::collection($blogPosts)->response();
    }

    /**
     * Get a single blog post
     * 
     * GET /api/v1/blog-posts/{id}
     * 
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(int $id, Request $request): JsonResponse
    {
        // Get authenticated user (may be null for public access)
        $student = $request->user();

        // Call service to get blog post with authorization check
        $blogPost = $this->blogPostService->getBlogPost($id, $student);

        // Return BlogPostResource
        return (new BlogPostResource($blogPost))->response();
    }

    /**
     * Get authenticated student's blog posts
     * 
     * GET /api/v1/blog-posts/my-posts
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function myPosts(Request $request): JsonResponse
    {
        // Get authenticated student
        $student = $request->user();

        // Get per_page parameter (default 15, max 100)
        $perPage = min((int) $request->input('per_page', 15), 100);

        // Call service to get student's blog posts
        $blogPosts = $this->blogPostService->getStudentBlogPosts($student->id, $perPage);

        // Return BlogPostResource collection with pagination
        return BlogPostResource::collection($blogPosts)->response();
    }

    /**
     * Create a new blog post
     * 
     * POST /api/v1/blog-posts
     * 
     * @param StoreBlogPostRequest $request
     * @return JsonResponse
     */
    public function store(StoreBlogPostRequest $request): JsonResponse
    {
        // Get authenticated student
        $student = $request->user();

        // Prepare data for blog post creation
        $data = $request->validated();

        // Handle image upload if provided
        if ($request->hasFile('featured_image')) {
            $imageUrl = $this->imageUploadService->uploadImage(
                $request->file('featured_image'),
                'blog-images'
            );
            $data['featured_image'] = $imageUrl;
        }

        // Call service to create blog post
        $blogPost = $this->blogPostService->createBlogPost($student, $data);

        // Return BlogPostResource with 201 status
        return (new BlogPostResource($blogPost))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a blog post
     * 
     * PUT /api/v1/blog-posts/{id}
     * 
     * @param UpdateBlogPostRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateBlogPostRequest $request, int $id): JsonResponse
    {
        // Find the blog post
        $blogPost = BlogPost::findOrFail($id);

        // Authorize using policy (already checked in UpdateBlogPostRequest, but double-check)
        $this->authorize('update', $blogPost);

        // Prepare data for blog post update
        $data = $request->validated();

        // Handle image upload if provided
        if ($request->hasFile('featured_image')) {
            // Delete old image if exists
            if ($blogPost->featured_image) {
                $this->imageUploadService->deleteImage($blogPost->featured_image);
            }

            // Upload new image
            $imageUrl = $this->imageUploadService->uploadImage(
                $request->file('featured_image'),
                'blog-images'
            );
            $data['featured_image'] = $imageUrl;
        }

        // Call service to update blog post
        $updatedBlogPost = $this->blogPostService->updateBlogPost($blogPost, $data);

        // Return BlogPostResource
        return (new BlogPostResource($updatedBlogPost))->response();
    }

    /**
     * Delete a blog post
     * 
     * DELETE /api/v1/blog-posts/{id}
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        // Find the blog post
        $blogPost = BlogPost::findOrFail($id);

        // Authorize using policy
        $this->authorize('delete', $blogPost);

        // Call service to delete blog post
        $this->blogPostService->deleteBlogPost($blogPost);

        // Return 204 No Content
        return response()->json(null, 204);
    }

    /**
     * Publish a draft blog post
     * 
     * POST /api/v1/blog-posts/{id}/publish
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function publish(int $id): JsonResponse
    {
        // Find the blog post
        $blogPost = BlogPost::findOrFail($id);

        // Authorize using policy (only author can publish)
        $this->authorize('update', $blogPost);

        // Call service to publish blog post
        $publishedBlogPost = $this->blogPostService->publishBlogPost($blogPost);

        // Reload relationships for resource
        $publishedBlogPost->load(['student', 'category', 'tags']);

        // Return BlogPostResource
        return (new BlogPostResource($publishedBlogPost))->response();
    }

    /**
     * Unpublish a blog post (revert to draft)
     * 
     * POST /api/v1/blog-posts/{id}/unpublish
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function unpublish(int $id): JsonResponse
    {
        // Find the blog post
        $blogPost = BlogPost::findOrFail($id);

        // Authorize using policy (only author can unpublish)
        $this->authorize('update', $blogPost);

        // Call service to unpublish blog post
        $unpublishedBlogPost = $this->blogPostService->unpublishBlogPost($blogPost);

        // Reload relationships for resource
        $unpublishedBlogPost->load(['student', 'category', 'tags']);

        // Return BlogPostResource
        return (new BlogPostResource($unpublishedBlogPost))->response();
    }
}
