<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\StateTransitionService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Process event state transitions every minute (Requirement 2.3)
Schedule::call(function () {
    $service = app(StateTransitionService::class);
    $result = $service->processTransitions();
    
    \Illuminate\Support\Facades\Log::info('Scheduled state transitions completed', $result);
})->everyMinute()->name('events:process-transitions');
