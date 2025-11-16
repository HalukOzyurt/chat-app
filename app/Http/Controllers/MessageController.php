<?php

namespace App\Http\Controllers;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Http\Requests\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    /**
     * Belirli bir konuşmanın mesajlarını listele
     * N+1 problemini önlemek için eager loading kullanıyoruz
     * 
     * @param Conversation $conversation
     * @return JsonResponse
     */
    public function index(Conversation $conversation): JsonResponse
    {
        // Kullanıcı bu konuşmada mı kontrol et
        if (!$conversation->hasParticipant(auth()->id())) {
            return response()->json([
                'message' => 'Bu konuşmaya erişim yetkiniz yok.'
            ], 403);
        }

        // Mesajları getir - N+1 problemini önle
        $messages = $conversation->messages()
            ->with([
                'sender:id,name,avatar',  // Sadece gerekli sütunları getir
                'readBy:id,name',         // Mesajı okuyanları getir
                'replyTo'                 // Yanıtlanan mesajı getir
            ])
            ->orderBy('created_at', 'asc')  // Eskiden yeniye sırala
            ->paginate(50);  // Sayfalama - 50 mesaj

        return response()->json($messages);
    }

    /**
     * Yeni mesaj gönder
     * 
     * @param StoreMessageRequest $request
     * @return JsonResponse
     */
    public function store(StoreMessageRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Konuşmayı bul
        $conversation = Conversation::findOrFail($validated['conversation_id']);

        // Kullanıcının bu konuşmada olup olmadığını kontrol et
        if (!$conversation->hasParticipant(auth()->id())) {
            return response()->json([
                'message' => 'Bu konuşmaya mesaj gönderme yetkiniz yok.'
            ], 403);
        }

        // Mesaj verisini hazırla
        $messageData = [
            'conversation_id' => $validated['conversation_id'],
            'sender_id' => auth()->id(),
            'message_type' => $validated['message_type'],
            'content' => $validated['content'] ?? null,
            'reply_to_message_id' => $validated['reply_to_message_id'] ?? null,
        ];

        // Eğer dosya varsa, yükle ve kaydet
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            
            // Dosya adını güvenli hale getir
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $fileName = time() . '_' . uniqid() . '.' . $extension;

            // Mesaj türüne göre klasöre kaydet
            $folder = match($validated['message_type']) {
                'image' => 'messages/images',
                'video' => 'messages/videos',
                'audio' => 'messages/audio',
                default => 'messages/files',
            };

            // Dosyayı storage'a kaydet
            $filePath = $file->storeAs($folder, $fileName, 'public');

            // Mesaj verisine dosya bilgilerini ekle
            $messageData['file_path'] = $filePath;
            $messageData['file_name'] = $originalName;
            $messageData['file_size'] = $file->getSize();
        }

        // Mesajı veritabanına kaydet
        $message = Message::create($messageData);

        // Mesajı ilişkileriyle birlikte getir (API response için)
        $message->load(['sender:id,name,avatar', 'replyTo']);

        // WebSocket ile mesajı broadcast et (gerçek zamanlı gönderim)
        broadcast(new MessageSent($message))->toOthers();

        // Konuşmadaki diğer kullanıcılara bildirim gönder
        $this->sendNotificationToParticipants($conversation, $message);

        return response()->json([
            'message' => 'Mesaj başarıyla gönderildi.',
            'data' => $message
        ], 201);
    }

    /**
     * Mesajı okundu olarak işaretle
     * 
     * @param Message $message
     * @return JsonResponse
     */
    public function markAsRead(Message $message): JsonResponse
    {
        // Kendi mesajını okuma
        if ($message->sender_id === auth()->id()) {
            return response()->json([
                'message' => 'Kendi mesajınızı okuyamazsınız.'
            ], 400);
        }

        // Kullanıcı bu konuşmada mı kontrol et
        if (!$message->conversation->hasParticipant(auth()->id())) {
            return response()->json([
                'message' => 'Bu mesaja erişim yetkiniz yok.'
            ], 403);
        }

        // Mesajı okundu olarak işaretle
        $message->markAsReadBy(auth()->user());

        // WebSocket ile okundu bilgisini broadcast et
        broadcast(new MessageRead($message, auth()->user()))->toOthers();

        return response()->json([
            'message' => 'Mesaj okundu olarak işaretlendi.'
        ]);
    }

    /**
     * Mesajı düzenle
     * 
     * @param Request $request
     * @param Message $message
     * @return JsonResponse
     */
    public function update(Request $request, Message $message): JsonResponse
    {
        // Sadece gönderen kişi düzenleyebilir
        if ($message->sender_id !== auth()->id()) {
            return response()->json([
                'message' => 'Bu mesajı düzenleme yetkiniz yok.'
            ], 403);
        }

        // Sadece metin mesajları düzenlenebilir
        if (!$message->isText()) {
            return response()->json([
                'message' => 'Sadece metin mesajları düzenlenebilir.'
            ], 400);
        }

        // İçerik doğrulaması
        $request->validate([
            'content' => 'required|string|max:10000'
        ]);

        // Mesajı düzenle
        $message->edit($request->content);

        // WebSocket ile güncellenmiş mesajı broadcast et
        // broadcast(new MessageEdited($message))->toOthers();

        return response()->json([
            'message' => 'Mesaj başarıyla düzenlendi.',
            'data' => $message
        ]);
    }

    /**
     * Mesajı sil (soft delete)
     * 
     * @param Message $message
     * @return JsonResponse
     */
    public function destroy(Message $message): JsonResponse
    {
        // Sadece gönderen kişi silebilir
        if ($message->sender_id !== auth()->id()) {
            return response()->json([
                'message' => 'Bu mesajı silme yetkiniz yok.'
            ], 403);
        }

        // Soft delete - Mesaj veritabanından silinmez, sadece gizlenir
        $message->delete();

        // Eğer dosya varsa, storage'dan sil
        if ($message->file_path) {
            Storage::disk('public')->delete($message->file_path);
        }

        // WebSocket ile silme işlemini broadcast et
        // broadcast(new MessageDeleted($message))->toOthers();

        return response()->json([
            'message' => 'Mesaj başarıyla silindi.'
        ]);
    }

    /**
     * Konuşmadaki diğer katılımcılara bildirim gönder
     * 
     * @param Conversation $conversation
     * @param Message $message
     */
    private function sendNotificationToParticipants(Conversation $conversation, Message $message): void
    {
        // Konuşmadaki tüm katılımcıları getir (mesaj göndereni hariç)
        $participants = $conversation->participants()
            ->where('user_id', '!=', auth()->id())
            ->get();

        // Her katılımcıya bildirim oluştur
        foreach ($participants as $participant) {
            $participant->notifications()->create([
                'type' => 'new_message',
                'title' => 'Yeni Mesaj',
                'message' => auth()->user()->name . ' size mesaj gönderdi',
                'data' => [
                    'conversation_id' => $conversation->id,
                    'message_id' => $message->id,
                    'sender_name' => auth()->user()->name,
                    'message_preview' => $message->preview,
                ],
            ]);
        }
    }
}
