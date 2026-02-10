<?php

use App\Models\Event;
use App\Models\Students;
use App\Services\EventService;
use App\Services\RegistrationService;
use App\Services\ParticipationService;
use App\Services\StateTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('event service can create an event', function () {
    $eventService = app(EventService::class);
    
    $eventData = [
        'title' => 'Test Event',
        'description' => 'This is a test event',
        'start_time' => now()->addHours(2),
        'end_time' => now()->addHours(4),
        'max_participants' => 50,
    ];
    
    $event = $eventService->createEvent($eventData);
    
    expect($event)->toBeInstanceOf(Event::class)
        ->and($event->title)->toBe('Test Event')
        ->and($event->state)->toBe('upcoming')
        ->and($event->registration_count)->toBe(0);
});

test('verified student can register for upcoming event', function () {
    $eventService = app(EventService::class);
    $registrationService = app(RegistrationService::class);
    
    // Create an upcoming event
    $event = $eventService->createEvent([
        'title' => 'Test Event',
        'description' => 'This is a test event',
        'start_time' => now()->addHours(2),
        'end_time' => now()->addHours(4),
        'max_participants' => 50,
    ]);
    
    // Create a verified student
    $student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Register student for event
    $registration = $registrationService->register($event->id, $student->id);
    
    expect($registration)->not->toBeNull()
        ->and($registration->event_id)->toBe($event->id)
        ->and($registration->student_id)->toBe($student->id);
    
    // Verify registration count increased
    $event->refresh();
    expect($event->registration_count)->toBe(1);
});

test('event transitions from upcoming to ongoing', function () {
    $eventService = app(EventService::class);
    $stateTransitionService = app(StateTransitionService::class);
    
    // Create an event with start time that will trigger transition
    $event = $eventService->createEvent([
        'title' => 'Test Event',
        'description' => 'This is a test event',
        'start_time' => now()->addSeconds(2),
        'end_time' => now()->addHours(2),
        'max_participants' => 50,
    ]);
    
    // Verify event is initially upcoming
    expect($event->state)->toBe('upcoming');
    
    // Wait for start time to pass
    sleep(3);
    
    // Process state transitions
    $result = $stateTransitionService->processTransitions();
    
    // Verify event transitioned to ongoing
    $event->refresh();
    expect($event->state)->toBe('ongoing')
        ->and($result['transitioned_to_ongoing'])->toBeGreaterThanOrEqual(1);
});

test('registrations convert to participations when event transitions to ongoing', function () {
    $eventService = app(EventService::class);
    $registrationService = app(RegistrationService::class);
    $stateTransitionService = app(StateTransitionService::class);
    
    // Create an upcoming event with start time that will trigger transition
    $event = $eventService->createEvent([
        'title' => 'Test Event',
        'description' => 'This is a test event',
        'start_time' => now()->addSeconds(2), // Just slightly in the future
        'end_time' => now()->addHours(2),
        'max_participants' => 50,
    ]);
    
    // Create verified students and register them
    $students = Students::factory()->count(3)->create([
        'email_verified_at' => now(),
    ]);
    
    foreach ($students as $student) {
        $registrationService->register($event->id, $student->id);
    }
    
    // Verify registrations exist
    $event->refresh();
    expect($event->registration_count)->toBe(3);
    
    // Wait for start time to pass
    sleep(3);
    
    // Process state transitions
    $stateTransitionService->processTransitions();
    
    // Verify event transitioned and registrations converted to participations
    $event->refresh();
    expect($event->state)->toBe('ongoing')
        ->and($event->registration_count)->toBe(0)
        ->and($event->participation_count)->toBe(3);
});

test('student can join ongoing event', function () {
    $eventService = app(EventService::class);
    $participationService = app(ParticipationService::class);
    
    // Create an ongoing event
    $event = Event::factory()->ongoing()->create();
    
    // Create a student
    $student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Join event
    $participation = $participationService->join($event->id, $student->id);
    
    expect($participation)->not->toBeNull()
        ->and($participation->event_id)->toBe($event->id)
        ->and($participation->student_id)->toBe($student->id);
    
    // Verify participation count increased
    $event->refresh();
    expect($event->participation_count)->toBe(1);
});

test('event transitions from ongoing to past and creates attendance records', function () {
    $stateTransitionService = app(StateTransitionService::class);
    $participationService = app(ParticipationService::class);
    
    // Create an ongoing event that should transition to past
    $event = Event::factory()->ongoing()->create([
        'end_time' => now()->subMinute(),
    ]);
    
    // Create students and add participations
    $students = Students::factory()->count(2)->create([
        'email_verified_at' => now(),
    ]);
    
    foreach ($students as $student) {
        $participationService->join($event->id, $student->id);
    }
    
    // Verify participations exist
    $event->refresh();
    expect($event->participation_count)->toBe(2);
    
    // Process state transitions
    $stateTransitionService->processTransitions();
    
    // Verify event transitioned and participations converted to attendance
    $event->refresh();
    expect($event->state)->toBe('past')
        ->and($event->participation_count)->toBe(0)
        ->and($event->attendance_count)->toBe(2);
});
