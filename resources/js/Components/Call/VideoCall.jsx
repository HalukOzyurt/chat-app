import React, { useState, useEffect, useRef } from 'react';
import webRTCService from '../../Services/webrtc';
import echo from '../../Services/websocket';

/**
 * VideoCall Component - Görüntülü/Sesli Arama UI
 * 
 * 1-1 video/ses aramaları için kullanılır
 */
export default function VideoCall({ 
    conversationId,      // Konuşma ID
    recipientUser,       // Aranan kullanıcı bilgisi
    isIncoming = false,  // Gelen arama mı?
    onEnd,               // Arama bittiğinde callback
}) {
    // State yönetimi
    const [callStatus, setCallStatus] = useState(isIncoming ? 'ringing' : 'connecting');
    const [isMuted, setIsMuted] = useState(false);           // Mikrofon kapalı mı?
    const [isVideoOff, setIsVideoOff] = useState(false);     // Kamera kapalı mı?
    const [isScreenSharing, setIsScreenSharing] = useState(false);  // Ekran paylaşımı aktif mi?
    const [callDuration, setCallDuration] = useState(0);     // Arama süresi (saniye)
    const [connectionQuality, setConnectionQuality] = useState('good');  // Bağlantı kalitesi

    // Video element refs
    const localVideoRef = useRef(null);   // Local video (kendi kameramız)
    const remoteVideoRef = useRef(null);  // Remote video (karşı tarafın kamerası)

    // Timer ref
    const durationTimerRef = useRef(null);

    /**
     * Component mount - Aramayı başlat
     */
    useEffect(() => {
        initializeCall();

        // WebRTC event listener'ları
        webRTCService.on('signal', handleSignal);
        webRTCService.on('stream', handleRemoteStream);
        webRTCService.on('error', handleError);
        webRTCService.on('close', handleClose);

        // Component unmount - Aramayı bitir
        return () => {
            endCall();
        };
    }, []);

    /**
     * Arama süresini güncelle
     */
    useEffect(() => {
        if (callStatus === 'connected') {
            // Her saniye süreyi artır
            durationTimerRef.current = setInterval(() => {
                setCallDuration(prev => prev + 1);
            }, 1000);

            // Bağlantı kalitesini her 5 saniyede kontrol et
            const qualityInterval = setInterval(async () => {
                const stats = await webRTCService.getConnectionStats(recipientUser.id);
                if (stats) {
                    // Paket kaybı ve gecikmeye göre kalite belirle
                    if (stats.packetsLost > 50 || stats.roundTripTime > 300) {
                        setConnectionQuality('poor');
                    } else if (stats.packetsLost > 20 || stats.roundTripTime > 150) {
                        setConnectionQuality('fair');
                    } else {
                        setConnectionQuality('good');
                    }
                }
            }, 5000);

            return () => {
                clearInterval(durationTimerRef.current);
                clearInterval(qualityInterval);
            };
        }
    }, [callStatus]);

    /**
     * Aramayı başlat
     */
    const initializeCall = async () => {
        try {
            // Kullanıcının kamera ve mikrofonunu aç
            const stream = await webRTCService.getUserMedia(true, true);
            
            // Local video element'e stream'i bağla
            if (localVideoRef.current) {
                localVideoRef.current.srcObject = stream;
            }

            if (isIncoming) {
                // Gelen arama - Kabul et butonuna basılana kadar bekle
                setCallStatus('ringing');
            } else {
                // Giden arama - Peer bağlantısı oluştur
                setCallStatus('calling');
                
                // WebRTC peer oluştur
                webRTCService.createPeer(recipientUser.id, stream);
                
                // WebSocket ile call signal gönder
                echo.private(`user.${recipientUser.id}`)
                    .whisper('call-signal', {
                        from: {{ auth()->id() }},
                        conversationId: conversationId,
                        type: 'video',
                    });
            }
        } catch (error) {
            console.error('Arama başlatma hatası:', error);
            alert(error.message);
            onEnd && onEnd();
        }
    };

    /**
     * Gelen aramayı kabul et
     */
    const acceptCall = async (signal) => {
        try {
            const stream = await webRTCService.getUserMedia(true, true);
            
            if (localVideoRef.current) {
                localVideoRef.current.srcObject = stream;
            }

            // Aramayı kabul et ve peer bağlantısı oluştur
            webRTCService.acceptCall(recipientUser.id, signal, stream);
            
            setCallStatus('connected');
        } catch (error) {
            console.error('Arama kabul etme hatası:', error);
            alert(error.message);
            onEnd && onEnd();
        }
    };

    /**
     * WebRTC signal event handler
     */
    const handleSignal = (userId, signal) => {
        // Signal'i WebSocket üzerinden karşı tarafa gönder
        echo.private(`user.${userId}`)
            .whisper('webrtc-signal', {
                from: {{ auth()->id() }},
                signal: signal,
            });
    };

    /**
     * Remote stream event handler
     */
    const handleRemoteStream = (userId, stream) => {
        // Karşı tarafın video stream'ini göster
        if (remoteVideoRef.current) {
            remoteVideoRef.current.srcObject = stream;
        }
        
        setCallStatus('connected');
    };

    /**
     * Error event handler
     */
    const handleError = (userId, error) => {
        console.error('WebRTC hatası:', error);
        alert('Bağlantı hatası: ' + error.message);
        endCall();
    };

    /**
     * Close event handler
     */
    const handleClose = (userId) => {
        endCall();
    };

    /**
     * Mikrofonun sesini aç/kapa
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
     * Ekran paylaşımını başlat/durdur
     */
    const toggleScreenShare = async () => {
        const success = await webRTCService.toggleScreenShare(!isScreenSharing);
        if (success !== undefined) {
            setIsScreenSharing(!isScreenSharing);
        }
    };

    /**
     * Aramayı sonlandır
     */
    const endCall = () => {
        webRTCService.endCall();
        
        // WebSocket ile karşı tarafa bildir
        echo.private(`user.${recipientUser.id}`)
            .whisper('call-ended', {
                from: {{ auth()->id() }},
            });
        
        onEnd && onEnd();
    };

    /**
     * Arama süresini formatla (MM:SS)
     */
    const formatDuration = (seconds) => {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    };

    /**
     * Bağlantı kalitesi göstergesi rengi
     */
    const getQualityColor = () => {
        switch (connectionQuality) {
            case 'good': return 'success';
            case 'fair': return 'warning';
            case 'poor': return 'danger';
            default: return 'secondary';
        }
    };

    return (
        <div className="video-call-container position-fixed top-0 start-0 w-100 h-100 bg-dark">
            {/* Header - Arama bilgileri */}
            <div className="video-call-header p-3 text-white d-flex justify-content-between align-items-center">
                <div>
                    <h5 className="mb-0">{recipientUser.name}</h5>
                    <small className="text-muted">
                        {callStatus === 'ringing' && 'Gelen arama...'}
                        {callStatus === 'calling' && 'Aranıyor...'}
                        {callStatus === 'connecting' && 'Bağlanıyor...'}
                        {callStatus === 'connected' && formatDuration(callDuration)}
                    </small>
                </div>
                
                {/* Bağlantı kalitesi göstergesi */}
                {callStatus === 'connected' && (
                    <div>
                        <span className={`badge bg-${getQualityColor()} me-2`}>
                            {connectionQuality === 'good' && '📶 İyi'}
                            {connectionQuality === 'fair' && '📶 Orta'}
                            {connectionQuality === 'poor' && '📶 Zayıf'}
                        </span>
                    </div>
                )}
            </div>

            {/* Video alanları */}
            <div className="video-streams position-relative w-100 h-100">
                {/* Remote video (Tam ekran) */}
                <video
                    ref={remoteVideoRef}
                    autoPlay
                    playsInline
                    className="remote-video w-100 h-100 object-fit-cover"
                    style={{ transform: 'scaleX(-1)' }}  // Ayna efekti
                />

                {/* Local video (Küçük önizleme) */}
                <div className="local-video-container position-absolute" style={{ top: '20px', right: '20px', width: '200px', height: '150px' }}>
                    <video
                        ref={localVideoRef}
                        autoPlay
                        playsInline
                        muted  // Echo önleme için kendi sesimizi kapatıyoruz
                        className="local-video w-100 h-100 rounded shadow-lg object-fit-cover"
                        style={{ transform: 'scaleX(-1)' }}
                    />
                    {isVideoOff && (
                        <div className="position-absolute top-0 start-0 w-100 h-100 bg-dark d-flex align-items-center justify-content-center rounded">
                            <span className="text-white">📷 Kapalı</span>
                        </div>
                    )}
                </div>

                {/* Gelen arama - Kabul et / Reddet butonları */}
                {callStatus === 'ringing' && isIncoming && (
                    <div className="incoming-call-actions position-absolute bottom-0 start-0 w-100 p-4 text-center">
                        <button
                            className="btn btn-success btn-lg rounded-circle me-3"
                            onClick={acceptCall}
                            style={{ width: '80px', height: '80px' }}
                        >
                            📞
                        </button>
                        <button
                            className="btn btn-danger btn-lg rounded-circle"
                            onClick={endCall}
                            style={{ width: '80px', height: '80px' }}
                        >
                            📵
                        </button>
                    </div>
                )}
            </div>

            {/* Kontrol butonları */}
            {callStatus === 'connected' && (
                <div className="video-controls position-absolute bottom-0 start-0 w-100 p-4 d-flex justify-content-center gap-3">
                    {/* Mikrofon aç/kapa */}
                    <button
                        className={`btn btn-lg rounded-circle ${isMuted ? 'btn-danger' : 'btn-secondary'}`}
                        onClick={toggleMute}
                        title={isMuted ? 'Mikrofonu Aç' : 'Mikrofonu Kapat'}
                        style={{ width: '60px', height: '60px' }}
                    >
                        {isMuted ? '🔇' : '🎤'}
                    </button>

                    {/* Kamera aç/kapa */}
                    <button
                        className={`btn btn-lg rounded-circle ${isVideoOff ? 'btn-danger' : 'btn-secondary'}`}
                        onClick={toggleVideo}
                        title={isVideoOff ? 'Kamerayı Aç' : 'Kamerayı Kapat'}
                        style={{ width: '60px', height: '60px' }}
                    >
                        {isVideoOff ? '📷' : '📹'}
                    </button>

                    {/* Ekran paylaşımı */}
                    <button
                        className={`btn btn-lg rounded-circle ${isScreenSharing ? 'btn-primary' : 'btn-secondary'}`}
                        onClick={toggleScreenShare}
                        title={isScreenSharing ? 'Ekran Paylaşımını Durdur' : 'Ekran Paylaş'}
                        style={{ width: '60px', height: '60px' }}
                    >
                        🖥️
                    </button>

                    {/* Aramayı bitir */}
                    <button
                        className="btn btn-danger btn-lg rounded-circle"
                        onClick={endCall}
                        title="Aramayı Bitir"
                        style={{ width: '60px', height: '60px' }}
                    >
                        📵
                    </button>
                </div>
            )}
        </div>
    );
}
