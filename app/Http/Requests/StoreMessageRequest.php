<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    /**
     * Kullanıcının bu isteği yapma yetkisi var mı?
     */
    public function authorize(): bool
    {
        // Kullanıcı giriş yapmış mı kontrol et
        return auth()->check();
    }

    /**
     * Doğrulama kuralları
     */
    public function rules(): array
    {
        return [
            // Konuşma ID - Mesajın hangi konuşmaya gönderileceği
            'conversation_id' => [
                'required',                     // Zorunlu
                'exists:conversations,id',      // Conversations tablosunda mevcut olmalı
            ],
            
            // Mesaj türü
            'message_type' => [
                'required',                     // Zorunlu
                'in:text,image,video,audio,file,system',  // İzin verilen değerler
            ],
            
            // Mesaj içeriği (metin mesajlar için)
            'content' => [
                'required_if:message_type,text',  // message_type 'text' ise zorunlu
                'nullable',                        // Diğer durumlarda boş olabilir
                'string',                          // String olmalı
                'max:10000',                       // Maksimum 10000 karakter
            ],
            
            // Dosya (medya mesajları için)
            'file' => [
                'required_if:message_type,image,video,audio,file',  // Medya tiplerinde zorunlu
                'nullable',                        // Text mesajında boş olabilir
                'file',                            // Dosya olmalı
                'max:51200',                       // Maksimum 50MB (kilobyte cinsinden)
            ],
            
            // Resim için ek validasyonlar
            'file' => [
                'image' => [                      // message_type 'image' ise
                    'mimes:jpeg,png,jpg,gif,webp', // İzin verilen formatlar
                ],
            ],
            
            // Video için ek validasyonlar
            'file' => [
                'video' => [                      // message_type 'video' ise
                    'mimes:mp4,mov,avi,wmv',       // İzin verilen formatlar
                ],
            ],
            
            // Ses için ek validasyonlar
            'file' => [
                'audio' => [                      // message_type 'audio' ise
                    'mimes:mp3,wav,ogg,m4a',       // İzin verilen formatlar
                ],
            ],
            
            // Yanıtlanan mesaj ID (opsiyonel)
            'reply_to_message_id' => [
                'nullable',                       // Boş olabilir
                'exists:messages,id',             // Messages tablosunda mevcut olmalı
            ],
        ];
    }

    /**
     * Validasyondan önce veriyi hazırla
     * Dosya tipine göre otomatik validasyon kuralları ekle
     */
    protected function prepareForValidation(): void
    {
        // Eğer dosya yüklendiyse, message_type'ı otomatik belirle
        if ($this->hasFile('file') && !$this->message_type) {
            $mimeType = $this->file('file')->getMimeType();
            
            // MIME type'a göre message_type belirle
            $this->merge([
                'message_type' => $this->detectMessageType($mimeType),
            ]);
        }
    }

    /**
     * MIME type'a göre mesaj türünü belirle
     */
    private function detectMessageType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }
        
        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }
        
        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }
        
        return 'file';
    }

    /**
     * Özel hata mesajları
     */
    public function messages(): array
    {
        return [
            'conversation_id.required' => 'Konuşma seçimi zorunludur.',
            'conversation_id.exists' => 'Seçilen konuşma bulunamadı.',
            
            'message_type.required' => 'Mesaj türü belirtilmelidir.',
            'message_type.in' => 'Geçersiz mesaj türü.',
            
            'content.required_if' => 'Mesaj içeriği boş olamaz.',
            'content.max' => 'Mesaj en fazla :max karakter olabilir.',
            
            'file.required_if' => 'Dosya seçimi zorunludur.',
            'file.file' => 'Geçerli bir dosya seçiniz.',
            'file.max' => 'Dosya boyutu en fazla 50MB olabilir.',
            'file.image.mimes' => 'Resim formatı jpeg, png, jpg, gif veya webp olmalıdır.',
            'file.video.mimes' => 'Video formatı mp4, mov, avi veya wmv olmalıdır.',
            'file.audio.mimes' => 'Ses formatı mp3, wav, ogg veya m4a olmalıdır.',
            
            'reply_to_message_id.exists' => 'Yanıtlanacak mesaj bulunamadı.',
        ];
    }
}
