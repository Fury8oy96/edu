<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = $this->faker->dateTimeBetween('now', '+1 month');
        $endTime = $this->faker->dateTimeBetween($startTime, '+2 months');

        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'state' => 'upcoming',
            'max_participants' => $this->faker->optional()->numberBetween(10, 100),
            'registration_count' => 0,
            'participation_count' => 0,
            'attendance_count' => 0,
        ];
    }

    /**
     * Indicate that the event is in upcoming state.
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => 'upcoming',
            'start_time' => $this->faker->dateTimeBetween('+1 hour', '+1 month'),
            'end_time' => $this->faker->dateTimeBetween('+2 hours', '+2 months'),
        ]);
    }

    /**
     * Indicate that the event is in ongoing state.
     */
    public function ongoing(): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => 'ongoing',
            'start_time' => $this->faker->dateTimeBetween('-2 hours', '-1 hour'),
            'end_time' => $this->faker->dateTimeBetween('+1 hour', '+2 hours'),
        ]);
    }

    /**
     * Indicate that the event is in past state.
     */
    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => 'past',
            'start_time' => $this->faker->dateTimeBetween('-1 month', '-2 days'),
            'end_time' => $this->faker->dateTimeBetween('-2 days', '-1 day'),
        ]);
    }

    /**
     * Indicate that the event has unlimited capacity.
     */
    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_participants' => null,
        ]);
    }

    /**
     * Indicate that the event has a specific capacity.
     */
    public function withCapacity(int $capacity): static
    {
        return $this->state(fn (array $attributes) => [
            'max_participants' => $capacity,
        ]);
    }
}
