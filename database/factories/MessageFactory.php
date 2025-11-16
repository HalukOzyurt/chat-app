<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Message::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_id' => User::factory(),
            'message_type' => 'text',
            'content' => fake()->sentence(),
            'file_path' => null,
            'file_name' => null,
            'file_size' => null,
            'reply_to_message_id' => null,
            'is_edited' => false,
            'edited_at' => null,
        ];
    }

    /**
     * Metin mesajı
     */
    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_type' => 'text',
            'content' => fake()->paragraph(),
            'file_path' => null,
            'file_name' => null,
            'file_size' => null,
        ]);
    }

    /**
     * Resim mesajı
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_type' => 'image',
            'content' => null,
            'file_path' => 'messages/images/' . fake()->uuid() . '.jpg',
            'file_name' => fake()->word() . '.jpg',
            'file_size' => fake()->numberBetween(100000, 5000000),
        ]);
    }

    /**
     * Video mesajı
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_type' => 'video',
            'content' => null,
            'file_path' => 'messages/videos/' . fake()->uuid() . '.mp4',
            'file_name' => fake()->word() . '.mp4',
            'file_size' => fake()->numberBetween(1000000, 50000000),
        ]);
    }

    /**
     * Ses mesajı
     */
    public function audio(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_type' => 'audio',
            'content' => null,
            'file_path' => 'messages/audio/' . fake()->uuid() . '.mp3',
            'file_name' => fake()->word() . '.mp3',
            'file_size' => fake()->numberBetween(50000, 5000000),
        ]);
    }

    /**
     * Dosya mesajı
     */
    public function file(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_type' => 'file',
            'content' => null,
            'file_path' => 'messages/files/' . fake()->uuid() . '.pdf',
            'file_name' => fake()->word() . '.pdf',
            'file_size' => fake()->numberBetween(100000, 10000000),
        ]);
    }

    /**
     * Sistem mesajı
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_type' => 'system',
            'content' => fake()->sentence(),
            'file_path' => null,
            'file_name' => null,
            'file_size' => null,
        ]);
    }

    /**
     * Düzenlenmiş mesaj
     */
    public function edited(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_edited' => true,
            'edited_at' => now(),
        ]);
    }

    /**
     * Yanıt mesajı
     */
    public function reply(): static
    {
        return $this->state(fn (array $attributes) => [
            'reply_to_message_id' => Message::factory(),
        ]);
    }
}
