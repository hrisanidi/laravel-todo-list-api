<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NoteFactory extends Factory
{
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(),
            'content' => $this->faker->paragraphs(3, true),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
            'completed' => $this->faker->boolean(30),
            'due_date' => $this->faker->optional(0.7)->dateTimeBetween('now', '+1 month'),
            'tags' => $this->faker->optional(0.5)->words(3),
        ];
    }
}
