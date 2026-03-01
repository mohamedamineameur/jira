import './bootstrap';

const state = {
    user: null,
    loading: false,
    flash: null,
    pendingOtpEmail: '',
};

const routes = {
    '#/': renderHome,
    '#/login': renderLogin,
    '#/verify-otp': renderVerifyOtp,
    '#/register': renderRegister,
    '#/forgot-password': renderForgotPassword,
    '#/dashboard': renderDashboard,
};

const app = document.querySelector('#app');

function navigate(hash) {
    if (window.location.hash !== hash) {
        window.location.hash = hash;
    } else {
        render();
    }
}

function setFlash(type, message) {
    state.flash = { type, message };
    render();
}

async function api(path, options = {}) {
    const response = await fetch(path, {
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            ...(options.headers || {}),
        },
        ...options,
    });

    let body = null;

    try {
        body = await response.json();
    } catch {
        body = null;
    }

    if (!response.ok) {
        const message =
            body?.message ||
            (body?.errors ? Object.values(body.errors).flat().join(' ') : null) ||
            `Request failed with status ${response.status}.`;

        throw new Error(message);
    }

    return body;
}

async function loadMe() {
    try {
        const res = await api('/api/me', { method: 'GET' });
        state.user = res.data ?? null;
    } catch {
        state.user = null;
    }
}

function withShell(content, { heroTitle, heroText } = {}) {
    const navItems = state.user
        ? `
            <li><a href="#/dashboard" class="${window.location.hash === '#/dashboard' ? 'active' : ''}">Dashboard</a></li>
            <li><a href="#/login" id="logout-link">Logout</a></li>
          `
        : `
            <li><a href="#/login" class="${window.location.hash === '#/login' ? 'active' : ''}">Login</a></li>
            <li><a href="#/register" class="${window.location.hash === '#/register' ? 'active' : ''}">Register</a></li>
            <li><a href="#/forgot-password" class="${window.location.hash === '#/forgot-password' ? 'active' : ''}">Forgot Password</a></li>
          `;

    return `
      <div class="app-root">
        <nav class="modern-nav">
          <div class="brand">Agilify</div>
          <ul>${navItems}</ul>
        </nav>
        <section class="hero">
          <h2>${heroTitle}</h2>
          <p>${heroText}</p>
        </section>
        <main class="layout">
          ${state.flash ? `<div class="alert ${state.flash.type}">${state.flash.message}</div>` : ''}
          ${content}
        </main>
        <footer class="modern-footer">Agilify SPA - Phase 1</footer>
      </div>
    `;
}

function renderHome() {
    const content = `
      <section class="cards">
        <article class="card">
          <h3>Secure Authentication</h3>
          <p>Login now uses OTP verification by email for better account protection.</p>
        </article>
        <article class="card">
          <h3>Password Recovery</h3>
          <p>Users can request a reset link and securely update their password.</p>
        </article>
        <article class="card">
          <h3>Responsive Experience</h3>
          <p>Mobile-first, glassmorphism UI with your custom gradient style.</p>
        </article>
      </section>
    `;

    return withShell(content, {
        heroTitle: 'Agilify Frontend SPA',
        heroText: 'English-only interface connected to your Laravel API.',
    });
}

function renderLogin() {
    const content = `
      <section class="cards">
        <article class="card">
          <h3>Login</h3>
          <form id="login-form">
            <div class="field">
              <label for="login-email">Email</label>
              <input class="input" id="login-email" type="email" name="email" required>
            </div>
            <div class="field">
              <label for="login-password">Password</label>
              <input class="input" id="login-password" type="password" name="password" required>
            </div>
            <button class="btn" type="submit">Send OTP</button>
          </form>
          <p class="muted">After this step, we will send a 6-digit OTP to your email.</p>
        </article>
      </section>
    `;

    return withShell(content, {
        heroTitle: 'Welcome Back',
        heroText: 'Sign in with password, then verify with OTP.',
    });
}

function renderVerifyOtp() {
    const content = `
      <section class="cards">
        <article class="card">
          <h3>Verify OTP</h3>
          <form id="verify-otp-form">
            <div class="field">
              <label for="otp-email">Email</label>
              <input class="input" id="otp-email" type="email" name="email" value="${state.pendingOtpEmail}" required>
            </div>
            <div class="field">
              <label for="otp-code">6-digit code</label>
              <input class="input otp-code" id="otp-code" type="text" name="otp" minlength="6" maxlength="6" pattern="[0-9]{6}" required>
            </div>
            <button class="btn" type="submit">Verify and Login</button>
          </form>
          <p class="muted">Enter the OTP received by email. The code expires quickly.</p>
        </article>
      </section>
    `;

    return withShell(content, {
        heroTitle: 'OTP Verification',
        heroText: 'Complete your login with the one-time code.',
    });
}

function renderRegister() {
    const content = `
      <section class="cards">
        <article class="card">
          <h3>Create Account</h3>
          <form id="register-form">
            <div class="field">
              <label for="register-name">Full name</label>
              <input class="input" id="register-name" type="text" name="name" required>
            </div>
            <div class="field">
              <label for="register-email">Email</label>
              <input class="input" id="register-email" type="email" name="email" required>
            </div>
            <div class="field">
              <label for="register-password">Password</label>
              <input class="input" id="register-password" type="password" name="password" required>
            </div>
            <div class="field">
              <label for="register-password-confirmation">Confirm password</label>
              <input class="input" id="register-password-confirmation" type="password" name="password_confirmation" required>
            </div>
            <button class="btn" type="submit">Create account</button>
          </form>
        </article>
      </section>
    `;

    return withShell(content, {
        heroTitle: 'Create Your Account',
        heroText: 'New users receive an email verification flow automatically.',
    });
}

function renderForgotPassword() {
    const content = `
      <section class="cards">
        <article class="card">
          <h3>Forgot Password</h3>
          <form id="forgot-form">
            <div class="field">
              <label for="forgot-email">Email</label>
              <input class="input" id="forgot-email" type="email" name="email" required>
            </div>
            <button class="btn" type="submit">Send reset link</button>
          </form>
          <p class="muted">You will receive an email with a secure reset link.</p>
        </article>
      </section>
    `;

    return withShell(content, {
        heroTitle: 'Reset Password',
        heroText: 'Request a password reset link using your email.',
    });
}

function renderDashboard() {
    if (!state.user) {
        return withShell(
            `
            <section class="cards">
              <article class="card">
                <h3>Authentication required</h3>
                <p>Please login to access your dashboard.</p>
                <a class="btn" href="#/login">Go to Login</a>
              </article>
            </section>
          `,
            {
                heroTitle: 'Protected Area',
                heroText: 'Your session is missing or expired.',
            },
        );
    }

    const content = `
      <section class="cards">
        <article class="card">
          <h3>Profile</h3>
          <p><strong>Name:</strong> ${state.user.name}</p>
          <p><strong>Email:</strong> ${state.user.email}</p>
          <p><strong>Email Verified:</strong> ${state.user.email_verified ? 'Yes' : 'No'}</p>
          <p><strong>Active:</strong> ${state.user.is_active ? 'Yes' : 'No'}</p>
        </article>
        <article class="card">
          <h3>Quick Actions</h3>
          <button class="btn" id="logout-button" type="button">Logout</button>
          <a class="btn secondary" href="#/forgot-password">Forgot Password</a>
        </article>
      </section>
    `;

    return withShell(content, {
        heroTitle: `Hello, ${state.user.name}`,
        heroText: 'You are authenticated and connected to your API session.',
    });
}

async function handleLoginSubmit(event) {
    event.preventDefault();
    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());

    try {
        await api('/api/login', {
            method: 'POST',
            body: JSON.stringify(payload),
        });
        state.pendingOtpEmail = String(payload.email || '');
        setFlash('success', 'OTP sent successfully. Check your email.');
        navigate('#/verify-otp');
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleVerifyOtpSubmit(event) {
    event.preventDefault();
    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());

    try {
        await api('/api/login/verify-otp', {
            method: 'POST',
            body: JSON.stringify(payload),
        });
        await loadMe();
        setFlash('success', 'Logged in successfully.');
        navigate('#/dashboard');
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleRegisterSubmit(event) {
    event.preventDefault();
    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());

    try {
        await api('/api/users', {
            method: 'POST',
            body: JSON.stringify(payload),
        });
        setFlash('success', 'Account created. Please check your email verification link.');
        navigate('#/login');
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleForgotSubmit(event) {
    event.preventDefault();
    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());

    try {
        await api('/api/password/forgot', {
            method: 'POST',
            body: JSON.stringify(payload),
        });
        setFlash('success', 'If the email exists, a reset link has been sent.');
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleLogout(event) {
    if (event) {
        event.preventDefault();
    }

    try {
        await api('/api/logout', { method: 'POST' });
    } catch {
        // Ignore logout error and clear local user state anyway.
    }

    state.user = null;
    setFlash('success', 'You are logged out.');
    navigate('#/login');
}

function bindPageEvents() {
    const loginForm = document.querySelector('#login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLoginSubmit);
    }

    const verifyForm = document.querySelector('#verify-otp-form');
    if (verifyForm) {
        verifyForm.addEventListener('submit', handleVerifyOtpSubmit);
    }

    const registerForm = document.querySelector('#register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegisterSubmit);
    }

    const forgotForm = document.querySelector('#forgot-form');
    if (forgotForm) {
        forgotForm.addEventListener('submit', handleForgotSubmit);
    }

    const logoutButton = document.querySelector('#logout-button');
    if (logoutButton) {
        logoutButton.addEventListener('click', handleLogout);
    }

    const logoutLink = document.querySelector('#logout-link');
    if (logoutLink) {
        logoutLink.addEventListener('click', handleLogout);
    }
}

function render() {
    const hash = window.location.hash || '#/';
    const page = routes[hash] || renderHome;
    app.innerHTML = page();
    bindPageEvents();
}

window.addEventListener('hashchange', render);

(async () => {
    await loadMe();
    if (!window.location.hash) {
        window.location.hash = '#/';
    }
    render();
})();
