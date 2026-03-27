<?php

namespace Tests\Feature;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Kullanıcı kendi konuşmasındaki mesajları görüntüleyebilir
     */
    public function test_user_can_view_messages_in_their_conversation(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['type' => 'private']);
        $conversation->addParticipant($user);

        // Bazı mesajlar oluştur
        Message::factory()->count(3)->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('messages.index', $conversation));

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    /**
     * Kullanıcı katılmadığı konuşmanın mesajlarını görüntüleyemez
     */
    public function test_user_cannot_view_messages_in_conversation_they_are_not_part_of(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $conversation = Conversation::factory()->create(['type' => 'private']);
        $conversation->addParticipant($otherUser); // Sadece diğer kullanıcıyı ekle

        $response = $this->actingAs($user)
            ->getJson(route('messages.index', $conversation));

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Bu konuşmaya erişim yetkiniz yok.']);
    }

    /**
     * Kullanıcı kendi konuşmasına metin mesajı gönderebilir
     */
    public function test_user_can_send_text_message_to_their_conversation(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['type' => 'private']);
        $conversation->addParticipant($user);

        $response = $this->actingAs($user)
            ->postJson(route('messages.store'), [
                'conversation_id' => $conversation->id,
                'message_type' => 'text',
                'content' => 'Merhaba, nasılsın?',
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'message' => 'Mesaj başarıyla gönderildi.',
        ]);

        // Mesaj veritabanında mı?
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'message_type' => 'text',
            'content' => 'Merhaba, nasılsın?',
        ]);

        // MessageSent event'i broadcast edildi mi?
        Event::assertDispatched(MessageSent::class);
    }

    /**
     * Kullanıcı katılmadığı konuşmaya mesaj gönderemez
     */
    public function test_user_cannot_send_message_to_conversation_they_are_not_part_of(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $conversation = Conversation::factory()->create(['type' => 'private']);
        $conversation->addParticipant($otherUser);

        $response = $this->actingAs($user)
            ->postJson(route('messages.store'), [
                'conversation_id' => $conversation->id,
                'message_type' => 'text',
                'content' => 'Test mesajı',
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('messages', 0);
    }

    /**
     * Mesaj gönderirken dosya yüklenebilir
     */
    public function test_user_can_send_message_with_file_attachment(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['type' => 'private']);
        $conversation->addParticipant($user);

        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($user)
            ->postJson(route('messages.store'), [
                'conversation_id' => $conversation->id,
                'message_type' => 'image',
                'file' => $file,
            ]);

        $response->assertStatus(201);

        // Dosya storage'a kaydedildi mi?
        $message = Message::first();
        Storage::disk('public')->assertExists($message->file_path);

        // Veritabanında dosya bilgileri var mı?
        $this->assertNotNull($message->file_path);
        $this->assertNotNull($message->file_name);
        $this->assertNotNull($message->file_size);
    }

    /**
     * Resim mesajları doğru klasöre kaydedilir
     */
    public function test_image_messages_are_stored_in_correct_folder(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['type' => 'private']);
        $conversation->addParticipant($user);

        $file = UploadedFile::fake()->image('photo.jpg');

        $this->actingAs($user)
            ->postJson(route('messages.store'), [
                'conversation_id' => $conversation->id,
                'message_type' => 'image',
                'file' => $file,
            ]);

        $message = Message::first();
        $this->assertStringContainsString('messages/images', $message->file_path);
    }

    /**
     * Kullanıcı mesajı okundu olarak işaretleyebilir
     */
    public function test_user_can_mark_message_as_read(): void
    {
        Event::fake();

        $sender = User::factory()->create();
        $reader = User::factory()->create();
        $conversation = Conversation::factory()->create(['type' => 'private']);
        $conversation->addParticipant($sender);
        $conversation->addParticipant($reader);

        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
        ]);

        $response = $this->actingAs($reader)
            ->postJson(route('messages.read', $message));

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Mesaj okundu olarak işaretlendi.']);

        // Mesaj okundu olarak işaretlendi mi?
        $this->assertTrue($message->fresh()->isReadBy($reader));

        // MessageRead event'i broadcast edildi mi?
        Event::assertDispatched(MessageRead::class);
    }

    /**
     * Kullanıcı kendi mesajını okundu olarak işaretleyemez
     */
    public function test_user_cannot_mark_their_own_message_as_read(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['type' => 'private']);
        $conversation->addParticipant($user);

        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('messages.read', $message));

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Kendi mesajınızı okuyamazsınız.']);
    }

    /**
     * Kullanıcı katılmadığı konuşmanın mesajını okuyamaz
     */
    public function test_user_cannot_mark_message_as_read_in_conversation_they_are_not_part_of(): void
    {
        $sender = User::factory()->create();
        $otherUser = User::factory()->create();
        $conversation = Conversation::factory()->create(['type' => 'private']);
        $conversation->addParticipant($sender);

        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->postJson(route('messages.read', $message));

        $response->assertStatus(403);
    }

    /**
     * Kullanıcı kendi mesajını düzenleyebilir
     */
    public function test_user_can_edit_their_own_message(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['type' => 'private']);
        $conversation->addParticipant($user);

        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'message_type' => 'text',
            'content' => 'Orijinal mesaj',
        ]);

        $response = $this->actingAs($user)
            ->putJson(route('messages.update', $message), [
                'content' => 'Düzenlenmiş mesaj',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Mesaj başarıyla düzenlendi.']);

        // Mesaj güncellendi mi?
        $message->refresh();
        $this->assertEquals('Düzenlenmiş mesaj', $message->content);
        $this->assertTrue($message->is_edited);
        $this->assertNotNull($message->edited_at);
    }

    /**
     * Kullanıcı başkasının mesajını düzenleyemez
     */
    public function test_user_cannot_edit_others_message(): void
    {
        $sender = User::factory()->create();
        $otherUser = User::factory()->create();
        $conversation = Conversation::factory()->create(['type' => 'private']);
        $conversation->addParticipant($sender);
        $conversation->addParticipant($otherUser);

        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'message_type' => 'text',
            'content' => 'Orijinal mesaj',
        ]);

        $response = $this->actingAs($otherUser)
            ->putJson(route('messages.update', $message), [
                'content' => 'Düzenlenmiş mesaj',
            ]);

        $response->assertStatus(403);

        // Mesaj değişmemiş olmalı
        $this->assertEquals('Orijinal mesaj', $message->fresh()->content);
    }

    /**
     * Sadece metin mesajları düzenlenebilir
     */
    public function test_only_text_messages_can_be_edited(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['type' => 'private']);
        $conversation->addParticipant($user);

        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'message_type' => 'image',
        ]);

        $response = $this->actingAs($user)
            ->putJson(route('messages.update', $message), [
                'content' => 'Düzenlenmiş mesaj',
            ]);

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Sadece metin mesajları düzenlenebilir.']);
    }

    /**
     * Kullanıcı kendi mesajını silebilir
     */
    public function test_user_can_delete_their_own_message(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['type' => 'private']);
        $conversation->addParticipant($user);

        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'message_type' => 'text',
            'content' => 'Silinecek mesaj',
        ]);

        $response = $this->actingAs($user)
            ->deleteJson(route('messages.destroy', $message));

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Mesaj başarıyla silindi.']);

        // Mesaj soft delete edildi mi?
        $this->assertSoftDeleted('messages', ['id' => $message->id]);
    }

    /**
     * Kullanıcı başkasının mesajını silemez
     */
    public function test_user_cannot_delete_others_message(): void
    {
        $sender = User::factory()->create();
        $otherUser = User::factory()->create();
        $conversation = Conversation::factory()->create(['type' => 'private']);
        $conversation->addParticipant($sender);
        $conversation->addParticipant($otherUser);

        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->deleteJson(route('messages.destroy', $message));

        $response->assertStatus(403);

        // Mesaj hala var olmalı
        $this->assertDatabaseHas('messages', ['id' => $message->id]);
        $this->assertNull($message->fresh()->deleted_at);
    }

    /**
     * Mesaj silindiğinde dosya da silinir
     */
    public function test_file_is_deleted_when_message_is_deleted(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['type' => 'private']);
        $conversation->addParticipant($user);

        $file = UploadedFile::fake()->image('photo.jpg');

        // Dosyalı mesaj gönder
        $this->actingAs($user)
            ->postJson(route('messages.store'), [
                'conversation_id' => $conversation->id,
                'message_type' => 'image',
                'file' => $file,
            ]);

        $message = Message::first();
        $filePath = $message->file_path;

        // Dosya var mı kontrol et
        Storage::disk('public')->assertExists($filePath);

        // Mesajı sil
        $this->actingAs($user)
            ->deleteJson(route('messages.destroy', $message));

        // Dosya silindi mi?
        Storage::disk('public')->assertMissing($filePath);
    }

    /**
     * Yanıt mesajı gönderilebilir
     */
    public function test_user_can_send_reply_message(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['type' => 'private']);
        $conversation->addParticipant($user);

        $originalMessage = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => 'Orijinal mesaj',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('messages.store'), [
                'conversation_id' => $conversation->id,
                'message_type' => 'text',
                'content' => 'Bu bir yanıt',
                'reply_to_message_id' => $originalMessage->id,
            ]);

        $response->assertStatus(201);

        // Yanıt mesajı doğru kaydedildi mi?
        $replyMessage = Message::latest()->first();
        $this->assertEquals($originalMessage->id, $replyMessage->reply_to_message_id);
    }
}
