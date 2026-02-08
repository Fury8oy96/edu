<?php

use App\Http\Requests\StoreBlogPostRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

test('authorize returns true for authenticated students', function () {
    $request = new StoreBlogPostRequest();
    
    expect($request->authorize())->toBeTrue();
});

test('validates required fields', function () {
    $request = new StoreBlogPostRequest();
    $validator = Validator::make([], $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('title'))->toBeTrue();
    expect($validator->errors()->has('content'))->toBeTrue();
});

test('validates title minimum length', function () {
    $request = new StoreBlogPostRequest();
    $data = [
        'title' => 'ab', // 2 characters (less than 3)
        'content' => 'This is valid content with more than 10 characters.',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('title'))->toBeTrue();
});

test('validates title maximum length', function () {
    $request = new StoreBlogPostRequest();
    $data = [
        'title' => str_repeat('a', 201), // 201 characters (more than 200)
        'content' => 'This is valid content with more than 10 characters.',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('title'))->toBeTrue();
});

test('validates content minimum length', function () {
    $request = new StoreBlogPostRequest();
    $data = [
        'title' => 'Valid Title',
        'content' => 'Short', // 5 characters (less than 10)
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('content'))->toBeTrue();
});

test('validates content maximum length', function () {
    $request = new StoreBlogPostRequest();
    $data = [
        'title' => 'Valid Title',
        'content' => str_repeat('a', 50001), // 50001 characters (more than 50000)
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('content'))->toBeTrue();
});

test('validates status must be draft or published', function () {
    $request = new StoreBlogPostRequest();
    $data = [
        'title' => 'Valid Title',
        'content' => 'This is valid content with more than 10 characters.',
        'status' => 'invalid_status',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('status'))->toBeTrue();
});

test('validates category_id must exist in categories table', function () {
    $request = new StoreBlogPostRequest();
    $data = [
        'title' => 'Valid Title',
        'content' => 'This is valid content with more than 10 characters.',
        'category_id' => 99999, // Non-existent category
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('category_id'))->toBeTrue();
});

test('validates tags must be an array', function () {
    $request = new StoreBlogPostRequest();
    $data = [
        'title' => 'Valid Title',
        'content' => 'This is valid content with more than 10 characters.',
        'tags' => 'not-an-array',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('tags'))->toBeTrue();
});

test('validates each tag minimum length', function () {
    $request = new StoreBlogPostRequest();
    $data = [
        'title' => 'Valid Title',
        'content' => 'This is valid content with more than 10 characters.',
        'tags' => ['a'], // 1 character (less than 2)
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('tags.0'))->toBeTrue();
});

test('validates each tag maximum length', function () {
    $request = new StoreBlogPostRequest();
    $data = [
        'title' => 'Valid Title',
        'content' => 'This is valid content with more than 10 characters.',
        'tags' => [str_repeat('a', 31)], // 31 characters (more than 30)
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('tags.0'))->toBeTrue();
});

test('validates featured_image must be an image', function () {
    $request = new StoreBlogPostRequest();
    $file = UploadedFile::fake()->create('document.pdf', 100);
    
    $data = [
        'title' => 'Valid Title',
        'content' => 'This is valid content with more than 10 characters.',
        'featured_image' => $file,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('featured_image'))->toBeTrue();
});

test('validates featured_image must be correct mime type', function () {
    $request = new StoreBlogPostRequest();
    $file = UploadedFile::fake()->create('image.bmp', 100); // BMP not allowed
    
    $data = [
        'title' => 'Valid Title',
        'content' => 'This is valid content with more than 10 characters.',
        'featured_image' => $file,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('featured_image'))->toBeTrue();
});

test('validates featured_image maximum size', function () {
    $request = new StoreBlogPostRequest();
    $file = UploadedFile::fake()->image('large-image.jpg')->size(5121); // 5121 KB (more than 5120)
    
    $data = [
        'title' => 'Valid Title',
        'content' => 'This is valid content with more than 10 characters.',
        'featured_image' => $file,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('featured_image'))->toBeTrue();
});

test('passes validation with minimal valid data', function () {
    $request = new StoreBlogPostRequest();
    $data = [
        'title' => 'Valid Title',
        'content' => 'This is valid content with more than 10 characters.',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('passes validation with all valid fields', function () {
    $category = Category::factory()->create();
    
    $request = new StoreBlogPostRequest();
    $file = UploadedFile::fake()->image('featured.jpg')->size(1024); // 1MB
    
    $data = [
        'title' => 'Complete Blog Post Title',
        'content' => 'This is a complete blog post with all fields populated and valid content.',
        'status' => 'draft',
        'category_id' => $category->id,
        'tags' => ['laravel', 'php', 'web-development'],
        'featured_image' => $file,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('accepts draft status', function () {
    $request = new StoreBlogPostRequest();
    $data = [
        'title' => 'Valid Title',
        'content' => 'This is valid content with more than 10 characters.',
        'status' => 'draft',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('accepts published status', function () {
    $request = new StoreBlogPostRequest();
    $data = [
        'title' => 'Valid Title',
        'content' => 'This is valid content with more than 10 characters.',
        'status' => 'published',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('accepts null category_id', function () {
    $request = new StoreBlogPostRequest();
    $data = [
        'title' => 'Valid Title',
        'content' => 'This is valid content with more than 10 characters.',
        'category_id' => null,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('accepts valid jpeg image', function () {
    $request = new StoreBlogPostRequest();
    $file = UploadedFile::fake()->image('photo.jpeg')->size(1024);
    
    $data = [
        'title' => 'Valid Title',
        'content' => 'This is valid content with more than 10 characters.',
        'featured_image' => $file,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('accepts valid png image', function () {
    $request = new StoreBlogPostRequest();
    $file = UploadedFile::fake()->image('photo.png')->size(1024);
    
    $data = [
        'title' => 'Valid Title',
        'content' => 'This is valid content with more than 10 characters.',
        'featured_image' => $file,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('accepts valid gif image', function () {
    $request = new StoreBlogPostRequest();
    $file = UploadedFile::fake()->image('photo.gif')->size(1024);
    
    $data = [
        'title' => 'Valid Title',
        'content' => 'This is valid content with more than 10 characters.',
        'featured_image' => $file,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('accepts valid webp image', function () {
    $request = new StoreBlogPostRequest();
    $file = UploadedFile::fake()->image('photo.webp')->size(1024);
    
    $data = [
        'title' => 'Valid Title',
        'content' => 'This is valid content with more than 10 characters.',
        'featured_image' => $file,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('accepts image at maximum size limit', function () {
    $request = new StoreBlogPostRequest();
    $file = UploadedFile::fake()->image('large.jpg')->size(5120); // Exactly 5120 KB
    
    $data = [
        'title' => 'Valid Title',
        'content' => 'This is valid content with more than 10 characters.',
        'featured_image' => $file,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('custom error messages are defined', function () {
    $request = new StoreBlogPostRequest();
    $messages = $request->messages();
    
    expect($messages)->toHaveKey('title.required');
    expect($messages)->toHaveKey('title.min');
    expect($messages)->toHaveKey('title.max');
    expect($messages)->toHaveKey('content.required');
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
