import React, { useState, useEffect, useRef } from 'react';
import { router } from '@inertiajs/react';
import axios from 'axios';
import echo from '../../Services/websocket';
import FileUpload from '../../Components/Chat/FileUpload';

/**
 * Chat Index Component - Ana sohbet sayfası
 * 
 * Kullanıcının tüm konuşmalarını ve aktif sohbet penceresini gösterir
 * Props:
 * - conversations: Kullanıcının tüm konuşmaları
 * - users: Yeni konuşma başlatmak için tüm kullanıcılar
 */
export default function ChatIndex({ conversations: initialConversations, users }) {
    // State yönetimi
    const [conversations, setConversations] = useState(initialConversations);  // Konuşma listesi
    const [selectedConversation, setSelectedConversation] = useState(null);     // Seçili konuşma
    const [messages, setMessages] = useState([]);                               // Mesajlar
    const [messageInput, setMessageInput] = useState('');                       // Mesaj input değeri
    const [isLoading, setIsLoading] = useState(false);                         // Yükleniyor mu?
    const [showNewChatModal, setShowNewChatModal] = useState(false);           // Yeni sohbet modalı
    const [searchQuery, setSearchQuery] = useState('');                        // Kullanıcı arama
    const [isTyping, setIsTyping] = useState(false);                           // Karşı taraf yazıyor mu?
    const [typingUser, setTypingUser] = useState(null);                        // Yazan kullanıcı adı
    const [showFileUpload, setShowFileUpload] = useState(false);               // Dosya yükleme modalı
    
    // Refs - DOM elementlerine erişim için
    const messagesEndRef = useRef(null);        // Mesajların sonuna scroll için
    const messageInputRef = useRef(null);       // Input elementine focus için

    /**
     * Konuşma seçildiğinde mesajları yükle ve WebSocket'e bağlan
     */
    useEffect(() => {
        if (selectedConversation) {
            loadMessages(selectedConversation.id);
            
            // WebSocket - Konuşma kanalına katıl (Presence Channel)
            const channel = echo.join(`conversation.${selectedConversation.id}`)
                // Şu anda konuşmada olan kullanıcılar
                .here((users) => {
                    console.log('Konuşmada şu an bulunan kullanıcılar:', users);
                })
                // Yeni kullanıcı konuşmaya katıldı
                .joining((user) => {
                    console.log('Konuşmaya katıldı:', user.name);
                })
                // Kullanıcı konuşmadan ayrıldı
                .leaving((user) => {
                    console.log('Konuşmadan ayrıldı:', user.name);
                })
                // Yeni mesaj geldi
                .listen('.message.sent', (data) => {
                    console.log('Yeni mesaj alındı:', data);
                    
                    // Mesajı listeye ekle (eğer kendi mesajımız değilse)
                    if (data.sender_id !== {{ auth()->id() }}) {
                        setMessages(prev => [...prev, data]);
                        scrollToBottom();
                        
                        // Bildirim sesi çal (opsiyonel)
                        playNotificationSound();
                    }
                })
                // Mesaj okundu bilgisi
                .listen('.message.read', (data) => {
                    console.log('Mesaj okundu:', data);
                    
                    // Mesaj listesindeki ilgili mesajı güncelle
                    setMessages(prev => prev.map(msg => {
                        if (msg.id === data.message_id) {
                            return {
                                ...msg,
                                read_by: [...(msg.read_by || []), data.read_by_user_id]
                            };
                        }
                        return msg;
                    }));
                })
                // Kullanıcı yazıyor göstergesi
                .listenForWhisper('typing', (data) => {
                    console.log(`${data.name} yazıyor...`);
                    
                    // Kendi mesajımız değilse yazıyor göstergesi göster
                    if (data.userId !== {{ auth()->id() }}) {
                        setIsTyping(true);
                        setTypingUser(data.name);
                        
                        // 3 saniye sonra yazıyor göstergesini kaldır
                        setTimeout(() => {
                            setIsTyping(false);
                            setTypingUser(null);
                        }, 3000);
                    }
                });

            // Component unmount olduğunda channel'dan ayrıl
            return () => {
                echo.leave(`conversation.${selectedConversation.id}`);
            };
        }
    }, [selectedConversation]);

    /**
     * Mesajları API'den yükle
     */
    const loadMessages = async (conversationId) => {
        setIsLoading(true);
        try {
            const response = await axios.get(`/messages/conversations/${conversationId}`);
            setMessages(response.data.data);  // Mesajları state'e kaydet
            scrollToBottom();  // En alta scroll
        } catch (error) {
            console.error('Mesajlar yüklenirken hata:', error);
            alert('Mesajlar yüklenirken bir hata oluştu');
        } finally {
            setIsLoading(false);
        }
    };

    /**
     * Yeni mesaj gönder
     */
    const sendMessage = async (e) => {
        e.preventDefault();
        
        // Boş mesaj gönderilmesin
        if (!messageInput.trim() || !selectedConversation) return;

        try {
            const response = await axios.post('/messages', {
                conversation_id: selectedConversation.id,
                message_type: 'text',
                content: messageInput.trim(),
            });

            // Yeni mesajı listeye ekle
            setMessages(prev => [...prev, response.data.data]);
            
            // Input'u temizle
            setMessageInput('');
            
            // Focus'u input'a getir
            messageInputRef.current?.focus();
            
            // En alta scroll
            scrollToBottom();
        } catch (error) {
            console.error('Mesaj gönderilirken hata:', error);
            alert('Mesaj gönderilemedi');
        }
    };

    /**
     * Dosya gönder
     */
    const sendFile = async (file) => {
        if (!selectedConversation) return;

        try {
            // FormData oluştur (dosya yükleme için)
            const formData = new FormData();
            formData.append('conversation_id', selectedConversation.id);
            formData.append('file', file);
            
            // Dosya türünü otomatik belirle (controller'da da yapılıyor)
            const fileType = file.type;
            if (fileType.startsWith('image/')) {
                formData.append('message_type', 'image');
            } else if (fileType.startsWith('video/')) {
                formData.append('message_type', 'video');
            } else if (fileType.startsWith('audio/')) {
                formData.append('message_type', 'audio');
            } else {
                formData.append('message_type', 'file');
            }

            // Yükleniyor göstergesi göster
            setIsLoading(true);

            const response = await axios.post('/messages', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
                // Yükleme progress göstergesi (opsiyonel)
                onUploadProgress: (progressEvent) => {
                    const percentCompleted = Math.round(
                        (progressEvent.loaded * 100) / progressEvent.total
                    );
                    console.log('Yükleme:', percentCompleted + '%');
                },
            });

            // Yeni mesajı listeye ekle
            setMessages(prev => [...prev, response.data.data]);
            
            // En alta scroll
            scrollToBottom();
            
            // Dosya yükleme modalını kapat
            setShowFileUpload(false);
        } catch (error) {
            console.error('Dosya gönderilirken hata:', error);
            alert('Dosya gönderilemedi');
        } finally {
            setIsLoading(false);
        }
    };

    /**
     * Mesajların sonuna scroll yap
     */
    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    /**
     * Bildirim sesi çal
     * Yeni mesaj geldiğinde ses efekti için
     */
    const playNotificationSound = () => {
        try {
            // HTML5 Audio API kullanarak ses çal
            const audio = new Audio('/sounds/notification.mp3');
            audio.volume = 0.5;  // Ses seviyesi (0.0 - 1.0)
            audio.play().catch(err => {
                console.log('Ses çalınamadı:', err);
            });
        } catch (error) {
            console.log('Ses dosyası bulunamadı:', error);
        }
    };

    /**
     * Yazıyor göstergesini gönder
     * Kullanıcı input'a yazarken çağrılır
     */
    const sendTypingIndicator = () => {
        if (selectedConversation) {
            // Whisper kullanarak typing event'ini gönder
            echo.join(`conversation.${selectedConversation.id}`)
                .whisper('typing', {
                    name: '{{ auth()->user()->name }}',
                    userId: {{ auth()->id() }}
                });
        }
    };

    /**
     * Input değiştiğinde yazıyor göstergesi gönder
     * Debounce ile gereksiz istekleri engelle
     */
    let typingTimer;
    const handleInputChange = (e) => {
        setMessageInput(e.target.value);
        
        // Yazıyor göstergesi gönder
        sendTypingIndicator();
        
        // 3 saniye sonra yazıyor durumunu temizle
        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => {
            setIsTyping(false);
        }, 3000);
    };

    /**
     * Yeni özel konuşma başlat
     */
    const startPrivateConversation = async (userId) => {
        try {
            const response = await axios.post('/chat/conversations', {
                user_id: userId,
            });

            // Konuşma listesini güncelle
            const newConversation = response.data.data;
            setConversations(prev => [newConversation, ...prev]);
            setSelectedConversation(newConversation);
            setShowNewChatModal(false);
        } catch (error) {
            console.error('Konuşma başlatılırken hata:', error);
            alert('Konuşma başlatılamadı');
        }
    };

    /**
     * Çıkış yap
     */
    const handleLogout = () => {
        router.post('/logout');
    };

    /**
     * Kullanıcı listesini filtrele (arama)
     */
    const filteredUsers = users.filter(user => 
        user.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        user.email.toLowerCase().includes(searchQuery.toLowerCase())
    );

    /**
     * Mesaj zamanını formatla
     */
    const formatMessageTime = (timestamp) => {
        const date = new Date(timestamp);
        return date.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' });
    };

    /**
     * Konuşma zamanını formatla
     */
    const formatConversationTime = (timestamp) => {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        
        // Bugün ise sadece saat göster
        if (diff < 86400000) {  // 24 saat
            return date.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' });
        }
        
        // Dün ise "Dün" yaz
        if (diff < 172800000) {  // 48 saat
            return 'Dün';
        }
        
        // Aksi halde tarih göster
        return date.toLocaleDateString('tr-TR', { day: 'numeric', month: 'short' });
    };

    return (
        <div className="chat-container">
            {/* SOL SİDEBAR - Konuşma Listesi */}
            <div className="chat-sidebar">
                {/* Sidebar Header */}
                <div className="sidebar-header">
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <h4 className="mb-0 fw-bold">Sohbetler</h4>
                        <div className="d-flex gap-2">
                            {/* Yeni sohbet butonu */}
                            <button 
                                className="btn btn-primary btn-sm rounded-circle"
                                onClick={() => setShowNewChatModal(true)}
                                title="Yeni Sohbet"
                            >
                                ✏️
                            </button>
                            {/* Çıkış butonu */}
                            <button 
                                className="btn btn-outline-danger btn-sm"
                                onClick={handleLogout}
                                title="Çıkış Yap"
                            >
                                🚪
                            </button>
                        </div>
                    </div>
                    
                    {/* Arama kutusu */}
                    <input
                        type="search"
                        className="form-control"
                        placeholder="Konuşmalarda ara..."
                        // Konuşma arama özelliği buraya eklenebilir
                    />
                </div>

                {/* Konuşma Listesi */}
                <div className="conversations-list">
                    {conversations.length === 0 ? (
                        // Konuşma yoksa
                        <div className="text-center py-5">
                            <p className="text-muted">Henüz konuşmanız yok</p>
                            <button 
                                className="btn btn-primary btn-sm"
                                onClick={() => setShowNewChatModal(true)}
                            >
                                Sohbet Başlat
                            </button>
                        </div>
                    ) : (
                        // Konuşmaları listele
                        conversations.map(conversation => (
                            <div
                                key={conversation.id}
                                className={`conversation-item ${selectedConversation?.id === conversation.id ? 'active' : ''}`}
                                onClick={() => setSelectedConversation(conversation)}
                            >
                                {/* Avatar ve online durumu */}
                                <div className="position-relative">
                                    <img
                                        src={conversation.avatar}
                                        alt={conversation.name}
                                        className="avatar"
                                    />
                                    {/* Online göstergesi */}
                                    {conversation.participants?.some(p => p.is_online) && (
                                        <span className="avatar-status online"></span>
                                    )}
                                </div>

                                {/* Konuşma bilgileri */}
                                <div className="flex-grow-1 overflow-hidden">
                                    <div className="d-flex justify-content-between align-items-start">
                                        <h6 className="mb-1 fw-semibold text-truncate">
                                            {conversation.name}
                                        </h6>
                                        <small className="text-muted">
                                            {conversation.updated_at && formatConversationTime(conversation.updated_at)}
                                        </small>
                                    </div>
                                    <p className="mb-0 text-muted small text-truncate">
                                        {conversation.last_message?.content || 'Henüz mesaj yok'}
                                    </p>
                                </div>

                                {/* Okunmamış mesaj sayısı */}
                                {conversation.unread_count > 0 && (
                                    <span className="badge bg-primary rounded-pill">
                                        {conversation.unread_count}
                                    </span>
                                )}
                            </div>
                        ))
                    )}
                </div>
            </div>

            {/* SAĞ TARAF - Chat Window */}
            <div className="chat-window">
                {selectedConversation ? (
                    <>
                        {/* Chat Header */}
                        <div className="chat-header">
                            <div className="d-flex align-items-center">
                                <img
                                    src={selectedConversation.avatar}
                                    alt={selectedConversation.name}
                                    className="avatar me-3"
                                />
                                <div>
                                    <h5 className="mb-0 fw-semibold">{selectedConversation.name}</h5>
                                    <small className="text-muted">
                                        {selectedConversation.participants?.some(p => p.is_online) ? 'Çevrimiçi' : 'Çevrimdışı'}
                                    </small>
                                </div>
                            </div>
                            
                            {/* Arama butonları (ileride eklenecek) */}
                            <div className="d-flex gap-2">
                                <button className="btn btn-outline-primary btn-sm" title="Sesli Arama">
                                    📞
                                </button>
                                <button className="btn btn-outline-primary btn-sm" title="Görüntülü Arama">
                                    📹
                                </button>
                            </div>
                        </div>

                        {/* Mesajlar Container */}
                        <div className="messages-container">
                            {isLoading ? (
                                // Yükleniyor göstergesi
                                <div className="text-center">
                                    <div className="spinner-border text-primary" role="status">
                                        <span className="visually-hidden">Yükleniyor...</span>
                                    </div>
                                </div>
                            ) : messages.length === 0 ? (
                                // Mesaj yoksa
                                <div className="text-center text-muted">
                                    <p>Henüz mesaj yok. İlk mesajı gönderin!</p>
                                </div>
                            ) : (
                                // Mesajları göster
                                messages.map((message, index) => {
                                    const isOwn = message.sender_id === {{ auth()->id() }};  // Kendi mesajı mı?
                                    
                                    return (
                                        <div key={message.id}>
                                            <div className={`message-bubble ${isOwn ? 'sent' : 'received'}`}>
                                                {/* Grup sohbetinde gönderen adı */}
                                                {!isOwn && selectedConversation.type === 'group' && (
                                                    <div className="fw-semibold small mb-1">
                                                        {message.sender?.name}
                                                    </div>
                                                )}
                                                
                                                {/* Mesaj içeriği - Metin */}
                                                {message.message_type === 'text' && (
                                                    <div>{message.content}</div>
                                                )}
                                                
                                                {/* Mesaj içeriği - Resim */}
                                                {message.message_type === 'image' && (
                                                    <div>
                                                        <img
                                                            src={message.file_path}
                                                            alt={message.file_name}
                                                            className="img-fluid rounded mb-2"
                                                            style={{ maxWidth: '300px', cursor: 'pointer' }}
                                                            onClick={() => window.open(message.file_path, '_blank')}
                                                        />
                                                        {message.content && <div className="mt-2">{message.content}</div>}
                                                    </div>
                                                )}
                                                
                                                {/* Mesaj içeriği - Video */}
                                                {message.message_type === 'video' && (
                                                    <div>
                                                        <video
                                                            src={message.file_path}
                                                            controls
                                                            className="rounded mb-2"
                                                            style={{ maxWidth: '300px' }}
                                                        />
                                                        {message.content && <div className="mt-2">{message.content}</div>}
                                                    </div>
                                                )}
                                                
                                                {/* Mesaj içeriği - Ses */}
                                                {message.message_type === 'audio' && (
                                                    <div>
                                                        <audio src={message.file_path} controls className="w-100 mb-2" />
                                                        {message.content && <div className="mt-2">{message.content}</div>}
                                                    </div>
                                                )}
                                                
                                                {/* Mesaj içeriği - Dosya */}
                                                {message.message_type === 'file' && (
                                                    <div>
                                                        <a
                                                            href={message.file_path}
                                                            download={message.file_name}
                                                            className="text-decoration-none d-flex align-items-center"
                                                        >
                                                            <span className="me-2" style={{ fontSize: '1.5rem' }}>📎</span>
                                                            <div>
                                                                <div className="fw-semibold">{message.file_name}</div>
                                                                <small className="text-muted">{message.formatted_file_size}</small>
                                                            </div>
                                                        </a>
                                                        {message.content && <div className="mt-2">{message.content}</div>}
                                                    </div>
                                                )}
                                                
                                                {/* Mesaj zamanı */}
                                                <div className="message-time">
                                                    {formatMessageTime(message.created_at)}
                                                    {/* Düzenlenmiş göstergesi */}
                                                    {message.is_edited && <span className="ms-1">(düzenlendi)</span>}
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })
                            )}
                            {/* Scroll referansı */}
                            <div ref={messagesEndRef} />
                        </div>

                        {/* Mesaj Input */}
                        <div className="message-input-container">
                            <form onSubmit={sendMessage} className="message-input-wrapper">
                                {/* Dosya ekleme butonu */}
                                <button
                                    type="button"
                                    className="btn btn-outline-primary"
                                    onClick={() => setShowFileUpload(true)}
                                    title="Dosya Ekle"
                                >
                                    📎
                                </button>
                                
                                <textarea
                                    ref={messageInputRef}
                                    className="message-input"
                                    placeholder="Mesajınızı yazın..."
                                    value={messageInput}
                                    onChange={handleInputChange}
                                    onKeyDown={(e) => {
                                        // Enter ile gönder (Shift+Enter ile satır atlama)
                                        if (e.key === 'Enter' && !e.shiftKey) {
                                            e.preventDefault();
                                            sendMessage(e);
                                        }
                                    }}
                                    rows={1}
                                />
                                <button type="submit" className="send-button" disabled={!messageInput.trim()}>
                                    📤
                                </button>
                            </form>
                            
                            {/* Yazıyor göstergesi */}
                            {isTyping && typingUser && (
                                <div className="typing-indicator-text mt-2 ms-3">
                                    <small className="text-muted">
                                        {typingUser} yazıyor
                                        <span className="typing-dots">
                                            <span className="typing-dot"></span>
                                            <span className="typing-dot"></span>
                                            <span className="typing-dot"></span>
                                        </span>
                                    </small>
                                </div>
                            )}
                        </div>
                    </>
                ) : (
                    // Konuşma seçilmemişse
                    <div className="d-flex align-items-center justify-content-center h-100">
                        <div className="text-center text-muted">
                            <div className="mb-3" style={{ fontSize: '4rem' }}>💬</div>
                            <h4>Bir konuşma seçin</h4>
                            <p>Mesajlaşmaya başlamak için soldaki listeden bir konuşma seçin</p>
                        </div>
                    </div>
                )}
            </div>

            {/* Dosya Yükleme Modalı */}
            {showFileUpload && (
                <div className="modal show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
                    <div className="modal-dialog modal-dialog-centered">
                        <div className="modal-content">
                            <div className="modal-header">
                                <h5 className="modal-title">Dosya Gönder</h5>
                                <button 
                                    type="button" 
                                    className="btn-close"
                                    onClick={() => setShowFileUpload(false)}
                                ></button>
                            </div>
                            <div className="modal-body">
                                <FileUpload
                                    onFileSelect={sendFile}
                                    onCancel={() => setShowFileUpload(false)}
                                />
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Yeni Sohbet Modalı */}
            {showNewChatModal && (
                <div className="modal show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
                    <div className="modal-dialog modal-dialog-centered">
                        <div className="modal-content">
                            <div className="modal-header">
                                <h5 className="modal-title">Yeni Sohbet Başlat</h5>
                                <button 
                                    type="button" 
                                    className="btn-close"
                                    onClick={() => setShowNewChatModal(false)}
                                ></button>
                            </div>
                            <div className="modal-body">
                                {/* Kullanıcı arama */}
                                <input
                                    type="search"
                                    className="form-control mb-3"
                                    placeholder="Kullanıcı ara..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                />
                                
                                {/* Kullanıcı listesi */}
                                <div style={{ maxHeight: '400px', overflowY: 'auto' }}>
                                    {filteredUsers.map(user => (
                                        <div
                                            key={user.id}
                                            className="d-flex align-items-center p-2 hover-bg-light cursor-pointer rounded"
                                            onClick={() => startPrivateConversation(user.id)}
                                            style={{ cursor: 'pointer' }}
                                        >
                                            <div className="position-relative">
                                                <img
                                                    src={user.avatar || `https://ui-avatars.com/api/?name=${user.name}`}
                                                    alt={user.name}
                                                    className="avatar me-3"
                                                />
                                                {user.is_online && (
                                                    <span className="avatar-status online"></span>
                                                )}
                                            </div>
                                            <div>
                                                <div className="fw-semibold">{user.name}</div>
                                                <small className="text-muted">{user.email}</small>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
