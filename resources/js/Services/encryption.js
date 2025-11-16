/**
 * E2E Encryption Service - Uçtan Uca Şifreleme
 * 
 * Web Crypto API kullanarak mesajları şifreler
 * RSA + AES hibrit şifreleme kullanır
 */

class EncryptionService {
    constructor() {
        // Kullanıcının anahtar çifti (public + private)
        this.keyPair = null;
        
        // Diğer kullanıcıların public anahtarları (userId: publicKey)
        this.publicKeys = new Map();
        
        // Şifreleme algoritmaları
        this.rsaAlgorithm = {
            name: 'RSA-OAEP',
            modulusLength: 2048,       // Anahtar uzunluğu
            publicExponent: new Uint8Array([1, 0, 1]),
            hash: 'SHA-256',
        };
        
        this.aesAlgorithm = {
            name: 'AES-GCM',
            length: 256,               // 256-bit AES
        };
    }

    /**
     * Kullanıcı için anahtar çifti oluştur
     * 
     * @returns {Promise<CryptoKeyPair>}
     */
    async generateKeyPair() {
        try {
            // RSA anahtar çifti oluştur (public + private)
            this.keyPair = await window.crypto.subtle.generateKey(
                this.rsaAlgorithm,
                true,  // extractable = true (export edilebilir)
                ['encrypt', 'decrypt']  // Kullanım amaçları
            );

            // Public key'i localStorage'a kaydet
            await this.savePublicKey();

            return this.keyPair;
        } catch (error) {
            console.error('Anahtar çifti oluşturma hatası:', error);
            throw error;
        }
    }

    /**
     * Public key'i localStorage'a kaydet
     */
    async savePublicKey() {
        try {
            // Public key'i export et (JWK formatında)
            const exportedKey = await window.crypto.subtle.exportKey(
                'jwk',
                this.keyPair.publicKey
            );

            // localStorage'a kaydet
            localStorage.setItem('e2e_public_key', JSON.stringify(exportedKey));

            return exportedKey;
        } catch (error) {
            console.error('Public key kaydetme hatası:', error);
            throw error;
        }
    }

    /**
     * Public key'i localStorage'dan yükle
     */
    async loadPublicKey() {
        try {
            const storedKey = localStorage.getItem('e2e_public_key');
            if (!storedKey) {
                // Anahtar yoksa yeni oluştur
                await this.generateKeyPair();
                return;
            }

            const keyData = JSON.parse(storedKey);

            // JWK'dan CryptoKey'e dönüştür
            const publicKey = await window.crypto.subtle.importKey(
                'jwk',
                keyData,
                this.rsaAlgorithm,
                true,
                ['encrypt']
            );

            return publicKey;
        } catch (error) {
            console.error('Public key yükleme hatası:', error);
            // Hata durumunda yeni anahtar oluştur
            await this.generateKeyPair();
        }
    }

    /**
     * Diğer kullanıcının public key'ini kaydet
     * 
     * @param {number} userId - Kullanıcı ID
     * @param {Object} publicKeyJwk - Public key (JWK formatında)
     */
    async addPublicKey(userId, publicKeyJwk) {
        try {
            // JWK'dan CryptoKey'e dönüştür
            const publicKey = await window.crypto.subtle.importKey(
                'jwk',
                publicKeyJwk,
                this.rsaAlgorithm,
                true,
                ['encrypt']
            );

            // Map'e kaydet
            this.publicKeys.set(userId, publicKey);
        } catch (error) {
            console.error('Public key ekleme hatası:', error);
            throw error;
        }
    }

    /**
     * Mesajı şifrele (Hibrit şifreleme: RSA + AES)
     * 
     * 1. AES anahtarı oluştur (rastgele)
     * 2. Mesajı AES ile şifrele
     * 3. AES anahtarını RSA ile şifrele
     * 
     * @param {string} message - Şifrelenecek mesaj
     * @param {number} recipientUserId - Alıcı kullanıcı ID
     * @returns {Promise<Object>} - Şifrelenmiş veri
     */
    async encryptMessage(message, recipientUserId) {
        try {
            // Alıcının public key'ini al
            const recipientPublicKey = this.publicKeys.get(recipientUserId);
            if (!recipientPublicKey) {
                throw new Error('Alıcının public key\'i bulunamadı');
            }

            // 1. AES anahtarı oluştur (symmetric key)
            const aesKey = await window.crypto.subtle.generateKey(
                this.aesAlgorithm,
                true,
                ['encrypt', 'decrypt']
            );

            // 2. Mesajı AES ile şifrele
            const encoder = new TextEncoder();
            const messageBuffer = encoder.encode(message);

            // IV (Initialization Vector) oluştur - Rastgele 12 byte
            const iv = window.crypto.getRandomValues(new Uint8Array(12));

            const encryptedMessageBuffer = await window.crypto.subtle.encrypt(
                {
                    name: 'AES-GCM',
                    iv: iv,
                },
                aesKey,
                messageBuffer
            );

            // 3. AES anahtarını RSA ile şifrele
            const exportedAesKey = await window.crypto.subtle.exportKey('raw', aesKey);
            const encryptedAesKey = await window.crypto.subtle.encrypt(
                {
                    name: 'RSA-OAEP',
                },
                recipientPublicKey,
                exportedAesKey
            );

            // Base64'e çevir (API'ye göndermek için)
            return {
                encryptedMessage: this.arrayBufferToBase64(encryptedMessageBuffer),
                encryptedKey: this.arrayBufferToBase64(encryptedAesKey),
                iv: this.arrayBufferToBase64(iv),
            };
        } catch (error) {
            console.error('Mesaj şifreleme hatası:', error);
            throw error;
        }
    }

    /**
     * Mesajın şifresini çöz
     * 
     * @param {Object} encryptedData - Şifrelenmiş veri
     * @returns {Promise<string>} - Orijinal mesaj
     */
    async decryptMessage(encryptedData) {
        try {
            if (!this.keyPair || !this.keyPair.privateKey) {
                throw new Error('Private key bulunamadı');
            }

            // Base64'ten ArrayBuffer'a çevir
            const encryptedMessage = this.base64ToArrayBuffer(encryptedData.encryptedMessage);
            const encryptedKey = this.base64ToArrayBuffer(encryptedData.encryptedKey);
            const iv = this.base64ToArrayBuffer(encryptedData.iv);

            // 1. AES anahtarının şifresini çöz (RSA ile)
            const aesKeyBuffer = await window.crypto.subtle.decrypt(
                {
                    name: 'RSA-OAEP',
                },
                this.keyPair.privateKey,
                encryptedKey
            );

            // AES anahtarını import et
            const aesKey = await window.crypto.subtle.importKey(
                'raw',
                aesKeyBuffer,
                this.aesAlgorithm,
                false,
                ['decrypt']
            );

            // 2. Mesajın şifresini çöz (AES ile)
            const decryptedBuffer = await window.crypto.subtle.decrypt(
                {
                    name: 'AES-GCM',
                    iv: iv,
                },
                aesKey,
                encryptedMessage
            );

            // ArrayBuffer'dan string'e çevir
            const decoder = new TextDecoder();
            return decoder.decode(decryptedBuffer);
        } catch (error) {
            console.error('Mesaj şifre çözme hatası:', error);
            throw error;
        }
    }

    /**
     * ArrayBuffer'ı Base64 string'e çevir
     */
    arrayBufferToBase64(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        bytes.forEach(b => binary += String.fromCharCode(b));
        return window.btoa(binary);
    }

    /**
     * Base64 string'i ArrayBuffer'a çevir
     */
    base64ToArrayBuffer(base64) {
        const binary = window.atob(base64);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return bytes.buffer;
    }

    /**
     * Public key'i JWK formatında döndür (API'ye göndermek için)
     */
    async getPublicKeyJwk() {
        if (!this.keyPair || !this.keyPair.publicKey) {
            await this.generateKeyPair();
        }

        return await window.crypto.subtle.exportKey('jwk', this.keyPair.publicKey);
    }
}

// Singleton instance
const encryptionService = new EncryptionService();

export default encryptionService;
