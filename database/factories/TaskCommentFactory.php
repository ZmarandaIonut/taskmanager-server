<?php

namespace Database\Factories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaskComment>
 */
class TaskCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'comment' => 'Comment' . strtoupper(fake()->randomLetter()) . fake()->randomLetter() . ' ' . fake()->randomNumber(2),
            'task_id' => Task::inRandomOrder()->first()->id,
        ];
    }
}
