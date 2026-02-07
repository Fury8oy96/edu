<?php

namespace Tests\Unit;

use App\Exceptions\AlreadyVerifiedException;
use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\InvalidOtpException;
use Illuminate\Http\Request;
use Tests\TestCase;

class ExceptionTest extends TestCase
{
    /**
     * Test InvalidCredentialsException returns 401 status code
     */
    public function test_invalid_credentials_exception_returns_401_status(): void
    {
        $exception = new InvalidCredentialsException();
        $request = Request::create('/test', 'POST');
        
        $response = $exception->render($request);
        
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Invalid credentials', $response->getData()->message);
    }

    /**
     * Test InvalidOtpException returns 400 status code
     */
    public function test_invalid_otp_exception_returns_400_status(): void
    {
        $exception = new InvalidOtpException();
        $request = Request::create('/test', 'POST');
        
        $response = $exception->render($request);
        
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Invalid or expired OTP', $response->getData()->message);
    }

    /**
     * Test AlreadyVerifiedException returns 400 status code
     */
    public function test_already_verified_exception_returns_400_status(): void
    {
        $exception = new AlreadyVerifiedException();
        $request = Request::create('/test', 'POST');
        
        $response = $exception->render($request);
        
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Email is already verified', $response->getData()->message);
    }

    /**
     * Test InvalidCredentialsException returns JSON response
     */
    public function test_invalid_credentials_exception_returns_json(): void
    {
        $exception = new InvalidCredentialsException();
        $request = Request::create('/test', 'POST');
        
        $response = $exception->render($request);
        
        $this->assertJson($response->getContent());
        $this->assertArrayHasKey('message', (array) $response->getData());
    }

    /**
     * Test InvalidOtpException returns JSON response
     */
    public function test_invalid_otp_exception_returns_json(): void
    {
        $exception = new InvalidOtpException();
        $request = Request::create('/test', 'POST');
        
        $response = $exception->render($request);
        
        $this->assertJson($response->getContent());
        $this->assertArrayHasKey('message', (array) $response->getData());
    }

    /**
     * Test AlreadyVerifiedException returns JSON response
     */
    public function test_already_verified_exception_returns_json(): void
    {
        $exception = new AlreadyVerifiedException();
        $request = Request::create('/test', 'POST');
        
        $response = $exception->render($request);
        
        $this->assertJson($response->getContent());
        $this->assertArrayHasKey('message', (array) $response->getData());
    }
}
