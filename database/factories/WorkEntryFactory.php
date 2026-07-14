<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkEntry>
 */
class WorkEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'work_date' => today()->subDays(fake()->numberBetween(0, 30)),
            'start_time' => '08:00',
            'end_time' => '16:30',
            'note' => fake()->optional()->sentence(),
        ];
    }
}
