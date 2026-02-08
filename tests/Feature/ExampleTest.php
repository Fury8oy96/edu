<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the application returns a successful response', function () {
    // Test a public API endpoint instead of the protected root route
    $response = $this->get('/api/v1/subscription-plans');

    $response->assertStatus(200);
});
