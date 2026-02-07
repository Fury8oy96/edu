<?php

use App\Http\Requests\RegisterRequest;
use App\Models\Students;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

test('validates required fields', function () {
    $request = new RegisterRequest();
    $validator = Validator::make([], $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('name'))->toBeTrue();
    expect($validator->errors()->has('email'))->toBeTrue();
    expect($validator->errors()->has('password'))->toBeTrue();
    expect($validator->errors()->has('profession'))->toBeTrue();
});

test('validates email format', function () {
    $request = new RegisterRequest();
    $data = [
        'name' => 'John Doe',
        'email' => 'invalid-email',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'profession' => 'Developer',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('email'))->toBeTrue();
});

test('validates email uniqueness', function () {
    // Create existing student
    Students::factory()->create(['email' => 'existing@example.com']);
    
    $request = new RegisterRequest();
    $data = [
        'name' => 'John Doe',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'profession' => 'Developer',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('email'))->toBeTrue();
});

test('validates password minimum length', function () {
    $request = new RegisterRequest();
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
        'profession' => 'Developer',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('password'))->toBeTrue();
});

test('validates password confirmation', function () {
    $request = new RegisterRequest();
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different123',
        'profession' => 'Developer',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('password'))->toBeTrue();
});

test('validates name max length', function () {
    $request = new RegisterRequest();
    $data = [
        'name' => str_repeat('a', 256), // 256 characters
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'profession' => 'Developer',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('name'))->toBeTrue();
});

test('validates profession max length', function () {
    $request = new RegisterRequest();
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'profession' => str_repeat('a', 256), // 256 characters
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('profession'))->toBeTrue();
});

test('passes validation with valid data', function () {
    $request = new RegisterRequest();
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'profession' => 'Software Developer',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('custom error messages are defined', function () {
    $request = new RegisterRequest();
    $messages = $request->messages();
    
    expect($messages)->toHaveKey('name.required');
    expect($messages)->toHaveKey('email.required');
    expect($messages)->toHaveKey('email.email');
    expect($messages)->toHaveKey('email.unique');
    expect($messages)->toHaveKey('password.required');
    expect($messages)->toHaveKey('password.min');
    expect($messages)->toHaveKey('password.confirmed');
    expect($messages)->toHaveKey('profession.required');
});
