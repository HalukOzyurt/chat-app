/**
 * WebRTC Service - Peer-to-Peer Görüntülü/Sesli Arama
 * 
 * Simple-Peer kütüphanesi kullanarak WebRTC bağlantıları yönetir
 * Hem 1-1 aramalar hem de grup konferanslar için kullanılır
 */

import Peer from 'simple-peer';

class WebRTCService {
    constructor() {
        // Aktif peer bağlantıları (userId: Peer instance)
        this.peers = new Map();
        
        // Local media stream (kamera ve mikrofon)
        this.localStream = null;
        
        // Arama durumu
        this.isInCall = false;
        
        // STUN/TURN sunucuları (NAT geçişi için)
        this.iceServers = [
            // Google'ın ücretsiz STUN sunucuları
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun1.l.google.com:19302' },
            { urls: 'stun:stun2.l.google.com:19302' },
            
            // Ücretsiz TURN sunucusu (sınırlı kullanım)
            // Production'da kendi TURN sunucunuzu kullanmalısınız
            {
                urls: 'turn:openrelay.metered.ca:80',
                username: 'openrelayproject',
                credential: 'openrelayproject',
            },
        ];
    }

    /**
     * Kullanıcının kamera ve mikrofonunu başlat
     * 
     * @param {boolean} video - Video aktif mi?
     * @param {boolean} audio - Ses aktif mi?
     * @returns {Promise<MediaStream>}
     */
    async getUserMedia(video = true, audio = true) {
        try {
            // Tarayıcıdan medya izni iste
            this.localStream = await navigator.mediaDevices.getUserMedia({
                video: video ? {
                    width: { ideal: 1280 },    // İdeal genişlik
                    height: { ideal: 720 },    // İdeal yükseklik
                    frameRate: { ideal: 30 },  // FPS
                } : false,
                audio: audio ? {
                    echoCancellation: true,     // Echo iptal
                    noiseSuppression: true,     // Gürültü azaltma
                    autoGainControl: true,      // Otomatik ses seviyesi
                } : false,
            });

            this.isInCall = true;
            return this.localStream;
        } catch (error) {
            console.error('Medya erişim hatası:', error);
            
            // Kullanıcı dostu hata mesajları
            if (error.name === 'NotAllowedError') {
                throw new Error('Kamera/mikrofon izni reddedildi. Lütfen tarayıcı ayarlarından izin verin.');
            } else if (error.name === 'NotFoundError') {
                throw new Error('Kamera veya mikrofon bulunamadı.');
            } else if (error.name === 'NotReadableError') {
                throw new Error('Kamera/mikrofon başka bir uygulama tarafından kullanılıyor.');
            } else {
                throw new Error('Medya erişim hatası: ' + error.message);
            }
        }
    }

    /**
     * Ekran paylaşımını başlat
     * 
     * @returns {Promise<MediaStream>}
     */
    async getDisplayMedia() {
        try {
            const screenStream = await navigator.mediaDevices.getDisplayMedia({
                video: {
                    cursor: 'always',  // İmleci göster
                },
                audio: false,  // Ekran sesi (opsiyonel)
            });

            return screenStream;
        } catch (error) {
            console.error('Ekran paylaşım hatası:', error);
            throw new Error('Ekran paylaşımı başlatılamadı: ' + error.message);
        }
    }

    /**
     * Yeni peer bağlantısı oluştur (Arayan taraf)
     * 
     * @param {string} userId - Aranacak kullanıcı ID
     * @param {MediaStream} stream - Local media stream
     * @returns {Peer}
     */
    createPeer(userId, stream) {
        // Simple-Peer instance oluştur (initiator = arayan)
        const peer = new Peer({
            initiator: true,          // Bu peer aramayı başlatan
            trickle: false,           // Tüm ICE candidate'leri topla
            stream: stream,           // Local stream ekle
            config: {
                iceServers: this.iceServers,  // STUN/TURN sunucuları
            },
        });

        // Peer bağlantısını kaydet
        this.peers.set(userId, peer);

        // Signal event - WebRTC offer/answer için
        peer.on('signal', (signal) => {
            // Signal'i WebSocket üzerinden karşı tarafa gönder
            this.onSignal && this.onSignal(userId, signal);
        });

        // Stream event - Karşı tarafın video/audio stream'i
        peer.on('stream', (remoteStream) => {
            this.onStream && this.onStream(userId, remoteStream);
        });

        // Error event
        peer.on('error', (error) => {
            console.error('Peer hatası:', error);
            this.onError && this.onError(userId, error);
        });

        // Close event
        peer.on('close', () => {
            this.removePeer(userId);
            this.onClose && this.onClose(userId);
        });

        return peer;
    }

    /**
     * Gelen aramayı kabul et (Aranan taraf)
     * 
     * @param {string} userId - Arayan kullanıcı ID
     * @param {Object} signal - WebRTC signal (offer)
     * @param {MediaStream} stream - Local media stream
     * @returns {Peer}
     */
    acceptCall(userId, signal, stream) {
        // Simple-Peer instance oluştur (initiator = false)
        const peer = new Peer({
            initiator: false,         // Bu peer aramayı kabul eden
            trickle: false,
            stream: stream,
            config: {
                iceServers: this.iceServers,
            },
        });

        this.peers.set(userId, peer);

        // Gelen signal'i işle (offer)
        peer.signal(signal);

        // Signal event - Answer göndermek için
        peer.on('signal', (answerSignal) => {
            this.onSignal && this.onSignal(userId, answerSignal);
        });

        peer.on('stream', (remoteStream) => {
            this.onStream && this.onStream(userId, remoteStream);
        });

        peer.on('error', (error) => {
            console.error('Peer hatası:', error);
            this.onError && this.onError(userId, error);
        });

        peer.on('close', () => {
            this.removePeer(userId);
            this.onClose && this.onClose(userId);
        });

        return peer;
    }

    /**
     * Karşı taraftan gelen signal'i işle
     * 
     * @param {string} userId - Kullanıcı ID
     * @param {Object} signal - WebRTC signal (answer/ice-candidate)
     */
    handleSignal(userId, signal) {
        const peer = this.peers.get(userId);
        if (peer) {
            peer.signal(signal);
        }
    }

    /**
     * Mikrofonun sesini aç/kapa
     * 
     * @param {boolean} enabled - Açık mı?
     */
    toggleAudio(enabled) {
        if (this.localStream) {
            this.localStream.getAudioTracks().forEach(track => {
                track.enabled = enabled;
            });
        }
    }

    /**
     * Kamerayı aç/kapa
     * 
     * @param {boolean} enabled - Açık mı?
     */
    toggleVideo(enabled) {
        if (this.localStream) {
            this.localStream.getVideoTracks().forEach(track => {
                track.enabled = enabled;
            });
        }
    }

    /**
     * Kamerayı değiştir (ön/arka)
     */
    async switchCamera() {
        if (!this.localStream) return;

        const videoTrack = this.localStream.getVideoTracks()[0];
        const currentFacingMode = videoTrack.getSettings().facingMode;

        // Yeni kamera ayarı (ön <-> arka)
        const newFacingMode = currentFacingMode === 'user' ? 'environment' : 'user';

        try {
            // Yeni stream al
            const newStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: newFacingMode },
                audio: true,
            });

            // Eski video track'i durdur
            videoTrack.stop();

            // Yeni track'i kullan
            const newVideoTrack = newStream.getVideoTracks()[0];
            this.localStream.removeTrack(videoTrack);
            this.localStream.addTrack(newVideoTrack);

            // Tüm peer'lara yeni track'i gönder
            this.peers.forEach(peer => {
                const sender = peer.streams[0]?.getVideoTracks()[0];
                if (sender) {
                    peer.replaceTrack(videoTrack, newVideoTrack, this.localStream);
                }
            });
        } catch (error) {
            console.error('Kamera değiştirme hatası:', error);
        }
    }

    /**
     * Ekran paylaşımını başlat/durdur
     * 
     * @param {boolean} start - Başlat mı?
     */
    async toggleScreenShare(start) {
        if (start) {
            try {
                const screenStream = await this.getDisplayMedia();
                const screenTrack = screenStream.getVideoTracks()[0];

                // Kamera track'ini ekran track'i ile değiştir
                const videoTrack = this.localStream.getVideoTracks()[0];
                
                this.localStream.removeTrack(videoTrack);
                this.localStream.addTrack(screenTrack);

                // Tüm peer'lara yeni track'i gönder
                this.peers.forEach(peer => {
                    peer.replaceTrack(videoTrack, screenTrack, this.localStream);
                });

                // Ekran paylaşımı durdurulduğunda kameraya geri dön
                screenTrack.onended = () => {
                    this.toggleScreenShare(false);
                };

                return true;
            } catch (error) {
                console.error('Ekran paylaşım hatası:', error);
                return false;
            }
        } else {
            // Kameraya geri dön
            const screenTrack = this.localStream.getVideoTracks()[0];
            
            const cameraStream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: false,
            });
            const cameraTrack = cameraStream.getVideoTracks()[0];

            this.localStream.removeTrack(screenTrack);
            this.localStream.addTrack(cameraTrack);

            // Tüm peer'lara kamera track'ini gönder
            this.peers.forEach(peer => {
                peer.replaceTrack(screenTrack, cameraTrack, this.localStream);
            });

            screenTrack.stop();
        }
    }

    /**
     * Peer bağlantısını kaldır
     * 
     * @param {string} userId - Kullanıcı ID
     */
    removePeer(userId) {
        const peer = this.peers.get(userId);
        if (peer) {
            peer.destroy();
            this.peers.delete(userId);
        }
    }

    /**
     * Aramayı sonlandır
     */
    endCall() {
        // Tüm peer'ları kapat
        this.peers.forEach(peer => peer.destroy());
        this.peers.clear();

        // Local stream'i durdur
        if (this.localStream) {
            this.localStream.getTracks().forEach(track => track.stop());
            this.localStream = null;
        }

        this.isInCall = false;
    }

    /**
     * Event listener'ları ayarla
     */
    on(event, callback) {
        switch (event) {
            case 'signal':
                this.onSignal = callback;
                break;
            case 'stream':
                this.onStream = callback;
                break;
            case 'error':
                this.onError = callback;
                break;
            case 'close':
                this.onClose = callback;
                break;
        }
    }

    /**
     * Bağlantı kalitesini kontrol et
     * 
     * @param {string} userId - Kullanıcı ID
     * @returns {Promise<Object>} - Bağlantı istatistikleri
     */
    async getConnectionStats(userId) {
        const peer = this.peers.get(userId);
        if (!peer || !peer._pc) return null;

        try {
            const stats = await peer._pc.getStats();
            const connectionStats = {
                bytesReceived: 0,
                bytesSent: 0,
                packetsLost: 0,
                roundTripTime: 0,
            };

            stats.forEach(report => {
                if (report.type === 'inbound-rtp') {
                    connectionStats.bytesReceived += report.bytesReceived || 0;
                    connectionStats.packetsLost += report.packetsLost || 0;
                }
                if (report.type === 'outbound-rtp') {
                    connectionStats.bytesSent += report.bytesSent || 0;
                }
                if (report.type === 'candidate-pair' && report.state === 'succeeded') {
                    connectionStats.roundTripTime = report.currentRoundTripTime || 0;
                }
            });

            return connectionStats;
        } catch (error) {
            console.error('Bağlantı istatistikleri hatası:', error);
            return null;
        }
    }
}

// Singleton instance
const webRTCService = new WebRTCService();

export default webRTCService;
