<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Conversation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'private',
            'name' => null,
            'avatar' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Grup konuşması durumu
     */
    public function group(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'group',
            'name' => fake()->words(3, true) . ' Grubu',
        ]);
    }

    /**
     * Özel (private) konuşma durumu
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'private',
            'name' => null,
        ]);
    }
}
