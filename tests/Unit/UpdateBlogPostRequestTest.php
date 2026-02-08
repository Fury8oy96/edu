<?php

use App\Http\Requests\UpdateBlogPostRequest;
use App\Models\BlogPost;
use App\Models\Students;
use Illuminate\Support\Facades\Validator;

test('validation rules are correctly defined', function () {
    $request = new UpdateBlogPostRequest();
    $rules = $request->rules();
    
    expect($rules)->toHaveKey('title');
    expect($rules)->toHaveKey('content');
    expect($rules)->toHaveKey('status');
    expect($rules)->toHaveKey('category_id');
    expect($rules)->toHaveKey('tags');
    expect($rules)->toHaveKey('tags.*');
    expect($rules)->toHaveKey('featured_image');
});

test('title validation rules are correct', function () {
    $request = new UpdateBlogPostRequest();
    $rules = $request->rules();
    
    expect($rules['title'])->toContain('sometimes');
    expect($rules['title'])->toContain('string');
    expect($rules['title'])->toContain('min:3');
    expect($rules['title'])->toContain('max:200');
});

test('content validation rules are correct', function () {
    $request = new UpdateBlogPostRequest();
    $rules = $request->rules();
    
    expect($rules['content'])->toContain('sometimes');
    expect($rules['content'])->toContain('string');
    expect($rules['content'])->toContain('min:10');
    expect($rules['content'])->toContain('max:50000');
});

test('status validation rules are correct', function () {
    $request = new UpdateBlogPostRequest();
    $rules = $request->rules();
    
    expect($rules['status'])->toContain('sometimes');
    expect($rules['status'])->toContain('in:draft,published');
});

test('category_id validation rules are correct', function () {
    $request = new UpdateBlogPostRequest();
    $rules = $request->rules();
    
    expect($rules['category_id'])->toContain('nullable');
    expect($rules['category_id'])->toContain('exists:categories,id');
});

test('tags validation rules are correct', function () {
    $request = new UpdateBlogPostRequest();
    $rules = $request->rules();
    
    expect($rules['tags'])->toContain('nullable');
    expect($rules['tags'])->toContain('array');
    expect($rules['tags.*'])->toContain('string');
    expect($rules['tags.*'])->toContain('min:2');
    expect($rules['tags.*'])->toContain('max:30');
});

test('featured_image validation rules are correct', function () {
    $request = new UpdateBlogPostRequest();
    $rules = $request->rules();
    
    expect($rules['featured_image'])->toContain('nullable');
    expect($rules['featured_image'])->toContain('image');
    expect($rules['featured_image'])->toContain('mimes:jpeg,png,jpg,gif,webp');
    expect($rules['featured_image'])->toContain('max:5120');
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

test('validates title minimum length without database', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'title' => 'ab',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('title'))->toBeTrue();
});

test('validates title maximum length without database', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'title' => str_repeat('a', 201),
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('title'))->toBeTrue();
});

test('validates content minimum length without database', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'content' => 'Short',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('content'))->toBeTrue();
});

test('validates content maximum length without database', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'content' => str_repeat('a', 50001),
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('content'))->toBeTrue();
});

test('validates status values without database', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'status' => 'invalid_status',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('status'))->toBeTrue();
});

test('accepts draft status without database', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'status' => 'draft',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('accepts published status without database', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'status' => 'published',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('validates tags must be array without database', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'tags' => 'not-an-array',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('tags'))->toBeTrue();
});

test('validates tag minimum length without database', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'tags' => ['a'],
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('tags.0'))->toBeTrue();
});

test('validates tag maximum length without database', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'tags' => [str_repeat('a', 31)],
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('tags.0'))->toBeTrue();
});

test('passes validation with empty data without database', function () {
    $request = new UpdateBlogPostRequest();
    $data = [];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('passes validation with only title without database', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'title' => 'Valid Title',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('passes validation with only content without database', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'content' => 'This is valid content with more than 10 characters.',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('passes validation with valid tags without database', function () {
    $request = new UpdateBlogPostRequest();
    $data = [
        'tags' => ['laravel', 'php', 'web-development'],
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});
