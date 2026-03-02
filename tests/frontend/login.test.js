import { beforeAll, beforeEach, describe, expect, it, vi } from 'vitest';

let authModule;

function buildSubmitEvent(payload) {
    const form = document.createElement('form');
    Object.entries(payload).forEach(([key, value]) => {
        const input = document.createElement('input');
        input.name = key;
        input.value = value;
        form.appendChild(input);
    });

    return {
        preventDefault: vi.fn(),
        currentTarget: form,
    };
}

describe('login flows', () => {
    beforeAll(async () => {
        document.body.innerHTML = '<div id="app"></div>';
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue({
            ok: true,
            json: () => Promise.resolve({ data: null, is_admin: false }),
        }));
        authModule = await import('../../resources/js/app');
    });

    beforeEach(() => {
        document.body.innerHTML = '<div id="app"></div>';
        authModule.state.pendingOtpEmail = '';
        vi.restoreAllMocks();
    });

    it('renders the login form with email + password fields', () => {
        document.body.innerHTML = authModule.renderLogin();

        const form = document.querySelector('#login-form');
        const emailInput = document.querySelector('#login-email');
        const passwordInput = document.querySelector('#login-password');

        expect(form).toBeTruthy();
        expect(emailInput?.getAttribute('type')).toBe('email');
        expect(passwordInput?.getAttribute('type')).toBe('password');
    });

    it('submits login, stores pending OTP email and navigates to verify page', async () => {
        const apiMock = vi.fn().mockResolvedValue({});
        const flashSpy = vi.fn();
        const navigateSpy = vi.fn();

        const event = buildSubmitEvent({ email: 'jane@example.com', password: 'secret' });

        await authModule.handleLoginSubmit(event, {
            apiFn: apiMock,
            flashFn: flashSpy,
            navigateFn: navigateSpy,
        });

        expect(apiMock).toHaveBeenCalledWith('/api/login', expect.any(Object));
        expect(authModule.state.pendingOtpEmail).toBe('jane@example.com');
        expect(flashSpy).toHaveBeenCalledWith('success', expect.stringContaining('OTP sent'));
        expect(navigateSpy).toHaveBeenCalledWith('#/verify-otp');
    });

    it('displays an error flash when login fails', async () => {
        const apiMock = vi.fn().mockRejectedValue(new Error('Invalid credentials'));
        const flashSpy = vi.fn();
        const navigateSpy = vi.fn();

        const event = buildSubmitEvent({ email: 'jane@example.com', password: 'wrong' });

        await authModule.handleLoginSubmit(event, {
            apiFn: apiMock,
            flashFn: flashSpy,
            navigateFn: navigateSpy,
        });

        expect(flashSpy).toHaveBeenCalledWith('error', 'Invalid credentials');
        expect(navigateSpy).not.toHaveBeenCalled();
    });

    it('renders the OTP view with pending email inserted', () => {
        authModule.state.pendingOtpEmail = 'otp@example.com';
        document.body.innerHTML = authModule.renderVerifyOtp();

        const emailInput = document.querySelector('#otp-email');

        expect(emailInput?.value).toBe('otp@example.com');
    });

    it('verifies OTP, refreshes user data and navigates to dashboard', async () => {
        const apiMock = vi.fn().mockResolvedValue({});
        const flashSpy = vi.fn();
        const navigateSpy = vi.fn();
        const loadMeSpy = vi.fn().mockResolvedValue(null);
        const resetSpy = vi.fn();

        const event = buildSubmitEvent({ email: 'otp@example.com', otp: '123456' });

        await authModule.handleVerifyOtpSubmit(event, {
            apiFn: apiMock,
            flashFn: flashSpy,
            navigateFn: navigateSpy,
            loadMeFn: loadMeSpy,
            resetCollectionsFn: resetSpy,
        });

        expect(apiMock).toHaveBeenCalledWith('/api/login/verify-otp', expect.any(Object));
        expect(loadMeSpy).toHaveBeenCalled();
        expect(resetSpy).toHaveBeenCalled();
        expect(flashSpy).toHaveBeenCalledWith('success', 'Logged in successfully.');
        expect(navigateSpy).toHaveBeenCalledWith('#/dashboard');
    });

    it('shows error flash on OTP verification failure', async () => {
        const apiMock = vi.fn().mockRejectedValue(new Error('OTP invalid'));
        const flashSpy = vi.fn();
        const navigateSpy = vi.fn();
        const loadMeSpy = vi.fn();
        const resetSpy = vi.fn();

        const event = buildSubmitEvent({ email: 'otp@example.com', otp: '000000' });

        await authModule.handleVerifyOtpSubmit(event, {
            apiFn: apiMock,
            flashFn: flashSpy,
            navigateFn: navigateSpy,
            loadMeFn: loadMeSpy,
            resetCollectionsFn: resetSpy,
        });

        expect(loadMeSpy).not.toHaveBeenCalled();
        expect(resetSpy).not.toHaveBeenCalled();
        expect(flashSpy).toHaveBeenCalledWith('error', 'OTP invalid');
        expect(navigateSpy).not.toHaveBeenCalled();
    });
});
