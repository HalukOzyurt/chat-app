import React, { useState, useRef } from 'react';

/**
 * FileUpload Component
 * 
 * Dosya yükleme ve önizleme için kullanılır
 * Desteklenen dosya tipleri: resim, video, ses, döküman
 */
export default function FileUpload({ onFileSelect, onCancel }) {
    const [selectedFile, setSelectedFile] = useState(null);      // Seçili dosya
    const [preview, setPreview] = useState(null);                // Dosya önizlemesi
    const [fileType, setFileType] = useState(null);              // Dosya tipi
    const fileInputRef = useRef(null);                           // File input ref

    /**
     * Dosya seçildiğinde
     */
    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (!file) return;

        // Dosya boyutu kontrolü (maksimum 50MB)
        const maxSize = 50 * 1024 * 1024; // 50MB
        if (file.size > maxSize) {
            alert('Dosya boyutu çok büyük! Maksimum 50MB yükleyebilirsiniz.');
            return;
        }

        setSelectedFile(file);

        // Dosya tipini belirle
        const type = file.type.split('/')[0]; // image, video, audio, application
        setFileType(type);

        // Resim ve video için önizleme oluştur
        if (type === 'image' || type === 'video') {
            const reader = new FileReader();
            reader.onload = (e) => {
                setPreview(e.target.result);
            };
            reader.readAsDataURL(file);
        }
    };

    /**
     * Dosyayı gönder
     */
    const handleSend = () => {
        if (selectedFile) {
            onFileSelect(selectedFile);
            handleCancel();
        }
    };

    /**
     * İptal et ve sıfırla
     */
    const handleCancel = () => {
        setSelectedFile(null);
        setPreview(null);
        setFileType(null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
        onCancel();
    };

    /**
     * Dosya boyutunu okunabilir formata çevir
     */
    const formatFileSize = (bytes) => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    };

    /**
     * Dosya tipine göre ikon getir
     */
    const getFileIcon = () => {
        if (!selectedFile) return '📎';
        
        const type = selectedFile.type;
        if (type.startsWith('image/')) return '🖼️';
        if (type.startsWith('video/')) return '🎥';
        if (type.startsWith('audio/')) return '🎵';
        if (type.includes('pdf')) return '📄';
        if (type.includes('word') || type.includes('document')) return '📝';
        if (type.includes('excel') || type.includes('spreadsheet')) return '📊';
        if (type.includes('zip') || type.includes('rar')) return '📦';
        return '📎';
    };

    return (
        <div className="file-upload-container">
            {/* Dosya seçme butonu */}
            {!selectedFile && (
                <div className="text-center p-4">
                    <input
                        ref={fileInputRef}
                        type="file"
                        onChange={handleFileChange}
                        className="d-none"
                        accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar"
                    />
                    <button
                        type="button"
                        className="btn btn-outline-primary btn-lg"
                        onClick={() => fileInputRef.current?.click()}
                    >
                        📎 Dosya Seç
                    </button>
                    <p className="text-muted small mt-2 mb-0">
                        Maksimum dosya boyutu: 50MB
                    </p>
                </div>
            )}

            {/* Dosya önizleme */}
            {selectedFile && (
                <div className="file-preview-container p-3">
                    <h6 className="mb-3">Dosya Önizleme</h6>
                    
                    <div className="card">
                        <div className="card-body">
                            {/* Resim önizleme */}
                            {fileType === 'image' && preview && (
                                <div className="text-center mb-3">
                                    <img
                                        src={preview}
                                        alt="Preview"
                                        className="img-fluid rounded"
                                        style={{ maxHeight: '300px' }}
                                    />
                                </div>
                            )}

                            {/* Video önizleme */}
                            {fileType === 'video' && preview && (
                                <div className="text-center mb-3">
                                    <video
                                        src={preview}
                                        controls
                                        className="w-100 rounded"
                                        style={{ maxHeight: '300px' }}
                                    />
                                </div>
                            )}

                            {/* Dosya bilgileri */}
                            <div className="d-flex align-items-center">
                                <div className="me-3" style={{ fontSize: '2rem' }}>
                                    {getFileIcon()}
                                </div>
                                <div className="flex-grow-1">
                                    <div className="fw-semibold">{selectedFile.name}</div>
                                    <small className="text-muted">
                                        {formatFileSize(selectedFile.size)}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Aksiyon butonları */}
                    <div className="d-flex gap-2 mt-3">
                        <button
                            type="button"
                            className="btn btn-primary flex-grow-1"
                            onClick={handleSend}
                        >
                            📤 Gönder
                        </button>
                        <button
                            type="button"
                            className="btn btn-outline-secondary"
                            onClick={handleCancel}
                        >
                            ❌ İptal
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
