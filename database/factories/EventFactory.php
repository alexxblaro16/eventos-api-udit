<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ciudades = ['Madrid', 'Barcelona', 'Valencia', 'Sevilla', 'Bilbao', 'Zaragoza'];

        return [
            'user_id' => User::factory()->state(['is_organizer' => true]),
            'category_id' => Category::factory(),
            'title' => fake()->sentence(rand(2, 4)),
            'description' => fake()->paragraph(),
            'city' => fake()->randomElement($ciudades),
            'venue' => fake()->company(),
            'starts_at' => fake()->dateTimeBetween('+1 week', '+2 months'),
            'capacity' => fake()->numberBetween(20, 200),
            'status' => 'active',
        ];
    }

    /**
     * Evento que ya ha pasado.
     */
    public function pasado(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => fake()->dateTimeBetween('-2 months', '-1 day'),
        ]);
    }

    /**
     * Evento cancelado.
     */
    public function cancelado(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
