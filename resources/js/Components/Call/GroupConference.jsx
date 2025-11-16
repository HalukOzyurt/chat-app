import React, { useState, useEffect, useRef } from 'react';
import webRTCService from '../../Services/webrtc';
import echo from '../../Services/websocket';

/**
 * GroupConference Component - Grup Görüntülü/Sesli Konferans
 * 
 * Mesh topology kullanarak grup aramaları yönetir
 * 3+ kişiyle görüntülü/sesli konferans
 */
export default function GroupConference({
    conversationId,          // Konuşma ID
    participants,            // Katılımcılar listesi
    onEnd,                   // Konferans bittiğinde callback
}) {
    // State yönetimi
    const [activeParticipants, setActiveParticipants] = useState([]);  // Aktif katılımcılar
    const [isMuted, setIsMuted] = useState(false);                     // Mikrofon durumu
    const [isVideoOff, setIsVideoOff] = useState(false);               // Kamera durumu
    const [isScreenSharing, setIsScreenSharing] = useState(false);     // Ekran paylaşımı
    const [layout, setLayout] = useState('grid');                      // Düzen: grid, speaker, sidebar

    // Local video ref
    const localVideoRef = useRef(null);
    
    // Remote video refs (userId: ref)
    const remoteVideoRefs = useRef(new Map());

    /**
     * Component mount - Konferansı başlat
     */
    useEffect(() => {
        initializeConference();

        // WebRTC event listener'ları
        webRTCService.on('signal', handleSignal);
        webRTCService.on('stream', handleRemoteStream);

        // WebSocket - Yeni katılımcı eklendi
        echo.join(`conversation.${conversationId}`)
            .joining((user) => {
                console.log('Konferansa katıldı:', user.name);
                addParticipant(user);
            })
            .leaving((user) => {
                console.log('Konferanstan ayrıldı:', user.name);
                removeParticipant(user.id);
            });

        return () => {
            endConference();
        };
    }, []);

    /**
     * Konferansı başlat
     */
    const initializeConference = async () => {
        try {
            // Kullanıcının kamera ve mikrofonunu aç
            const stream = await webRTCService.getUserMedia(true, true);
            
            // Local video'ya bağla
            if (localVideoRef.current) {
                localVideoRef.current.srcObject = stream;
            }

            // Mevcut katılımcılarla peer bağlantısı kur
            participants.forEach(participant => {
                if (participant.id !== {{ auth()->id() }}) {
                    webRTCService.createPeer(participant.id, stream);
                }
            });

            setActiveParticipants(participants);
        } catch (error) {
            console.error('Konferans başlatma hatası:', error);
            alert(error.message);
            onEnd && onEnd();
        }
    };

    /**
     * Yeni katılımcı ekle
     */
    const addParticipant = (user) => {
        // Zaten eklenmişse ekleme
        if (activeParticipants.find(p => p.id === user.id)) {
            return;
        }

        // Peer bağlantısı kur
        const stream = webRTCService.localStream;
        if (stream) {
            webRTCService.createPeer(user.id, stream);
        }

        // Katılımcı listesine ekle
        setActiveParticipants(prev => [...prev, user]);
    };

    /**
     * Katılımcıyı kaldır
     */
    const removeParticipant = (userId) => {
        // Peer bağlantısını kapat
        webRTCService.removePeer(userId);

        // Listeden çıkar
        setActiveParticipants(prev => prev.filter(p => p.id !== userId));
    };

    /**
     * WebRTC signal handler
     */
    const handleSignal = (userId, signal) => {
        // Signal'i ilgili kullanıcıya gönder
        echo.private(`user.${userId}`)
            .whisper('webrtc-signal', {
                from: {{ auth()->id() }},
                signal: signal,
            });
    };

    /**
     * Remote stream handler
     */
    const handleRemoteStream = (userId, stream) => {
        // Video ref'i oluştur
        const videoElement = remoteVideoRefs.current.get(userId);
        if (videoElement) {
            videoElement.srcObject = stream;
        }
    };

    /**
     * Mikrofonu aç/kapa
     */
    const toggleMute = () => {
        webRTCService.toggleAudio(!isMuted);
        setIsMuted(!isMuted);
    };

    /**
     * Kamerayı aç/kapa
     */
    const toggleVideo = () => {
        webRTCService.toggleVideo(!isVideoOff);
        setIsVideoOff(!isVideoOff);
    };

    /**
     * Ekran paylaşımı
     */
    const toggleScreenShare = async () => {
        const success = await webRTCService.toggleScreenShare(!isScreenSharing);
        if (success !== undefined) {
            setIsScreenSharing(!isScreenSharing);
        }
    };

    /**
     * Konferansı sonlandır
     */
    const endConference = () => {
        webRTCService.endCall();
        onEnd && onEnd();
    };

    /**
     * Video düzenini değiştir
     */
    const changeLayout = (newLayout) => {
        setLayout(newLayout);
    };

    /**
     * Grid layout CSS sınıfı
     */
    const getGridClass = () => {
        const count = activeParticipants.length;
        if (count <= 2) return 'conference-grid-2';
        if (count <= 4) return 'conference-grid-4';
        if (count <= 6) return 'conference-grid-6';
        return 'conference-grid-9';
    };

    return (
        <div className="group-conference position-fixed top-0 start-0 w-100 h-100 bg-dark">
            {/* Header */}
            <div className="conference-header p-3 text-white d-flex justify-content-between align-items-center">
                <div>
                    <h5 className="mb-0">Grup Konferans</h5>
                    <small className="text-muted">
                        {activeParticipants.length} katılımcı
                    </small>
                </div>
                
                {/* Düzen seçimi */}
                <div className="btn-group">
                    <button
                        className={`btn btn-sm ${layout === 'grid' ? 'btn-primary' : 'btn-outline-light'}`}
                        onClick={() => changeLayout('grid')}
                        title="Grid Düzeni"
                    >
                        ▦
                    </button>
                    <button
                        className={`btn btn-sm ${layout === 'speaker' ? 'btn-primary' : 'btn-outline-light'}`}
                        onClick={() => changeLayout('speaker')}
                        title="Konuşmacı Düzeni"
                    >
                        ▨
                    </button>
                </div>
            </div>

            {/* Video Grid */}
            <div className={`conference-videos ${getGridClass()} p-3`}>
                {/* Local Video */}
                <div className="conference-video-item position-relative">
                    <video
                        ref={localVideoRef}
                        autoPlay
                        playsInline
                        muted
                        className="w-100 h-100 object-fit-cover rounded"
                        style={{ transform: 'scaleX(-1)' }}
                    />
                    <div className="participant-name position-absolute bottom-0 start-0 m-2 px-2 py-1 bg-dark bg-opacity-75 rounded text-white small">
                        Sen {isMuted && '🔇'} {isVideoOff && '📷'}
                    </div>
                    {isVideoOff && (
                        <div className="position-absolute top-0 start-0 w-100 h-100 bg-dark d-flex align-items-center justify-content-center">
                            <div className="text-white text-center">
                                <div className="mb-2" style={{ fontSize: '3rem' }}>👤</div>
                                <div>Kamera Kapalı</div>
                            </div>
                        </div>
                    )}
                </div>

                {/* Remote Videos */}
                {activeParticipants.map(participant => {
                    if (participant.id === {{ auth()->id() }}) return null;
                    
                    return (
                        <div key={participant.id} className="conference-video-item position-relative">
                            <video
                                ref={el => {
                                    if (el) {
                                        remoteVideoRefs.current.set(participant.id, el);
                                    }
                                }}
                                autoPlay
                                playsInline
                                className="w-100 h-100 object-fit-cover rounded"
                                style={{ transform: 'scaleX(-1)' }}
                            />
                            <div className="participant-name position-absolute bottom-0 start-0 m-2 px-2 py-1 bg-dark bg-opacity-75 rounded text-white small">
                                {participant.name}
                            </div>
                        </div>
                    );
                })}
            </div>

            {/* Kontrol Butonları */}
            <div className="conference-controls position-absolute bottom-0 start-0 w-100 p-4 d-flex justify-content-center gap-3">
                {/* Mikrofon */}
                <button
                    className={`btn btn-lg rounded-circle ${isMuted ? 'btn-danger' : 'btn-secondary'}`}
                    onClick={toggleMute}
                    style={{ width: '60px', height: '60px' }}
                    title={isMuted ? 'Mikrofonu Aç' : 'Mikrofonu Kapat'}
                >
                    {isMuted ? '🔇' : '🎤'}
                </button>

                {/* Kamera */}
                <button
                    className={`btn btn-lg rounded-circle ${isVideoOff ? 'btn-danger' : 'btn-secondary'}`}
                    onClick={toggleVideo}
                    style={{ width: '60px', height: '60px' }}
                    title={isVideoOff ? 'Kamerayı Aç' : 'Kamerayı Kapat'}
                >
                    {isVideoOff ? '📷' : '📹'}
                </button>

                {/* Ekran Paylaşımı */}
                <button
                    className={`btn btn-lg rounded-circle ${isScreenSharing ? 'btn-primary' : 'btn-secondary'}`}
                    onClick={toggleScreenShare}
                    style={{ width: '60px', height: '60px' }}
                    title="Ekran Paylaş"
                >
                    🖥️
                </button>

                {/* Konferansı Bitir */}
                <button
                    className="btn btn-danger btn-lg rounded-circle"
                    onClick={endConference}
                    style={{ width: '60px', height: '60px' }}
                    title="Konferansı Bitir"
                >
                    📵
                </button>
            </div>
        </div>
    );
}
