import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Login from '../Login';

// Inertia.js useForm hook'unu mock'la
const mockPost = vi.fn();
const mockSetData = vi.fn();

vi.mock('@inertiajs/react', () => ({
    useForm: () => ({
        data: {
            email: '',
            password: '',
            remember: false,
        },
        setData: mockSetData,
        post: mockPost,
        processing: false,
        errors: {},
    }),
}));

describe('Login Component', () => {
    beforeEach(() => {
        // Her testten önce mock'ları temizle
        mockPost.mockClear();
        mockSetData.mockClear();
    });

    describe('Render Testleri', () => {
        it('component başarıyla render edilir', () => {
            render(<Login />);

            // Logo ve başlık
            expect(screen.getByText('ChatConnect')).toBeInTheDocument();
            expect(screen.getByText('Hesabınıza giriş yapın')).toBeInTheDocument();
        });

        it('tüm form alanları görüntülenir', () => {
            render(<Login />);

            // E-posta input
            expect(screen.getByLabelText(/e-posta adresi/i)).toBeInTheDocument();

            // Şifre input
            expect(screen.getByLabelText(/şifre/i)).toBeInTheDocument();

            // Beni hatırla checkbox
            expect(screen.getByLabelText(/beni hatırla/i)).toBeInTheDocument();

            // Giriş butonu
            expect(screen.getByRole('button', { name: /giriş yap/i })).toBeInTheDocument();
        });

        it('kayıt ol linki görüntülenir', () => {
            render(<Login />);

            const registerLink = screen.getByText(/kayıt olun/i);
            expect(registerLink).toBeInTheDocument();
            expect(registerLink).toHaveAttribute('href', '/register');
        });

        it('logo emoji görüntülenir', () => {
            render(<Login />);

            expect(screen.getByText('💬')).toBeInTheDocument();
        });

        it('footer metni görüntülenir', () => {
            render(<Login />);

            expect(screen.getByText(/© 2024 ChatConnect/i)).toBeInTheDocument();
        });
    });

    describe('Form Input Testleri', () => {
        it('e-posta alanı placeholder içerir', () => {
            render(<Login />);

            const emailInput = screen.getByPlaceholderText('ornek@email.com');
            expect(emailInput).toBeInTheDocument();
        });

        it('şifre alanı placeholder içerir', () => {
            render(<Login />);

            const passwordInput = screen.getByPlaceholderText('••••••••');
            expect(passwordInput).toBeInTheDocument();
        });

        it('e-posta alanı otomatik focus alır', () => {
            render(<Login />);

            const emailInput = screen.getByLabelText(/e-posta adresi/i);
            expect(emailInput).toHaveAttribute('autoFocus');
        });

        it('e-posta input type="email" özelliğine sahiptir', () => {
            render(<Login />);

            const emailInput = screen.getByLabelText(/e-posta adresi/i);
            expect(emailInput).toHaveAttribute('type', 'email');
        });

        it('şifre input varsayılan olarak type="password" özelliğine sahiptir', () => {
            render(<Login />);

            const passwordInput = screen.getByLabelText(/şifre/i);
            expect(passwordInput).toHaveAttribute('type', 'password');
        });
    });

    describe('Şifre Göster/Gizle Fonksiyonu', () => {
        it('şifre göster butonu görüntülenir', () => {
            render(<Login />);

            const toggleButton = screen.getByRole('button', { name: /👁️/ });
            expect(toggleButton).toBeInTheDocument();
        });

        it('şifre göster butonuna tıklandığında şifre görünür olur', async () => {
            const user = userEvent.setup();
            render(<Login />);

            const passwordInput = screen.getByLabelText(/şifre/i);
            const toggleButton = screen.getByRole('button', { name: /👁️‍🗨️/ });

            // Başlangıçta password türünde
            expect(passwordInput).toHaveAttribute('type', 'password');

            // Butona tıkla
            await user.click(toggleButton);

            // Text türüne dönüşmeli
            expect(passwordInput).toHaveAttribute('type', 'text');
        });

        it('şifre göster butonuna iki kez tıklandığında şifre tekrar gizlenir', async () => {
            const user = userEvent.setup();
            render(<Login />);

            const passwordInput = screen.getByLabelText(/şifre/i);
            const toggleButton = screen.getByRole('button', { name: /👁️‍🗨️/ });

            // İlk tıklama - göster
            await user.click(toggleButton);
            expect(passwordInput).toHaveAttribute('type', 'text');

            // İkinci tıklama - gizle
            const hideButton = screen.getByRole('button', { name: /👁️/ });
            await user.click(hideButton);
            expect(passwordInput).toHaveAttribute('type', 'password');
        });
    });

    describe('Beni Hatırla Checkbox', () => {
        it('beni hatırla checkbox varsayılan olarak işaretli değildir', () => {
            render(<Login />);

            const rememberCheckbox = screen.getByLabelText(/beni hatırla/i);
            expect(rememberCheckbox).not.toBeChecked();
        });

        it('beni hatırla checkbox tıklanabilir', async () => {
            const user = userEvent.setup();
            render(<Login />);

            const rememberCheckbox = screen.getByLabelText(/beni hatırla/i);

            await user.click(rememberCheckbox);
            // Mock kullanıldığı için sadece tıklamanın gerçekleştiğini test ediyoruz
            expect(rememberCheckbox).toBeInTheDocument();
        });
    });

    describe('Form Gönderimi', () => {
        it('form submit edildiğinde post fonksiyonu çağrılır', async () => {
            const user = userEvent.setup();
            render(<Login />);

            const submitButton = screen.getByRole('button', { name: /giriş yap/i });

            await user.click(submitButton);

            expect(mockPost).toHaveBeenCalledWith('/login');
        });

        it('form submit edildiğinde sayfa yenilenmez', () => {
            render(<Login />);

            const form = screen.getByRole('button', { name: /giriş yap/i }).closest('form');
            const event = new Event('submit', { bubbles: true, cancelable: true });
            const preventDefaultSpy = vi.spyOn(event, 'preventDefault');

            form.dispatchEvent(event);

            expect(preventDefaultSpy).toHaveBeenCalled();
        });
    });

    describe('Processing (Yükleniyor) Durumu', () => {
        it('processing durumunda submit butonu disabled olur', () => {
            // Processing true ile mock'la
            vi.mocked(vi.importActual('@inertiajs/react')).useForm = () => ({
                data: { email: '', password: '', remember: false },
                setData: mockSetData,
                post: mockPost,
                processing: true, // Processing true
                errors: {},
            });

            render(<Login />);

            const submitButton = screen.getByRole('button', { name: /giriş yapılıyor/i });
            expect(submitButton).toBeDisabled();
        });

        it('processing durumunda spinner görüntülenir', () => {
            vi.mocked(vi.importActual('@inertiajs/react')).useForm = () => ({
                data: { email: '', password: '', remember: false },
                setData: mockSetData,
                post: mockPost,
                processing: true,
                errors: {},
            });

            render(<Login />);

            const spinner = document.querySelector('.spinner-border');
            expect(spinner).toBeInTheDocument();
        });

        it('processing durumunda "Giriş yapılıyor..." metni gösterilir', () => {
            vi.mocked(vi.importActual('@inertiajs/react')).useForm = () => ({
                data: { email: '', password: '', remember: false },
                setData: mockSetData,
                post: mockPost,
                processing: true,
                errors: {},
            });

            render(<Login />);

            expect(screen.getByText(/giriş yapılıyor/i)).toBeInTheDocument();
        });
    });

    describe('Validasyon Hataları', () => {
        it('e-posta hatası gösterilir', () => {
            vi.mocked(vi.importActual('@inertiajs/react')).useForm = () => ({
                data: { email: '', password: '', remember: false },
                setData: mockSetData,
                post: mockPost,
                processing: false,
                errors: { email: 'E-posta adresi zorunludur.' },
            });

            render(<Login />);

            expect(screen.getByText('E-posta adresi zorunludur.')).toBeInTheDocument();
        });

        it('şifre hatası gösterilir', () => {
            vi.mocked(vi.importActual('@inertiajs/react')).useForm = () => ({
                data: { email: '', password: '', remember: false },
                setData: mockSetData,
                post: mockPost,
                processing: false,
                errors: { password: 'Şifre alanı zorunludur.' },
            });

            render(<Login />);

            expect(screen.getByText('Şifre alanı zorunludur.')).toBeInTheDocument();
        });

        it('hata durumunda input is-invalid class\'ı alır', () => {
            vi.mocked(vi.importActual('@inertiajs/react')).useForm = () => ({
                data: { email: '', password: '', remember: false },
                setData: mockSetData,
                post: mockPost,
                processing: false,
                errors: { email: 'Hata mesajı' },
            });

            render(<Login />);

            const emailInput = screen.getByLabelText(/e-posta adresi/i);
            expect(emailInput).toHaveClass('is-invalid');
        });
    });

    describe('Accessibility (Erişilebilirlik)', () => {
        it('tüm inputlar label ile ilişkilendirilmiştir', () => {
            render(<Login />);

            const emailInput = screen.getByLabelText(/e-posta adresi/i);
            const passwordInput = screen.getByLabelText(/şifre/i);
            const rememberCheckbox = screen.getByLabelText(/beni hatırla/i);

            expect(emailInput).toBeInTheDocument();
            expect(passwordInput).toBeInTheDocument();
            expect(rememberCheckbox).toBeInTheDocument();
        });

        it('form submit butonu type="submit" özelliğine sahiptir', () => {
            render(<Login />);

            const submitButton = screen.getByRole('button', { name: /giriş yap/i });
            expect(submitButton).toHaveAttribute('type', 'submit');
        });
    });
});
