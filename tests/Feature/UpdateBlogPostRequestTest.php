<?php

use App\Http\Requests\UpdateBlogPostRequest;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Students;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

test('authorize returns true when user is the post author', function () {
    $student = Students::factory()->create();
    $blogPost = BlogPost::factory()->create(['student_id' => $student->id]);
    
    $request = UpdateBlogPostRequest::create(
        "/api/v1/blog-posts/{$blogPost->id}",
        'PUT',
        []
    );
    $request->setUserResolver(fn() => $student);
    $request->setRouteResolver(function () use ($blogPost) {
        $route = Mockery::mock(\Illuminate\Routing\Route::class);
        $route->shouldReceive('parameter')->with('id', null)->andReturn($blogPost->id);
        return $route;
    });
    
    expect($request->authorize())->toBeTrue();
});

test('authorize returns false when user is not the post author', function () {
    $author = Students::factory()->create();
    $otherStudent = Students::factory()->create();
    $blogPost = BlogPost::factory()->create(['student_id' => $author->id]);
    
    $request = UpdateBlogPostRequest::create(
        "/api/v1/blog-posts/{$blogPost->id}",
        'PUT',
        []
    );
    $request->setUserResolver(fn() => $otherStudent);
    $request->setRouteResolver(function () use ($blogPost) {
        $route = Mockery::mock(\Illuminate\Routing\Route::class);
        $route->shouldReceive('parameter')->with('id', null)->andReturn($blogPost->id);
        return $route;
    });
    
    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when blog post does not exist', function () {
    $student = Students::factory()->create();
    
    $request = UpdateBlogPostRequest::create(
        '/api/v1/blog-posts/99999',
        'PUT',
        []
    );
    $request->setUserResolver(fn() => $student);
    $request->setRouteResolver(function () {
        $route = Mockery::mock(\Illuminate\Routing\Route::class);
        $route->shouldReceive('parameter')->with('id', null)->andReturn(99999);
        return $route;
    });
    
    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when user is not authenticated', function () {
    $blogPost = BlogPost::factory()->create();
    
    $request = UpdateBlogPostRequest::create(
        "/api/v1/blog-posts/{$blogPost->id}",
        'PUT',
        []
    );
    $request->setUserResolver(fn() => null);
    $request->setRouteResolver(function () use ($blogPost) {
        $route = Mockery::mock(\Illuminate\Routing\Route::class);
        $route->shouldReceive('parameter')->with('id', null)->andReturn($blogPost->id);
        return $route;
    });
    
    expect($request->authorize())->toBeFalse();
});

test('passes validation with empty data', function () {
    $request = new UpdateBlogPostRequest();
    $data = [];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('validates title minimum length', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'title' => 'ab', // 2 characters (less than 3)
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('title'))->toBeTrue();
});

test('validates title maximum length', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'title' => str_repeat('a', 201), // 201 characters (more than 200)
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('title'))->toBeTrue();
});

test('validates content minimum length', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'content' => 'Short', // 5 characters (less than 10)
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('content'))->toBeTrue();
});

test('validates content maximum length', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'content' => str_repeat('a', 50001), // 50001 characters (more than 50000)
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('content'))->toBeTrue();
});

test('validates status must be draft or published', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'status' => 'invalid_status',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('status'))->toBeTrue();
});

test('validates category_id must exist in categories table', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'category_id' => 99999, // Non-existent category
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('category_id'))->toBeTrue();
});

test('validates tags must be an array', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'tags' => 'not-an-array',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('tags'))->toBeTrue();
});

test('validates each tag minimum length', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'tags' => ['a'], // 1 character (less than 2)
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('tags.0'))->toBeTrue();
});

test('validates each tag maximum length', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'tags' => [str_repeat('a', 31)], // 31 characters (more than 30)
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('tags.0'))->toBeTrue();
});

test('validates featured_image must be an image', function () {
    $request = new UpdateBlogPostRequest();
    $file = UploadedFile::fake()->create('document.pdf', 100);
    
    $data = [
        'featured_image' => $file,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('featured_image'))->toBeTrue();
});

test('validates featured_image must be correct mime type', function () {
    $request = new UpdateBlogPostRequest();
    $file = UploadedFile::fake()->create('image.bmp', 100); // BMP not allowed
    
    $data = [
        'featured_image' => $file,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('featured_image'))->toBeTrue();
});

test('validates featured_image maximum size', function () {
    $request = new UpdateBlogPostRequest();
    $file = UploadedFile::fake()->image('large-image.jpg')->size(5121); // 5121 KB (more than 5120)
    
    $data = [
        'featured_image' => $file,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('featured_image'))->toBeTrue();
});

test('passes validation with only title', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'title' => 'Valid Title',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('passes validation with only content', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'content' => 'This is valid content with more than 10 characters.',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('passes validation with only status', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'status' => 'published',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('passes validation with partial fields', function () {
    $category = Category::factory()->create();
    
    $request = new UpdateBlogPostRequest();
    $data = [
        'title' => 'Updated Title',
        'category_id' => $category->id,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('passes validation with all valid fields', function () {
    $category = Category::factory()->create();
    
    $request = new UpdateBlogPostRequest();
    $file = UploadedFile::fake()->image('featured.jpg')->size(1024); // 1MB
    
    $data = [
        'title' => 'Updated Blog Post Title',
        'content' => 'This is updated content with all fields populated and valid.',
        'status' => 'published',
        'category_id' => $category->id,
        'tags' => ['laravel', 'php', 'web-development'],
        'featured_image' => $file,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('accepts draft status', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'status' => 'draft',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('accepts published status', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'status' => 'published',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('accepts null category_id', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'category_id' => null,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('accepts valid jpeg image', function () {
    $request = new UpdateBlogPostRequest();
    $file = UploadedFile::fake()->image('photo.jpeg')->size(1024);
    
    $data = [
        'featured_image' => $file,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('accepts valid png image', function () {
    $request = new UpdateBlogPostRequest();
    $file = UploadedFile::fake()->image('photo.png')->size(1024);
    
    $data = [
        'featured_image' => $file,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('accepts valid gif image', function () {
    $request = new UpdateBlogPostRequest();
    $file = UploadedFile::fake()->image('photo.gif')->size(1024);
    
    $data = [
        'featured_image' => $file,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('accepts valid webp image', function () {
    $request = new UpdateBlogPostRequest();
    $file = UploadedFile::fake()->image('photo.webp')->size(1024);
    
    $data = [
        'featured_image' => $file,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('accepts image at maximum size limit', function () {
    $request = new UpdateBlogPostRequest();
    $file = UploadedFile::fake()->image('large.jpg')->size(5120); // Exactly 5120 KB
    
    $data = [
        'featured_image' => $file,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('accepts valid tags', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'tags' => ['laravel', 'php', 'web-development'],
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('custom error messages are defined', function () {
    $request = new UpdateBlogPostRequest();
    $messages = $request->messages();
    
    expect($messages)->toHaveKey('title.string');
    expect($messages)->toHaveKey('title.min');
    expect($messages)->toHaveKey('title.max');
    expect($messages)->toHaveKey('content.string');
    expect($messages)->toHaveKey('content.min');
    expect($messages)->toHaveKey('content.max');
    expect($messages)->toHaveKey('status.in');
    expect($messages)->toHaveKey('category_id.exists');
    expect($messages)->toHaveKey('tags.array');
    expect($messages)->toHaveKey('tags.*.min');
    expect($messages)->toHaveKey('tags.*.max');
    expect($messages)->toHaveKey('featured_image.image');
    expect($messages)->toHaveKey('featured_image.mimes');
    expect($messages)->toHaveKey('featured_image.max');
});
