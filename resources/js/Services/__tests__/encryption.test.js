import { describe, it, expect, beforeEach, vi } from 'vitest';
import encryptionService from '../encryption';

describe('EncryptionService', () => {
    beforeEach(() => {
        // localStorage'ı temizle
        localStorage.clear();

        // KeyPair'i sıfırla
        encryptionService.keyPair = null;
        encryptionService.publicKeys.clear();
    });

    describe('Anahtar Çifti Oluşturma', () => {
        it('anahtar çifti oluşturulabilir', async () => {
            const keyPair = await encryptionService.generateKeyPair();

            expect(keyPair).toBeDefined();
            expect(keyPair.publicKey).toBeDefined();
            expect(keyPair.privateKey).toBeDefined();
            expect(encryptionService.keyPair).toBe(keyPair);
        });

        it('public key localStorage\'a kaydedilir', async () => {
            await encryptionService.generateKeyPair();

            const storedKey = localStorage.getItem('e2e_public_key');
            expect(storedKey).toBeDefined();
            expect(storedKey).not.toBeNull();

            // JWK formatında olmalı
            const keyData = JSON.parse(storedKey);
            expect(keyData.kty).toBe('RSA');
            expect(keyData.alg).toBeDefined();
        });
    });

    describe('Public Key Yükleme', () => {
        it('localStorage\'dan public key yüklenir', async () => {
            // Önce bir anahtar oluştur
            await encryptionService.generateKeyPair();
            const storedKey = localStorage.getItem('e2e_public_key');

            // KeyPair'i sıfırla
            encryptionService.keyPair = null;

            // Public key'i yükle
            const publicKey = await encryptionService.loadPublicKey();

            expect(publicKey).toBeDefined();
            expect(publicKey.type).toBe('public');
        });

        it('public key yoksa yeni anahtar oluşturulur', async () => {
            // localStorage boş
            expect(localStorage.getItem('e2e_public_key')).toBeNull();

            await encryptionService.loadPublicKey();

            // Yeni anahtar oluşturulmuş olmalı
            expect(encryptionService.keyPair).toBeDefined();
            expect(localStorage.getItem('e2e_public_key')).not.toBeNull();
        });

        it('bozuk public key durumunda yeni anahtar oluşturulur', async () => {
            // Bozuk veri
            localStorage.setItem('e2e_public_key', 'invalid-json');

            await encryptionService.loadPublicKey();

            // Hata durumunda yeni anahtar oluşturulmalı
            expect(encryptionService.keyPair).toBeDefined();
        });
    });

    describe('Diğer Kullanıcıların Public Key\'lerini Kaydetme', () => {
        it('başka kullanıcının public key\'i eklenebilir', async () => {
            // Önce kendi anahtarımızı oluştur
            await encryptionService.generateKeyPair();

            // Public key'i export et
            const publicKeyJwk = await window.crypto.subtle.exportKey(
                'jwk',
                encryptionService.keyPair.publicKey
            );

            // Başka bir kullanıcı için ekle
            await encryptionService.addPublicKey(123, publicKeyJwk);

            expect(encryptionService.publicKeys.has(123)).toBe(true);
            expect(encryptionService.publicKeys.get(123)).toBeDefined();
        });
    });

    describe('Mesaj Şifreleme ve Şifre Çözme', () => {
        it('mesaj şifrelenir ve şifresi çözülür', async () => {
            const originalMessage = 'Merhaba, bu gizli bir mesaj!';

            // Alıcının anahtar çiftini oluştur
            await encryptionService.generateKeyPair();
            const recipientPublicKeyJwk = await encryptionService.getPublicKeyJwk();
            const recipientKeyPair = encryptionService.keyPair;

            // Gönderenin perspektifinden public key'i ekle
            await encryptionService.addPublicKey(123, recipientPublicKeyJwk);

            // Mesajı şifrele
            const encryptedData = await encryptionService.encryptMessage(originalMessage, 123);

            expect(encryptedData).toBeDefined();
            expect(encryptedData.encryptedMessage).toBeDefined();
            expect(encryptedData.encryptedKey).toBeDefined();
            expect(encryptedData.iv).toBeDefined();

            // Alıcının perspektifine geç
            encryptionService.keyPair = recipientKeyPair;

            // Mesajın şifresini çöz
            const decryptedMessage = await encryptionService.decryptMessage(encryptedData);

            expect(decryptedMessage).toBe(originalMessage);
        });

        it('şifrelenmiş mesaj orijinal mesajdan farklıdır', async () => {
            const originalMessage = 'Gizli mesaj';

            await encryptionService.generateKeyPair();
            const publicKeyJwk = await encryptionService.getPublicKeyJwk();
            await encryptionService.addPublicKey(123, publicKeyJwk);

            const encryptedData = await encryptionService.encryptMessage(originalMessage, 123);

            // Şifrelenmiş mesaj orijinal mesajdan farklı olmalı
            expect(encryptedData.encryptedMessage).not.toBe(originalMessage);
        });

        it('özel karakterler şifrelenir ve şifresi çözülür', async () => {
            const originalMessage = 'Türkçe karakterler: ğüşıöç 🎉 !@#$%^&*()';

            await encryptionService.generateKeyPair();
            const publicKeyJwk = await encryptionService.getPublicKeyJwk();
            const recipientKeyPair = encryptionService.keyPair;

            await encryptionService.addPublicKey(123, publicKeyJwk);
            const encryptedData = await encryptionService.encryptMessage(originalMessage, 123);

            encryptionService.keyPair = recipientKeyPair;
            const decryptedMessage = await encryptionService.decryptMessage(encryptedData);

            expect(decryptedMessage).toBe(originalMessage);
        });

        it('boş mesaj şifrelenir', async () => {
            const originalMessage = '';

            await encryptionService.generateKeyPair();
            const publicKeyJwk = await encryptionService.getPublicKeyJwk();
            const recipientKeyPair = encryptionService.keyPair;

            await encryptionService.addPublicKey(123, publicKeyJwk);
            const encryptedData = await encryptionService.encryptMessage(originalMessage, 123);

            encryptionService.keyPair = recipientKeyPair;
            const decryptedMessage = await encryptionService.decryptMessage(encryptedData);

            expect(decryptedMessage).toBe(originalMessage);
        });

        it('uzun mesaj şifrelenir', async () => {
            const originalMessage = 'A'.repeat(10000); // 10,000 karakter

            await encryptionService.generateKeyPair();
            const publicKeyJwk = await encryptionService.getPublicKeyJwk();
            const recipientKeyPair = encryptionService.keyPair;

            await encryptionService.addPublicKey(123, publicKeyJwk);
            const encryptedData = await encryptionService.encryptMessage(originalMessage, 123);

            encryptionService.keyPair = recipientKeyPair;
            const decryptedMessage = await encryptionService.decryptMessage(encryptedData);

            expect(decryptedMessage).toBe(originalMessage);
        });

        it('alıcının public key\'i yoksa hata fırlatır', async () => {
            await encryptionService.generateKeyPair();

            // Public key eklenmedi
            await expect(
                encryptionService.encryptMessage('Test mesajı', 999)
            ).rejects.toThrow('Alıcının public key\'i bulunamadı');
        });

        it('private key olmadan şifre çözülemez', async () => {
            await encryptionService.generateKeyPair();
            const publicKeyJwk = await encryptionService.getPublicKeyJwk();
            await encryptionService.addPublicKey(123, publicKeyJwk);

            const encryptedData = await encryptionService.encryptMessage('Test', 123);

            // Private key'i kaldır
            encryptionService.keyPair = null;

            await expect(
                encryptionService.decryptMessage(encryptedData)
            ).rejects.toThrow('Private key bulunamadı');
        });
    });

    describe('Base64 Dönüşümleri', () => {
        it('ArrayBuffer Base64\'e dönüştürülür', () => {
            const testData = new Uint8Array([72, 101, 108, 108, 111]); // "Hello"
            const base64 = encryptionService.arrayBufferToBase64(testData.buffer);

            expect(base64).toBe('SGVsbG8=');
        });

        it('Base64 ArrayBuffer\'a dönüştürülür', () => {
            const base64 = 'SGVsbG8=';
            const buffer = encryptionService.base64ToArrayBuffer(base64);
            const bytes = new Uint8Array(buffer);

            expect(bytes[0]).toBe(72); // 'H'
            expect(bytes[1]).toBe(101); // 'e'
            expect(bytes[2]).toBe(108); // 'l'
            expect(bytes[3]).toBe(108); // 'l'
            expect(bytes[4]).toBe(111); // 'o'
        });

        it('ArrayBuffer -> Base64 -> ArrayBuffer dönüşümü veri kaybına neden olmaz', () => {
            const originalData = new Uint8Array([1, 2, 3, 4, 5, 255, 128, 0]);

            const base64 = encryptionService.arrayBufferToBase64(originalData.buffer);
            const convertedBuffer = encryptionService.base64ToArrayBuffer(base64);
            const convertedData = new Uint8Array(convertedBuffer);

            expect(convertedData).toEqual(originalData);
        });
    });

    describe('Public Key JWK Export', () => {
        it('public key JWK formatında export edilir', async () => {
            await encryptionService.generateKeyPair();
            const jwk = await encryptionService.getPublicKeyJwk();

            expect(jwk).toBeDefined();
            expect(jwk.kty).toBe('RSA');
            expect(jwk.e).toBeDefined();
            expect(jwk.n).toBeDefined();
        });

        it('anahtar yoksa önce oluşturulur', async () => {
            expect(encryptionService.keyPair).toBeNull();

            const jwk = await encryptionService.getPublicKeyJwk();

            expect(jwk).toBeDefined();
            expect(encryptionService.keyPair).not.toBeNull();
        });
    });

    describe('Güvenlik', () => {
        it('aynı mesajın iki farklı şifrelemesi farklı sonuç verir', async () => {
            const message = 'Test mesajı';

            await encryptionService.generateKeyPair();
            const publicKeyJwk = await encryptionService.getPublicKeyJwk();
            await encryptionService.addPublicKey(123, publicKeyJwk);

            const encrypted1 = await encryptionService.encryptMessage(message, 123);
            const encrypted2 = await encryptionService.encryptMessage(message, 123);

            // IV rastgele olduğu için her şifreleme farklı olmalı
            expect(encrypted1.encryptedMessage).not.toBe(encrypted2.encryptedMessage);
            expect(encrypted1.iv).not.toBe(encrypted2.iv);
        });

        it('yanlış private key ile şifre çözülemez', async () => {
            // İki farklı kullanıcı
            await encryptionService.generateKeyPair();
            const user1PublicKey = await encryptionService.getPublicKeyJwk();
            const user1KeyPair = encryptionService.keyPair;

            // Kullanıcı 2
            await encryptionService.generateKeyPair();
            const user2KeyPair = encryptionService.keyPair;

            // Kullanıcı 1'e gönderilecek mesajı şifrele
            await encryptionService.addPublicKey(1, user1PublicKey);
            const encryptedData = await encryptionService.encryptMessage('Gizli mesaj', 1);

            // Kullanıcı 2'nin anahtarı ile çözmeye çalış
            encryptionService.keyPair = user2KeyPair;

            await expect(
                encryptionService.decryptMessage(encryptedData)
            ).rejects.toThrow();
        });
    });
});
