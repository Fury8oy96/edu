<?php

use App\Http\Requests\VerifyOtpRequest;
use App\Models\Students;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

test('validates required fields', function () {
    $request = new VerifyOtpRequest();
    $validator = Validator::make([], $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('email'))->toBeTrue();
    expect($validator->errors()->has('otp'))->toBeTrue();
});

test('validates email format', function () {
    $request = new VerifyOtpRequest();
    $data = [
        'email' => 'invalid-email',
        'otp' => '123456',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('email'))->toBeTrue();
});

test('validates email exists in students table', function () {
    $request = new VerifyOtpRequest();
    $data = [
        'email' => 'nonexistent@example.com',
        'otp' => '123456',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('email'))->toBeTrue();
});

test('validates otp is exactly 6 characters', function () {
    Students::factory()->create(['email' => 'test@example.com']);
    
    $request = new VerifyOtpRequest();
    
    // Test with less than 6 characters
    $data = [
        'email' => 'test@example.com',
        'otp' => '12345',
    ];
    $validator = Validator::make($data, $request->rules());
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('otp'))->toBeTrue();
    
    // Test with more than 6 characters
    $data = [
        'email' => 'test@example.com',
        'otp' => '1234567',
    ];
    $validator = Validator::make($data, $request->rules());
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('otp'))->toBeTrue();
});

test('validates otp is numeric', function () {
    Students::factory()->create(['email' => 'test@example.com']);
    
    $request = new VerifyOtpRequest();
    $data = [
        'email' => 'test@example.com',
        'otp' => 'abc123',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('otp'))->toBeTrue();
});

test('validates otp matches regex pattern', function () {
    Students::factory()->create(['email' => 'test@example.com']);
    
    $request = new VerifyOtpRequest();
    
    // Test with special characters
    $data = [
        'email' => 'test@example.com',
        'otp' => '12-456',
    ];
    $validator = Validator::make($data, $request->rules());
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('otp'))->toBeTrue();
    
    // Test with letters
    $data = [
        'email' => 'test@example.com',
        'otp' => '12a456',
    ];
    $validator = Validator::make($data, $request->rules());
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('otp'))->toBeTrue();
});

test('passes validation with valid data', function () {
    Students::factory()->create(['email' => 'test@example.com']);
    
    $request = new VerifyOtpRequest();
    $data = [
        'email' => 'test@example.com',
        'otp' => '123456',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('custom error messages are defined', function () {
    $request = new VerifyOtpRequest();
    $messages = $request->messages();
    
    expect($messages)->toHaveKey('email.required');
    expect($messages)->toHaveKey('email.email');
    expect($messages)->toHaveKey('email.exists');
    expect($messages)->toHaveKey('otp.required');
    expect($messages)->toHaveKey('otp.size');
    expect($messages)->toHaveKey('otp.regex');
});

