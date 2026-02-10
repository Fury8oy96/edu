<?php

use App\Exceptions\AttemptAlreadySubmittedException;
use App\Exceptions\AttemptNotFoundException;
use App\Exceptions\DeadlineExceededException;
use App\Exceptions\MaxAttemptsExceededException;
use App\Exceptions\NotEnrolledException;
use App\Exceptions\QuizNotFoundException;
use App\Exceptions\UnauthorizedAttemptAccessException;
use Illuminate\Http\Request;

test('QuizNotFoundException returns 404 with correct message', function () {
    $exception = new QuizNotFoundException();
    $request = Request::create('/test', 'GET');
    
    $response = $exception->render($request);
    
    expect($response->getStatusCode())->toBe(404)
        ->and($response->getData(true))->toBe([
            'message' => 'Quiz not found'
        ]);
});

test('NotEnrolledException returns 403 with correct message', function () {
    $exception = new NotEnrolledException();
    $request = Request::create('/test', 'GET');
    
    $response = $exception->render($request);
    
    expect($response->getStatusCode())->toBe(403)
        ->and($response->getData(true))->toBe([
            'message' => 'You must be enrolled in this course to access the quiz'
        ]);
});

test('MaxAttemptsExceededException returns 409 with correct message', function () {
    $exception = new MaxAttemptsExceededException();
    $request = Request::create('/test', 'POST');
    
    $response = $exception->render($request);
    
    expect($response->getStatusCode())->toBe(409)
        ->and($response->getData(true))->toBe([
            'message' => 'Maximum quiz attempts exceeded'
        ]);
});

test('AttemptNotFoundException returns 404 with correct message', function () {
    $exception = new AttemptNotFoundException();
    $request = Request::create('/test', 'GET');
    
    $response = $exception->render($request);
    
    expect($response->getStatusCode())->toBe(404)
        ->and($response->getData(true))->toBe([
            'message' => 'Quiz attempt not found'
        ]);
});

test('AttemptAlreadySubmittedException returns 400 with correct message', function () {
    $exception = new AttemptAlreadySubmittedException();
    $request = Request::create('/test', 'POST');
    
    $response = $exception->render($request);
    
    expect($response->getStatusCode())->toBe(400)
        ->and($response->getData(true))->toBe([
            'message' => 'This quiz attempt has already been submitted'
        ]);
});

test('DeadlineExceededException returns 400 with correct message', function () {
    $exception = new DeadlineExceededException();
    $request = Request::create('/test', 'POST');
    
    $response = $exception->render($request);
    
    expect($response->getStatusCode())->toBe(400)
        ->and($response->getData(true))->toBe([
            'message' => 'Quiz deadline has passed'
        ]);
});

test('UnauthorizedAttemptAccessException returns 403 with correct message', function () {
    $exception = new UnauthorizedAttemptAccessException();
    $request = Request::create('/test', 'GET');
    
    $response = $exception->render($request);
    
    expect($response->getStatusCode())->toBe(403)
        ->and($response->getData(true))->toBe([
            'message' => 'You do not have permission to access this quiz attempt'
        ]);
});

test('all quiz exceptions extend Exception class', function () {
    expect(new QuizNotFoundException())->toBeInstanceOf(Exception::class)
        ->and(new NotEnrolledException())->toBeInstanceOf(Exception::class)
        ->and(new MaxAttemptsExceededException())->toBeInstanceOf(Exception::class)
        ->and(new AttemptNotFoundException())->toBeInstanceOf(Exception::class)
        ->and(new AttemptAlreadySubmittedException())->toBeInstanceOf(Exception::class)
        ->and(new DeadlineExceededException())->toBeInstanceOf(Exception::class)
        ->and(new UnauthorizedAttemptAccessException())->toBeInstanceOf(Exception::class);
});

test('all quiz exceptions return JsonResponse', function () {
    $request = Request::create('/test', 'GET');
    
    expect((new QuizNotFoundException())->render($request))->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and((new NotEnrolledException())->render($request))->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and((new MaxAttemptsExceededException())->render($request))->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and((new AttemptNotFoundException())->render($request))->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and((new AttemptAlreadySubmittedException())->render($request))->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and((new DeadlineExceededException())->render($request))->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and((new UnauthorizedAttemptAccessException())->render($request))->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
});
