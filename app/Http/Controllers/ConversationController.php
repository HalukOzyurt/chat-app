<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConversationController extends Controller
{
    /**
     * Ana chat sayfasını göster
     * Kullanıcının tüm konuşmalarını listele
     */
    public function index(): Response
    {
        // Kullanıcının konuşmalarını getir - N+1 problemini önle
        $conversations = auth()->user()
            ->conversations()
            ->with([
                'lastMessage.sender:id,name',  // Son mesaj ve göndereni
                'participants:id,name,avatar,is_online',  // Katılımcılar
            ])
            ->withCount('messages')  // Mesaj sayısı
            ->latest('updated_at')   // En son güncellenenden eskiye
            ->get()
            ->map(function ($conversation) {
                return [
                    'id' => $conversation->id,
                    'type' => $conversation->type,
                    'name' => $conversation->getTitle(auth()->user()),
                    'avatar' => $conversation->getAvatarUrl(auth()->user()),
                    'last_message' => $conversation->lastMessage,
                    'unread_count' => $conversation->unreadMessagesCount(auth()->user()),
                    'participants' => $conversation->participants,
                    'updated_at' => $conversation->updated_at,
                ];
            });

        // Tüm kullanıcıları getir (yeni konuşma başlatmak için)
        $users = User::where('id', '!=', auth()->id())
            ->select('id', 'name', 'avatar', 'is_online')
            ->orderBy('is_online', 'desc')  // Çevrimiçi olanlar üstte
            ->orderBy('name')
            ->get();

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations,
            'users' => $users,
        ]);
    }

    /**
     * Yeni özel konuşma başlat
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',  // Mesajlaşılacak kullanıcı
        ]);

        $otherUserId = $request->user_id;

        // Zaten bu kullanıcı ile konuşma var mı kontrol et
        $existingConversation = Conversation::where('type', 'private')
            ->whereHas('participants', function ($query) use ($otherUserId) {
                $query->where('user_id', $otherUserId);
            })
            ->whereHas('participants', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->first();

        // Varsa mevcut konuşmayı döndür
        if ($existingConversation) {
            return response()->json([
                'message' => 'Konuşma zaten mevcut.',
                'data' => $existingConversation
            ]);
        }

        // Yoksa yeni konuşma oluştur
        $conversation = Conversation::create([
            'type' => 'private',
            'created_by' => auth()->id(),
        ]);

        // Her iki kullanıcıyı da konuşmaya ekle
        $conversation->addParticipant(auth()->id());
        $conversation->addParticipant($otherUserId);

        // İlişkileri yükle
        $conversation->load('participants:id,name,avatar,is_online');

        return response()->json([
            'message' => 'Konuşma başarıyla oluşturuldu.',
            'data' => $conversation
        ], 201);
    }

    /**
     * Grup konuşması oluştur
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function createGroup(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',  // Grup adı
            'participant_ids' => 'required|array|min:2',  // En az 2 katılımcı
            'participant_ids.*' => 'exists:users,id',  // Her ID geçerli olmalı
        ]);

        // Grup konuşması oluştur
        $conversation = Conversation::create([
            'type' => 'group',
            'name' => $request->name,
            'created_by' => auth()->id(),
        ]);

        // Oluşturanı admin olarak ekle
        $conversation->addParticipant(auth()->id(), 'admin');

        // Diğer katılımcıları ekle
        foreach ($request->participant_ids as $participantId) {
            if ($participantId != auth()->id()) {
                $conversation->addParticipant($participantId, 'member');
            }
        }

        // İlişkileri yükle
        $conversation->load('participants:id,name,avatar,is_online');

        return response()->json([
            'message' => 'Grup başarıyla oluşturuldu.',
            'data' => $conversation
        ], 201);
    }

    /**
     * Belirli bir konuşmanın detaylarını getir
     * 
     * @param Conversation $conversation
     * @return JsonResponse
     */
    public function show(Conversation $conversation): JsonResponse
    {
        // Kullanıcı bu konuşmada mı kontrol et
        if (!$conversation->hasParticipant(auth()->id())) {
            return response()->json([
                'message' => 'Bu konuşmaya erişim yetkiniz yok.'
            ], 403);
        }

        // Konuşma detaylarını yükle
        $conversation->load([
            'participants:id,name,avatar,is_online,email',
            'lastMessage'
        ]);

        return response()->json($conversation);
    }

    /**
     * Kullanıcıyı konuşmadan çıkar
     * 
     * @param Conversation $conversation
     * @return JsonResponse
     */
    public function leave(Conversation $conversation): JsonResponse
    {
        // Kullanıcı bu konuşmada mı kontrol et
        if (!$conversation->hasParticipant(auth()->id())) {
            return response()->json([
                'message' => 'Bu konuşmada değilsiniz.'
            ], 400);
        }

        // Özel konuşmalardan çıkılamaz
        if ($conversation->isPrivate()) {
            return response()->json([
                'message' => 'Özel konuşmalardan çıkılamaz.'
            ], 400);
        }

        // Konuşmadan çıkar
        $conversation->removeParticipant(auth()->id());

        return response()->json([
            'message' => 'Konuşmadan başarıyla ayrıldınız.'
        ]);
    }
}
