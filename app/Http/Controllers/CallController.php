<?php

namespace App\Http\Controllers;

use App\Events\CallInitiated;
use App\Events\CallEnded;
use App\Models\Call;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallController extends Controller
{
    /**
     * Yeni arama başlat
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function initiate(Request $request): JsonResponse
    {
        // Validasyon
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'receiver_id' => 'required|exists:users,id',
            'call_type' => 'required|in:audio,video',
        ]);

        // Konuşmayı kontrol et
        $conversation = Conversation::findOrFail($request->conversation_id);
        
        // Kullanıcının konuşmada olup olmadığını kontrol et
        if (!$conversation->hasParticipant(auth()->id())) {
            return response()->json([
                'message' => 'Bu konuşmada değilsiniz.'
            ], 403);
        }

        // Arama kaydı oluştur
        $call = Call::create([
            'conversation_id' => $request->conversation_id,
            'caller_id' => auth()->id(),
            'receiver_id' => $request->receiver_id,
            'call_type' => $request->call_type,
            'status' => 'ringing',
        ]);

        // İlişkileri yükle
        $call->load(['caller:id,name,avatar', 'receiver:id,name,avatar']);

        // WebSocket ile karşı tarafa bildir
        broadcast(new CallInitiated($call))->toOthers();

        return response()->json([
            'message' => 'Arama başlatıldı.',
            'data' => $call
        ], 201);
    }

    /**
     * Aramayı kabul et
     * 
     * @param Call $call
     * @return JsonResponse
     */
    public function accept(Call $call): JsonResponse
    {
        // Sadece aranan kişi kabul edebilir
        if ($call->receiver_id !== auth()->id()) {
            return response()->json([
                'message' => 'Bu aramayı kabul etme yetkiniz yok.'
            ], 403);
        }

        // Arama durumunu güncelle
        $call->start();

        return response()->json([
            'message' => 'Arama kabul edildi.',
            'data' => $call
        ]);
    }

    /**
     * Aramayı reddet
     * 
     * @param Call $call
     * @return JsonResponse
     */
    public function reject(Call $call): JsonResponse
    {
        // Sadece aranan kişi reddedebilir
        if ($call->receiver_id !== auth()->id()) {
            return response()->json([
                'message' => 'Bu aramayı reddetme yetkiniz yok.'
            ], 403);
        }

        // Arama durumunu güncelle
        $call->reject();

        // WebSocket ile arayan kişiye bildir
        broadcast(new CallEnded($call))->toOthers();

        return response()->json([
            'message' => 'Arama reddedildi.'
        ]);
    }

    /**
     * Aramayı sonlandır
     * 
     * @param Call $call
     * @return JsonResponse
     */
    public function end(Call $call): JsonResponse
    {
        // Sadece aramanın tarafları sonlandırabilir
        if ($call->caller_id !== auth()->id() && $call->receiver_id !== auth()->id()) {
            return response()->json([
                'message' => 'Bu aramayı sonlandırma yetkiniz yok.'
            ], 403);
        }

        // Arama durumunu güncelle
        if ($call->status === 'ongoing') {
            $call->end();
        } else if ($call->status === 'ringing') {
            $call->markAsMissed();
        }

        // WebSocket ile karşı tarafa bildir
        broadcast(new CallEnded($call))->toOthers();

        return response()->json([
            'message' => 'Arama sonlandırıldı.',
            'data' => $call
        ]);
    }

    /**
     * Kullanıcının arama geçmişini getir
     * 
     * @return JsonResponse
     */
    public function history(): JsonResponse
    {
        // Kullanıcının yaptığı ve aldığı aramalar
        $calls = Call::where('caller_id', auth()->id())
            ->orWhere('receiver_id', auth()->id())
            ->with(['caller:id,name,avatar', 'receiver:id,name,avatar'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($calls);
    }
}
