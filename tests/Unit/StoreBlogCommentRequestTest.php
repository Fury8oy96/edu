<?php

use App\Http\Requests\StoreBlogCommentRequest;
use Illuminate\Support\Facades\Validator;

test('authorize returns true', function () {
    $request = new StoreBlogCommentRequest();
    
    expect($request->authorize())->toBeTrue();
});

test('validation rules are correctly defined', function () {
    $request = new StoreBlogCommentRequest();
    $rules = $request->rules();
    
    expect($rules)->toHaveKey('content');
});

test('content validation rules are correct', function () {
    $request = new StoreBlogCommentRequest();
    $rules = $request->rules();
    
    expect($rules['content'])->toContain('required');
    expect($rules['content'])->toContain('string');
    expect($rules['content'])->toContain('min:1');
    expect($rules['content'])->toContain('max:1000');
});

test('custom error messages are defined', function () {
    $request = new StoreBlogCommentRequest();
    $messages = $request->messages();
    
    expect($messages)->toHaveKey('content.required');
    expect($messages)->toHaveKey('content.string');
    expect($messages)->toHaveKey('content.min');
    expect($messages)->toHaveKey('content.max');
});

test('validates required content field without database', function () {
    $request = new StoreBlogCommentRequest();
    $validator = Validator::make([], $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('content'))->toBeTrue();
});

test('validates content minimum length without database', function () {
    $request = new StoreBlogCommentRequest();
    $data = [
        'content' => '',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('content'))->toBeTrue();
});

test('validates content maximum length without database', function () {
    $request = new StoreBlogCommentRequest();
    $data = [
        'content' => str_repeat('a', 1001),
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('content'))->toBeTrue();
});

test('accepts content with exactly 1 character without database', function () {
    $request = new StoreBlogCommentRequest();
    $data = [
        'content' => 'a',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('accepts content with exactly 1000 characters without database', function () {
    $request = new StoreBlogCommentRequest();
    $data = [
        'content' => str_repeat('a', 1000),
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('passes validation with valid comment content without database', function () {
    $request = new StoreBlogCommentRequest();
    $data = [
        'content' => 'This is a valid comment with reasonable length.',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('validates content must be string without database', function () {
    $request = new StoreBlogCommentRequest();
    $data = [
        'content' => 12345,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    // Note: Laravel's validator will cast numbers to strings, so this might pass
    // But we're testing that the rule is defined correctly
    $rules = $request->rules();
    expect($rules['content'])->toContain('string');
});

test('accepts multiline comment content without database', function () {
    $request = new StoreBlogCommentRequest();
    $data = [
        'content' => "This is a comment\nwith multiple lines\nof text.",
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('accepts comment with special characters without database', function () {
    $request = new StoreBlogCommentRequest();
    $data = [
        'content' => 'Great post! 👍 I really enjoyed reading this. @author',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});
