import './bootstrap';
import { applyListView as buildListView } from './lib/list-utils';

const state = {
    user: null,
    isAdmin: false,
    flash: null,
    pendingOtpEmail: '',
    users: [],
    usersLoaded: false,
    usersLoading: false,
    selectedUser: null,
    admins: [],
    adminsLoaded: false,
    adminsLoading: false,
    organizations: [],
    organizationsLoaded: false,
    organizationsLoading: false,
    organizationsError: null,
    selectedOrganization: null,
    organizationMembers: [],
    membersLoading: false,
    organizationInvitations: [],
    invitationsLoading: false,
    projects: [],
    projectsLoaded: false,
    projectsLoading: false,
    selectedProject: null,
    projectLabels: [],
    labelsLoading: false,
    projectTickets: [],
    ticketsLoading: false,
    selectedTicket: null,
    ticketComments: [],
    commentsLoading: false,
    ticketLabels: [],
    ticketLabelsLoading: false,
    sessions: [],
    sessionsLoaded: false,
    sessionsLoading: false,
    selectedSession: null,
    auditLogs: [],
    auditLogsLoaded: false,
    auditLogsLoading: false,
    selectedAuditLog: null,
    ui: {
        users: { search: '', page: 1, perPage: 8 },
        admins: { search: '', page: 1, perPage: 6 },
        organizations: { search: '', page: 1, perPage: 6 },
        projects: { search: '', page: 1, perPage: 6 },
        tickets: { search: '', page: 1, perPage: 6 },
        sessions: { search: '', page: 1, perPage: 6 },
        auditLogs: { search: '', page: 1, perPage: 6 },
    },
};

const routes = {
    '#/': renderHome,
    '#/login': renderLogin,
    '#/verify-otp': renderVerifyOtp,
    '#/register': renderRegister,
    '#/forgot-password': renderForgotPassword,
    '#/reset-password': renderResetPassword,
    '#/dashboard': renderDashboard,
    '#/users': renderUsers,
    '#/admins': renderAdmins,
    '#/organizations': renderOrganizations,
    '#/projects': renderProjects,
    '#/sessions': renderSessions,
    '#/audit-logs': renderAuditLogs,
};

const app = document.querySelector('#app');
const requestCache = new Map();
const CACHE_TTL_MS = 20000;

function navigate(hash) {
    if (window.location.hash !== hash) {
        window.location.hash = hash;
    } else {
        render();
    }
}

function currentHashPath() {
    const hash = window.location.hash || '#/';
    const queryIndex = hash.indexOf('?');

    return queryIndex === -1 ? hash : hash.slice(0, queryIndex);
}

function getHashParams() {
    const hash = window.location.hash || '';
    const queryIndex = hash.indexOf('?');
    if (queryIndex === -1) {
        return new URLSearchParams();
    }

    return new URLSearchParams(hash.slice(queryIndex + 1));
}

function setFlash(type, message) {
    state.flash = { type, message };
    render();
}

function invalidateCache(prefixes = ['/api']) {
    for (const key of requestCache.keys()) {
        if (prefixes.some((prefix) => key.startsWith(prefix))) {
            requestCache.delete(key);
        }
    }
}

function getListState(listKey) {
    return state.ui[listKey] ?? { search: '', page: 1, perPage: 6 };
}

function setListSearch(listKey, search) {
    if (!state.ui[listKey]) {
        return;
    }

    state.ui[listKey].search = String(search || '');
    state.ui[listKey].page = 1;
    render();
}

function setListPerPage(listKey, perPageRaw) {
    if (!state.ui[listKey]) {
        return;
    }

    const perPage = Number.parseInt(String(perPageRaw), 10);
    state.ui[listKey].perPage = Number.isFinite(perPage) && perPage > 0 ? perPage : state.ui[listKey].perPage;
    state.ui[listKey].page = 1;
    render();
}

function updateListPage(listKey, direction) {
    if (!state.ui[listKey]) {
        return;
    }

    state.ui[listKey].page = Math.max(1, state.ui[listKey].page + direction);
    render();
}

function applyListView(items, listKey, fields) {
    const list = Array.isArray(items) ? items : [];
    const listState = getListState(listKey);
    const view = buildListView(list, listState, fields);
    const page = view.page;
    if (page !== listState.page) {
        state.ui[listKey].page = page;
    }
    return view;
}

async function apiGet(path, { force = false, ttlMs = CACHE_TTL_MS } = {}) {
    const cached = requestCache.get(path);
    if (!force && cached && Date.now() - cached.timestamp < ttlMs) {
        return cached.body;
    }

    const body = await api(path, { method: 'GET' });
    requestCache.set(path, { body, timestamp: Date.now() });
    return body;
}

function resetUserAdminCollections() {
    state.users = [];
    state.usersLoaded = false;
    state.usersLoading = false;
    state.selectedUser = null;
    state.admins = [];
    state.adminsLoaded = false;
    state.adminsLoading = false;
    state.organizations = [];
    state.organizationsLoaded = false;
    state.organizationsLoading = false;
    state.organizationsError = null;
    state.selectedOrganization = null;
    state.organizationMembers = [];
    state.membersLoading = false;
    state.organizationInvitations = [];
    state.invitationsLoading = false;
    state.projects = [];
    state.projectsLoaded = false;
    state.projectsLoading = false;
    state.selectedProject = null;
    state.projectLabels = [];
    state.labelsLoading = false;
    state.projectTickets = [];
    state.ticketsLoading = false;
    state.selectedTicket = null;
    state.ticketComments = [];
    state.commentsLoading = false;
    state.ticketLabels = [];
    state.ticketLabelsLoading = false;
    state.sessions = [];
    state.sessionsLoaded = false;
    state.sessionsLoading = false;
    state.selectedSession = null;
    state.auditLogs = [];
    state.auditLogsLoaded = false;
    state.auditLogsLoading = false;
    state.selectedAuditLog = null;
    state.ui.users.page = 1;
    state.ui.admins.page = 1;
    state.ui.organizations.page = 1;
    state.ui.projects.page = 1;
    state.ui.tickets.page = 1;
    state.ui.sessions.page = 1;
    state.ui.auditLogs.page = 1;
}

async function api(path, options = {}) {
    const response = await fetch(path, {
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            ...(options.headers || {}),
        },
        ...options,
    });

    let body;

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

    const method = String(options.method || 'GET').toUpperCase();
    if (method !== 'GET') {
        invalidateCache();
    }

    return body;
}

async function loadMe() {
    try {
        const res = await api('/api/me', { method: 'GET' });
        state.user = res.data ?? null;
        state.isAdmin = Boolean(res.is_admin);
    } catch {
        state.user = null;
        state.isAdmin = false;
    }
}

async function fetchUsers(force = false) {
    if (!state.user || (state.usersLoaded && !force) || state.usersLoading) {
        return;
    }

    state.usersLoading = true;
    render();

    try {
        const res = await apiGet('/api/users?per_page=50', { force });
        state.users = Array.isArray(res?.data) ? res.data : [];
        state.usersLoaded = true;

        if (!state.selectedUser && state.users.length > 0) {
            state.selectedUser = state.users[0];
        }
    } catch (error) {
        setFlash('error', error.message);
    } finally {
        state.usersLoading = false;
        render();
    }
}

async function fetchUserById(id) {
    if (!id) {
        return;
    }

    try {
        const res = await apiGet(`/api/users/${id}`);
        state.selectedUser = res.data ?? null;
        render();
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function fetchAdmins(force = false) {
    if (!state.user || !state.isAdmin || (state.adminsLoaded && !force) || state.adminsLoading) {
        return;
    }

    state.adminsLoading = true;
    render();

    try {
        const res = await apiGet('/api/admins?per_page=50', { force });
        state.admins = Array.isArray(res?.data) ? res.data : [];
        state.adminsLoaded = true;
    } catch (error) {
        setFlash('error', error.message);
    } finally {
        state.adminsLoading = false;
        render();
    }
}

async function fetchOrganizations(force = false) {
    if (force) {
        state.organizationsError = null;
    }

    if (state.organizationsError && !force) {
        return;
    }

    if (!state.user || (state.organizationsLoaded && !force) || state.organizationsLoading) {
        return;
    }

    state.organizationsLoading = true;
    render();

    try {
        const res = await apiGet('/api/organizations?per_page=50', { force });
        state.organizations = Array.isArray(res?.data) ? res.data : [];
        state.organizationsLoaded = true;
        state.organizationsError = null;

        if (!state.selectedOrganization && state.organizations.length > 0) {
            state.selectedOrganization = state.organizations[0];
            await loadOrganizationDetails(state.selectedOrganization.id);
        }
    } catch (error) {
        state.organizationsError = error.message;
        setFlash('error', error.message);
    } finally {
        state.organizationsLoading = false;
        render();
    }
}

async function loadOrganizationDetails(organizationId, force = false) {
    if (!organizationId) {
        return;
    }

    state.membersLoading = true;
    state.invitationsLoading = true;
    render();

    try {
        const [orgRes, membersRes, invitationsRes] = await Promise.all([
            apiGet(`/api/organizations/${organizationId}`, { force }),
            apiGet(`/api/organizations/${organizationId}/members?per_page=50`, { force }),
            apiGet(`/api/organizations/${organizationId}/invitations?per_page=50`, { force }),
        ]);

        state.selectedOrganization = orgRes.data ?? null;
        state.organizationMembers = Array.isArray(membersRes?.data) ? membersRes.data : [];
        state.organizationInvitations = Array.isArray(invitationsRes?.data) ? invitationsRes.data : [];
    } catch (error) {
        setFlash('error', error.message);
    } finally {
        state.membersLoading = false;
        state.invitationsLoading = false;
        render();
    }
}

async function fetchProjects(force = false) {
    if (!state.user) {
        return;
    }

    if (!state.organizationsLoaded) {
        await fetchOrganizations();
    }

    if (!state.selectedOrganization && state.organizations.length > 0) {
        state.selectedOrganization = state.organizations[0];
    }

    const organizationId = state.selectedOrganization?.id;
    if (!organizationId) {
        return;
    }

    if (state.projectsLoaded && !force) {
        return;
    }

    state.projectsLoading = true;
    render();

    try {
        const projectsRes = await apiGet(`/api/organizations/${organizationId}/projects?per_page=50`, { force });
        state.projects = Array.isArray(projectsRes?.data) ? projectsRes.data : [];
        state.projectsLoaded = true;

        const existingSelected = state.projects.find((project) => project.id === state.selectedProject?.id) ?? null;
        state.selectedProject = existingSelected ?? state.projects[0] ?? null;

        if (state.selectedProject) {
            await loadProjectWorkspace(state.selectedProject.id);
        } else {
            state.projectLabels = [];
            state.projectTickets = [];
            state.selectedTicket = null;
            state.ticketComments = [];
            state.ticketLabels = [];
        }
    } catch (error) {
        setFlash('error', error.message);
    } finally {
        state.projectsLoading = false;
        render();
    }
}

async function loadProjectWorkspace(projectId, force = false) {
    const organizationId = state.selectedOrganization?.id;
    if (!organizationId || !projectId) {
        return;
    }

    state.labelsLoading = true;
    state.ticketsLoading = true;
    render();

    try {
        const [projectRes, labelsRes, ticketsRes] = await Promise.all([
            apiGet(`/api/organizations/${organizationId}/projects/${projectId}`, { force }),
            apiGet(`/api/organizations/${organizationId}/projects/${projectId}/labels?per_page=50`, { force }),
            apiGet(`/api/organizations/${organizationId}/projects/${projectId}/tickets?per_page=50`, { force }),
        ]);

        state.selectedProject = projectRes.data ?? null;
        state.projectLabels = Array.isArray(labelsRes?.data) ? labelsRes.data : [];
        state.projectTickets = Array.isArray(ticketsRes?.data) ? ticketsRes.data : [];

        if (state.projectTickets.length > 0 && !state.selectedTicket) {
            state.selectedTicket = state.projectTickets[0];
            await loadTicketWorkspace(state.selectedTicket.id);
        } else if (state.projectTickets.length === 0) {
            state.selectedTicket = null;
            state.ticketComments = [];
            state.ticketLabels = [];
        }
    } catch (error) {
        setFlash('error', error.message);
    } finally {
        state.labelsLoading = false;
        state.ticketsLoading = false;
        render();
    }
}

async function loadTicketWorkspace(ticketId, force = false) {
    const organizationId = state.selectedOrganization?.id;
    const projectId = state.selectedProject?.id;
    if (!organizationId || !projectId || !ticketId) {
        return;
    }

    state.commentsLoading = true;
    state.ticketLabelsLoading = true;
    render();

    try {
        const [ticketRes, commentsRes, ticketLabelsRes] = await Promise.all([
            apiGet(`/api/organizations/${organizationId}/projects/${projectId}/tickets/${ticketId}`, { force }),
            apiGet(`/api/organizations/${organizationId}/projects/${projectId}/tickets/${ticketId}/comments?per_page=50`, { force }),
            apiGet(`/api/organizations/${organizationId}/projects/${projectId}/tickets/${ticketId}/labels?per_page=50`, { force }),
        ]);

        state.selectedTicket = ticketRes.data ?? null;
        state.ticketComments = Array.isArray(commentsRes?.data) ? commentsRes.data : [];
        state.ticketLabels = Array.isArray(ticketLabelsRes?.data) ? ticketLabelsRes.data : [];
    } catch (error) {
        setFlash('error', error.message);
    } finally {
        state.commentsLoading = false;
        state.ticketLabelsLoading = false;
        render();
    }
}

async function fetchSessions(force = false) {
    if (!state.user || (state.sessionsLoaded && !force) || state.sessionsLoading) {
        return;
    }

    state.sessionsLoading = true;
    render();

    try {
        const res = await apiGet('/api/sessions?per_page=50', { force });
        state.sessions = Array.isArray(res?.data) ? res.data : [];
        state.sessionsLoaded = true;

        const existing = state.sessions.find((session) => session.id === state.selectedSession?.id) ?? null;
        state.selectedSession = existing ?? state.sessions[0] ?? null;
    } catch (error) {
        setFlash('error', error.message);
    } finally {
        state.sessionsLoading = false;
        render();
    }
}

async function fetchSessionById(sessionId) {
    if (!sessionId) {
        return;
    }

    try {
        const res = await apiGet(`/api/sessions/${sessionId}`);
        state.selectedSession = res.data ?? null;
        render();
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function fetchAuditLogs(force = false) {
    if (!state.user || !state.isAdmin || (state.auditLogsLoaded && !force) || state.auditLogsLoading) {
        return;
    }

    state.auditLogsLoading = true;
    render();

    try {
        const res = await apiGet('/api/audit-logs?per_page=50', { force });
        state.auditLogs = Array.isArray(res?.data) ? res.data : [];
        state.auditLogsLoaded = true;

        const existing = state.auditLogs.find((log) => log.id === state.selectedAuditLog?.id) ?? null;
        state.selectedAuditLog = existing ?? state.auditLogs[0] ?? null;
    } catch (error) {
        setFlash('error', error.message);
    } finally {
        state.auditLogsLoading = false;
        render();
    }
}

async function fetchAuditLogById(auditLogId) {
    if (!auditLogId || !state.isAdmin) {
        return;
    }

    try {
        const res = await apiGet(`/api/audit-logs/${auditLogId}`);
        state.selectedAuditLog = res.data ?? null;
        render();
    } catch (error) {
        setFlash('error', error.message);
    }
}

function withShell(content, { heroTitle, heroText } = {}) {
    const path = currentHashPath();

    const navItems = state.user
        ? `
            <li><a href="#/dashboard" class="${path === '#/dashboard' ? 'active' : ''}">Dashboard</a></li>
            <li><a href="#/users" class="${path === '#/users' ? 'active' : ''}">Users</a></li>
            <li><a href="#/organizations" class="${path === '#/organizations' ? 'active' : ''}">Organizations</a></li>
            <li><a href="#/projects" class="${path === '#/projects' ? 'active' : ''}">Projects</a></li>
            <li><a href="#/sessions" class="${path === '#/sessions' ? 'active' : ''}">Sessions</a></li>
            ${state.isAdmin ? `<li><a href="#/audit-logs" class="${path === '#/audit-logs' ? 'active' : ''}">Audit Logs</a></li>` : ''}
            ${state.isAdmin ? `<li><a href="#/admins" class="${path === '#/admins' ? 'active' : ''}">Admins</a></li>` : ''}
            <li><a href="#/login" id="logout-link">Logout</a></li>
          `
        : `
            <li><a href="#/login" class="${path === '#/login' ? 'active' : ''}">Login</a></li>
            <li><a href="#/register" class="${path === '#/register' ? 'active' : ''}">Register</a></li>
            <li><a href="#/forgot-password" class="${path === '#/forgot-password' ? 'active' : ''}">Forgot Password</a></li>
          `;

    return `
      <div class="app-root">
        <a class="skip-link" href="#main-content">Skip to content</a>
        <nav class="modern-nav" aria-label="Primary navigation">
          <div class="brand">Agilify</div>
          <ul>${navItems}</ul>
        </nav>
        <section class="hero">
          <h2>${heroTitle}</h2>
          <p>${heroText}</p>
        </section>
        <main class="layout" id="main-content" tabindex="-1">
          ${state.flash ? `<div class="alert ${state.flash.type}" role="status" aria-live="polite">${state.flash.message}</div>` : ''}
          ${content}
        </main>
        <footer class="modern-footer">Agilify SPA - Phase 3 in progress</footer>
      </div>
    `;
}

function renderProtectedMessage() {
    return `
      <section class="cards">
        <article class="card">
          <h3>Authentication required</h3>
          <p>Please login to access this section.</p>
          <a class="btn" href="#/login">Go to Login</a>
        </article>
      </section>
    `;
}

function renderHome() {
    const content = `
      <section class="cards">
        <article class="card">
          <h3>Secure Authentication</h3>
          <p>Login uses OTP verification by email for stronger account security.</p>
        </article>
        <article class="card">
          <h3>Password Recovery</h3>
          <p>Users can request a reset link and set a new password from SPA.</p>
        </article>
        <article class="card">
          <h3>Responsive Experience</h3>
          <p>Mobile-first layout with your gradient + glassmorphism style.</p>
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
          <p class="muted">After this step, a 6-digit OTP is sent to your email.</p>
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
        heroText: 'Complete your login with your one-time code.',
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

function renderResetPassword() {
    const params = getHashParams();
    const email = params.get('email') || '';
    const token = params.get('token') || '';

    const content = `
      <section class="cards">
        <article class="card">
          <h3>Set New Password</h3>
          <form id="reset-password-form">
            <div class="field">
              <label for="reset-email">Email</label>
              <input class="input" id="reset-email" type="email" name="email" value="${email}" required>
            </div>
            <div class="field">
              <label for="reset-token">Reset token</label>
              <input class="input" id="reset-token" type="text" name="token" value="${token}" required>
            </div>
            <div class="field">
              <label for="reset-password">New password</label>
              <input class="input" id="reset-password" type="password" name="password" required>
            </div>
            <div class="field">
              <label for="reset-password-confirmation">Confirm password</label>
              <input class="input" id="reset-password-confirmation" type="password" name="password_confirmation" required>
            </div>
            <button class="btn" type="submit">Update password</button>
          </form>
          <p class="muted">Paste token manually if your email client removed URL parameters.</p>
        </article>
      </section>
    `;

    return withShell(content, {
        heroTitle: 'Password Reset',
        heroText: 'Set a new password with your reset token.',
    });
}

function renderDashboard() {
    if (!state.user) {
        return withShell(renderProtectedMessage(), {
            heroTitle: 'Protected Area',
            heroText: 'Your session is missing or expired.',
        });
    }

    const content = `
      <section class="cards">
        <article class="card">
          <h3>Profile</h3>
          <p><strong>Name:</strong> ${state.user.name}</p>
          <p><strong>Email:</strong> ${state.user.email}</p>
          <p><strong>Email Verified:</strong> ${state.user.email_verified ? 'Yes' : 'No'}</p>
          <p><strong>Active:</strong> ${state.user.is_active ? 'Yes' : 'No'}</p>
          <p><strong>Role:</strong> ${state.isAdmin ? 'Admin' : 'User'}</p>
        </article>
        <article class="card">
          <h3>Quick Actions</h3>
          <a class="btn" href="#/users">Manage Users</a>
          <a class="btn secondary" href="#/organizations">Manage Organizations</a>
          <a class="btn secondary" href="#/projects">Manage Projects</a>
          <a class="btn secondary" href="#/sessions">Manage Sessions</a>
          ${state.isAdmin ? '<a class="btn secondary" href="#/audit-logs">View Audit Logs</a>' : ''}
          ${state.isAdmin ? '<a class="btn secondary" href="#/admins">Manage Admins</a>' : ''}
          <a class="btn secondary" href="#/forgot-password">Forgot Password</a>
          <button class="btn secondary" id="logout-button" type="button">Logout</button>
        </article>
      </section>
    `;

    return withShell(content, {
        heroTitle: `Hello, ${state.user.name}`,
        heroText: 'You are authenticated and connected to your API session.',
    });
}

function renderUsers() {
    if (!state.user) {
        return withShell(renderProtectedMessage(), {
            heroTitle: 'Users',
            heroText: 'Login required.',
        });
    }

    const usersView = applyListView(state.users, 'users', ['name', 'email', 'id']);
    const userRows = usersView.items
        .map(
            (user) => `
        <div class="card" style="padding:16px;">
          <p><strong>${user.name}</strong></p>
          <p class="muted">${user.email}</p>
          <button class="btn secondary user-open-btn" data-user-id="${user.id}" type="button">Open</button>
        </div>`,
        )
        .join('');

    const selected = state.selectedUser;
    const selectedUserCard = selected
        ? `
          <article class="card">
            <h3>User Details</h3>
            <p><strong>ID:</strong> ${selected.id}</p>
            <p><strong>Name:</strong> ${selected.name}</p>
            <p><strong>Email:</strong> ${selected.email}</p>
            <p><strong>Active:</strong> ${selected.is_active ? 'Yes' : 'No'}</p>
            <p><strong>Deleted:</strong> ${selected.is_deleted ? 'Yes' : 'No'}</p>
          </article>

          <article class="card">
            <h3>Update Profile</h3>
            <form id="user-profile-form" data-user-id="${selected.id}">
              <div class="field">
                <label>Name</label>
                <input class="input" name="name" value="${selected.name ?? ''}">
              </div>
              <div class="field">
                <label>Email</label>
                <input class="input" type="email" name="email" value="${selected.email ?? ''}">
              </div>
              <button class="btn" type="submit">Save profile</button>
            </form>
          </article>

          <article class="card">
            <h3>Update Password</h3>
            <form id="user-password-form" data-user-id="${selected.id}">
              <div class="field">
                <label>New password</label>
                <input class="input" type="password" name="password" required>
              </div>
              <div class="field">
                <label>Confirm password</label>
                <input class="input" type="password" name="password_confirmation" required>
              </div>
              <button class="btn" type="submit">Update password</button>
            </form>
          </article>

          <article class="card">
            <h3>Admin Actions</h3>
            <form id="user-admin-form" data-user-id="${selected.id}">
              <div class="field">
                <label>Account active</label>
                <select class="input" name="is_active">
                  <option value="true" ${selected.is_active ? 'selected' : ''}>True</option>
                  <option value="false" ${!selected.is_active ? 'selected' : ''}>False</option>
                </select>
              </div>
              <button class="btn" type="submit">Update status</button>
            </form>
            <button class="btn secondary" id="user-delete-button" data-user-id="${selected.id}" type="button" style="margin-top:12px;">
              Delete user
            </button>
          </article>
        `
        : `
          <article class="card">
            <h3>Select a user</h3>
            <p>Open a user from the list to view and edit details.</p>
          </article>
        `;

    const content = `
      <section class="cards">
        <article class="card">
          <h3>Users List</h3>
          <div class="field">
            <label>Search users</label>
            <input class="input" data-list-search="users" value="${usersView.search}" placeholder="Search by name, email or id">
          </div>
          <div class="field">
            <label>Items per page</label>
            <select class="input" data-list-per-page="users">
              <option value="5" ${usersView.perPage === 5 ? 'selected' : ''}>5</option>
              <option value="8" ${usersView.perPage === 8 ? 'selected' : ''}>8</option>
              <option value="12" ${usersView.perPage === 12 ? 'selected' : ''}>12</option>
            </select>
          </div>
          <button class="btn secondary" id="users-refresh-btn" type="button">Refresh users</button>
          ${state.usersLoading ? '<p class="muted">Loading users...</p>' : ''}
          <div class="cards" style="padding: 16px 0 0;">
            ${userRows || '<p class="muted">No users loaded yet.</p>'}
          </div>
          <p class="muted">Page ${usersView.page} / ${usersView.totalPages} - ${usersView.total} result(s)</p>
          <div style="display:flex; gap:8px;">
            <button class="btn secondary" type="button" data-list-page-prev="users" ${usersView.page <= 1 ? 'disabled' : ''}>Previous</button>
            <button class="btn secondary" type="button" data-list-page-next="users" ${usersView.page >= usersView.totalPages ? 'disabled' : ''}>Next</button>
          </div>
        </article>
        ${selectedUserCard}
      </section>
    `;

    return withShell(content, {
        heroTitle: 'Users Management',
        heroText: 'List users and perform profile/password/admin actions.',
    });
}

function renderAdmins() {
    if (!state.user) {
        return withShell(renderProtectedMessage(), {
            heroTitle: 'Admins',
            heroText: 'Login required.',
        });
    }

    if (!state.isAdmin) {
        return withShell(
            `
          <section class="cards">
            <article class="card">
              <h3>Forbidden</h3>
              <p>You must be an admin to access this page.</p>
            </article>
          </section>
        `,
            {
                heroTitle: 'Admins',
                heroText: 'Insufficient permissions.',
            },
        );
    }

    const adminsView = applyListView(state.admins, 'admins', ['id', 'user.name', 'user.email']);
    const adminRows = adminsView.items
        .map((admin) => {
            const adminName = admin.user?.name ?? 'Unknown user';
            const adminEmail = admin.user?.email ?? '';
            return `
            <article class="card">
              <h3>${adminName}</h3>
              <p class="muted">${adminEmail}</p>
              <p><strong>Admin ID:</strong> ${admin.id}</p>
              <form class="admin-update-form" data-admin-id="${admin.id}">
                <div class="field">
                  <label>Admin active</label>
                  <select class="input" name="is_active">
                    <option value="true" ${admin.is_active ? 'selected' : ''}>True</option>
                    <option value="false" ${!admin.is_active ? 'selected' : ''}>False</option>
                  </select>
                </div>
                <button class="btn secondary" type="submit">Update</button>
              </form>
              <button class="btn secondary admin-delete-btn" data-admin-id="${admin.id}" type="button" style="margin-top:12px;">Delete</button>
            </article>
          `;
        })
        .join('');

    const content = `
      <section class="cards">
        <article class="card">
          <h3>Create Admin</h3>
          <form id="admin-create-form">
            <div class="field">
              <label>User ID</label>
              <input class="input" name="user_id" placeholder="UUID of user" required>
            </div>
            <div class="field">
              <label>Active</label>
              <select class="input" name="is_active">
                <option value="true" selected>True</option>
                <option value="false">False</option>
              </select>
            </div>
            <button class="btn" type="submit">Create admin</button>
          </form>
          <div class="field">
            <label>Search admins</label>
            <input class="input" data-list-search="admins" value="${adminsView.search}" placeholder="Search by id, name, email">
          </div>
          <div class="field">
            <label>Items per page</label>
            <select class="input" data-list-per-page="admins">
              <option value="4" ${adminsView.perPage === 4 ? 'selected' : ''}>4</option>
              <option value="6" ${adminsView.perPage === 6 ? 'selected' : ''}>6</option>
              <option value="10" ${adminsView.perPage === 10 ? 'selected' : ''}>10</option>
            </select>
          </div>
          <button class="btn secondary" id="admins-refresh-btn" type="button" style="margin-top:12px;">Refresh admins</button>
          ${state.adminsLoading ? '<p class="muted">Loading admins...</p>' : ''}
          <p class="muted">Page ${adminsView.page} / ${adminsView.totalPages} - ${adminsView.total} result(s)</p>
          <div style="display:flex; gap:8px;">
            <button class="btn secondary" type="button" data-list-page-prev="admins" ${adminsView.page <= 1 ? 'disabled' : ''}>Previous</button>
            <button class="btn secondary" type="button" data-list-page-next="admins" ${adminsView.page >= adminsView.totalPages ? 'disabled' : ''}>Next</button>
          </div>
        </article>
        ${adminRows || '<article class="card"><h3>No admins</h3><p>Add one using user ID.</p></article>'}
      </section>
    `;

    return withShell(content, {
        heroTitle: 'Admins Management',
        heroText: 'Create, update and delete admin assignments.',
    });
}

function renderOrganizations() {
    if (!state.user) {
        return withShell(renderProtectedMessage(), {
            heroTitle: 'Organizations',
            heroText: 'Login required.',
        });
    }

    const organizationsView = applyListView(state.organizations, 'organizations', ['name', 'slug', 'id', 'plan']);
    const organizationRows = organizationsView.items
        .map(
            (organization) => `
          <div class="card" style="padding:16px;">
            <p><strong>${organization.name}</strong></p>
            <p class="muted">${organization.slug}</p>
            <p class="muted">Plan: ${organization.plan ?? 'free'}</p>
            <button class="btn secondary organization-open-btn" data-organization-id="${organization.id}" type="button">Open</button>
          </div>
        `,
        )
        .join('');

    const organization = state.selectedOrganization;
    const organizationPanel = organization
        ? `
          <article class="card">
            <h3>Organization Details</h3>
            <p><strong>ID:</strong> ${organization.id}</p>
            <p><strong>Name:</strong> ${organization.name}</p>
            <p><strong>Slug:</strong> ${organization.slug}</p>
            <p><strong>Plan:</strong> ${organization.plan ?? 'free'}</p>
            <p><strong>Owner ID:</strong> ${organization.owner_id}</p>
          </article>

          <article class="card">
            <h3>Create Organization</h3>
            <form id="organization-create-form">
              <div class="field"><label>Name</label><input class="input" name="name" required></div>
              <div class="field"><label>Slug</label><input class="input" name="slug" required></div>
              <div class="field"><label>Owner ID</label><input class="input" name="owner_id" value="${state.user.id}" required></div>
              <div class="field">
                <label>Plan</label>
                <select class="input" name="plan">
                  <option value="free" selected>free</option>
                  <option value="pro">pro</option>
                  <option value="enterprise">enterprise</option>
                </select>
              </div>
              <button class="btn" type="submit">Create organization</button>
            </form>
          </article>

          <article class="card">
            <h3>Update Organization</h3>
            <form id="organization-update-form" data-organization-id="${organization.id}">
              <div class="field"><label>Name</label><input class="input" name="name" value="${organization.name ?? ''}"></div>
              <div class="field"><label>Slug</label><input class="input" name="slug" value="${organization.slug ?? ''}"></div>
              <div class="field"><label>Owner ID</label><input class="input" name="owner_id" value="${organization.owner_id ?? ''}"></div>
              <div class="field">
                <label>Plan</label>
                <select class="input" name="plan">
                  <option value="free" ${(organization.plan ?? 'free') === 'free' ? 'selected' : ''}>free</option>
                  <option value="pro" ${organization.plan === 'pro' ? 'selected' : ''}>pro</option>
                  <option value="enterprise" ${organization.plan === 'enterprise' ? 'selected' : ''}>enterprise</option>
                </select>
              </div>
              <button class="btn" type="submit">Update organization</button>
            </form>
            <form id="organization-plan-form" data-organization-id="${organization.id}" style="margin-top:12px;">
              <div class="field">
                <label>Update plan only</label>
                <select class="input" name="plan">
                  <option value="free" ${(organization.plan ?? 'free') === 'free' ? 'selected' : ''}>free</option>
                  <option value="pro" ${organization.plan === 'pro' ? 'selected' : ''}>pro</option>
                  <option value="enterprise" ${organization.plan === 'enterprise' ? 'selected' : ''}>enterprise</option>
                </select>
              </div>
              <button class="btn secondary" type="submit">Update plan</button>
            </form>
            <button class="btn secondary" id="organization-delete-button" data-organization-id="${organization.id}" type="button" style="margin-top:12px;">Delete organization</button>
          </article>

          <article class="card">
            <h3>Members</h3>
            <form id="member-create-form" data-organization-id="${organization.id}">
              <div class="field"><label>User ID</label><input class="input" name="user_id" required></div>
              <div class="field">
                <label>Role</label>
                <select class="input" name="role">
                  <option value="member" selected>member</option>
                  <option value="admin">admin</option>
                  <option value="owner">owner</option>
                </select>
              </div>
              <button class="btn" type="submit">Add member</button>
            </form>
            ${state.membersLoading ? '<p class="muted">Loading members...</p>' : ''}
            <div class="cards" style="padding: 16px 0 0;">
              ${
                  state.organizationMembers
                      .map(
                          (member) => `
                    <div class="card" style="padding:14px;">
                      <p><strong>${member.user?.name ?? member.user_id}</strong></p>
                      <p class="muted">${member.user?.email ?? ''}</p>
                      <form class="member-update-form" data-organization-id="${organization.id}" data-user-id="${member.user_id}">
                        <div class="field">
                          <label>Role</label>
                          <select class="input" name="role">
                            <option value="owner" ${member.role === 'owner' ? 'selected' : ''}>owner</option>
                            <option value="admin" ${member.role === 'admin' ? 'selected' : ''}>admin</option>
                            <option value="member" ${member.role === 'member' ? 'selected' : ''}>member</option>
                          </select>
                        </div>
                        <button class="btn secondary" type="submit">Update</button>
                      </form>
                      <button class="btn secondary member-delete-btn" data-organization-id="${organization.id}" data-user-id="${member.user_id}" type="button" style="margin-top:10px;">Remove</button>
                    </div>
                  `,
                      )
                      .join('') || '<p class="muted">No members.</p>'
              }
            </div>
          </article>

          <article class="card">
            <h3>Invitations</h3>
            <form id="invitation-create-form" data-organization-id="${organization.id}">
              <div class="field"><label>Email</label><input class="input" type="email" name="email" required></div>
              <div class="field">
                <label>Role</label>
                <select class="input" name="role">
                  <option value="member" selected>member</option>
                  <option value="admin">admin</option>
                </select>
              </div>
              <div class="field"><label>Expires At (optional)</label><input class="input" type="datetime-local" name="expires_at"></div>
              <button class="btn" type="submit">Create invitation</button>
            </form>
            ${state.invitationsLoading ? '<p class="muted">Loading invitations...</p>' : ''}
            <div class="cards" style="padding: 16px 0 0;">
              ${
                  state.organizationInvitations
                      .map(
                          (invitation) => `
                    <div class="card" style="padding:14px;">
                      <p><strong>${invitation.email}</strong></p>
                      <p class="muted">Role: ${invitation.role}</p>
                      <p class="muted">Token: ${invitation.token}</p>
                      <button class="btn secondary invitation-delete-btn" data-organization-id="${organization.id}" data-invitation-id="${invitation.id}" type="button">Delete</button>
                    </div>
                  `,
                      )
                      .join('') || '<p class="muted">No invitations.</p>'
              }
            </div>
          </article>

          <article class="card">
            <h3>Accept Invitation</h3>
            <form id="invitation-accept-form">
              <div class="field"><label>Invitation token</label><input class="input" name="token" required></div>
              <button class="btn" type="submit">Accept invitation</button>
            </form>
          </article>
        `
        : `
          <article class="card">
            <h3>Create Organization</h3>
            <form id="organization-create-form">
              <div class="field"><label>Name</label><input class="input" name="name" required></div>
              <div class="field"><label>Slug</label><input class="input" name="slug" required></div>
              <div class="field"><label>Owner ID</label><input class="input" name="owner_id" value="${state.user.id}" required></div>
              <div class="field">
                <label>Plan</label>
                <select class="input" name="plan">
                  <option value="free" selected>free</option>
                  <option value="pro">pro</option>
                  <option value="enterprise">enterprise</option>
                </select>
              </div>
              <button class="btn" type="submit">Create organization</button>
            </form>
          </article>
          <article class="card">
            <h3>Select an organization</h3>
            <p>Choose one from the list to manage members and invitations.</p>
          </article>
        `;

    const content = `
      <section class="cards">
        <article class="card">
          <h3>Organizations List</h3>
          ${state.organizationsError ? `<p class="muted">Auto-refresh paused after error: ${state.organizationsError}</p>` : ''}
          <div class="field">
            <label>Search organizations</label>
            <input class="input" data-list-search="organizations" value="${organizationsView.search}" placeholder="Search by name, slug, plan">
          </div>
          <div class="field">
            <label>Items per page</label>
            <select class="input" data-list-per-page="organizations">
              <option value="4" ${organizationsView.perPage === 4 ? 'selected' : ''}>4</option>
              <option value="6" ${organizationsView.perPage === 6 ? 'selected' : ''}>6</option>
              <option value="10" ${organizationsView.perPage === 10 ? 'selected' : ''}>10</option>
            </select>
          </div>
          <button class="btn secondary" id="organizations-refresh-btn" type="button">Refresh organizations</button>
          ${state.organizationsLoading ? '<p class="muted">Loading organizations...</p>' : ''}
          <div class="cards" style="padding: 16px 0 0;">
            ${organizationRows || '<p class="muted">No organizations yet.</p>'}
          </div>
          <p class="muted">Page ${organizationsView.page} / ${organizationsView.totalPages} - ${organizationsView.total} result(s)</p>
          <div style="display:flex; gap:8px;">
            <button class="btn secondary" type="button" data-list-page-prev="organizations" ${organizationsView.page <= 1 ? 'disabled' : ''}>Previous</button>
            <button class="btn secondary" type="button" data-list-page-next="organizations" ${organizationsView.page >= organizationsView.totalPages ? 'disabled' : ''}>Next</button>
          </div>
        </article>
        ${organizationPanel}
      </section>
    `;

    return withShell(content, {
        heroTitle: 'Organizations Management',
        heroText: 'Manage organizations, members, invitations and plans.',
    });
}

function renderProjects() {
    if (!state.user) {
        return withShell(renderProtectedMessage(), {
            heroTitle: 'Projects',
            heroText: 'Login required.',
        });
    }

    const organizationOptions = state.organizations
        .map(
            (organization) => `
            <option value="${organization.id}" ${state.selectedOrganization?.id === organization.id ? 'selected' : ''}>
              ${organization.name} (${organization.slug})
            </option>
        `,
        )
        .join('');

    const projectsView = applyListView(state.projects, 'projects', ['name', 'key', 'description', 'id']);
    const projectRows = projectsView.items
        .map(
            (project) => `
            <div class="card" style="padding:14px;">
              <p><strong>${project.name}</strong></p>
              <p class="muted">${project.key}</p>
              <button class="btn secondary project-open-btn" data-project-id="${project.id}" type="button">Open</button>
            </div>
        `,
        )
        .join('');

    const project = state.selectedProject;
    const ticket = state.selectedTicket;

    const labelsMarkup = state.projectLabels
        .map(
            (label) => `
            <div class="card" style="padding:14px;">
              <p><strong>${label.name}</strong></p>
              <p class="muted">Color: ${label.color ?? '-'}</p>
              <form class="label-update-form" data-label-id="${label.id}" style="margin-top:8px;">
                <div class="field"><label>Name</label><input class="input" name="name" value="${label.name ?? ''}" required></div>
                <div class="field"><label>Color</label><input class="input" name="color" value="${label.color ?? ''}"></div>
                <button class="btn secondary" type="submit">Update label</button>
              </form>
              <button class="btn secondary label-delete-btn" data-label-id="${label.id}" type="button" style="margin-top:10px;">Delete label</button>
            </div>
        `,
        )
        .join('');

    const ticketsView = applyListView(state.projectTickets, 'tickets', ['title', 'status', 'priority', 'type', 'id']);
    const ticketsMarkup = ticketsView.items
        .map(
            (item) => `
            <div class="card" style="padding:14px;">
              <p><strong>${item.title}</strong></p>
              <p class="muted">${item.status} / ${item.priority} / ${item.type}</p>
              <button class="btn secondary ticket-open-btn" data-ticket-id="${item.id}" type="button">Open ticket</button>
            </div>
        `,
        )
        .join('');

    const commentsMarkup = state.ticketComments
        .map(
            (comment) => `
            <div class="card" style="padding:14px;">
              <p><strong>${comment.author?.name ?? comment.author_id}</strong></p>
              <p>${comment.content}</p>
              <form class="comment-update-form" data-comment-id="${comment.id}" style="margin-top:8px;">
                <div class="field"><label>Content</label><textarea class="input" name="content" required>${comment.content ?? ''}</textarea></div>
                <button class="btn secondary" type="submit">Update comment</button>
              </form>
              <button class="btn secondary comment-delete-btn" data-comment-id="${comment.id}" type="button" style="margin-top:10px;">Delete comment</button>
            </div>
        `,
        )
        .join('');

    const ticketLabelsMarkup = state.ticketLabels
        .map(
            (item) => `
            <div class="card" style="padding:14px;">
              <p><strong>${item.label?.name ?? item.label_id}</strong></p>
              <p class="muted">${item.label?.color ?? '-'}</p>
              <button class="btn secondary ticket-label-delete-btn" data-label-id="${item.label_id}" type="button">Remove label</button>
            </div>
        `,
        )
        .join('');

    const content = `
      <section class="cards">
        <article class="card">
          <h3>Workspace Selector</h3>
          <div class="field">
            <label>Organization</label>
            <select class="input" id="projects-organization-select">
              ${organizationOptions || '<option value="">No organizations</option>'}
            </select>
          </div>
          <button class="btn secondary" id="projects-refresh-btn" type="button">Refresh workspace</button>
          ${state.projectsLoading ? '<p class="muted">Loading projects...</p>' : ''}
        </article>

        <article class="card">
          <h3>Projects</h3>
          <form id="project-create-form">
            <div class="field"><label>Name</label><input class="input" name="name" required></div>
            <div class="field"><label>Description</label><textarea class="input" name="description"></textarea></div>
            <div class="field"><label>Key</label><input class="input" name="key" required></div>
            <button class="btn" type="submit">Create project</button>
          </form>
          <div class="field">
            <label>Search projects</label>
            <input class="input" data-list-search="projects" value="${projectsView.search}" placeholder="Search by name, key, description">
          </div>
          <div class="field">
            <label>Items per page</label>
            <select class="input" data-list-per-page="projects">
              <option value="4" ${projectsView.perPage === 4 ? 'selected' : ''}>4</option>
              <option value="6" ${projectsView.perPage === 6 ? 'selected' : ''}>6</option>
              <option value="10" ${projectsView.perPage === 10 ? 'selected' : ''}>10</option>
            </select>
          </div>
          <div class="cards" style="padding:16px 0 0;">${projectRows || '<p class="muted">No projects.</p>'}</div>
          <p class="muted">Page ${projectsView.page} / ${projectsView.totalPages} - ${projectsView.total} result(s)</p>
          <div style="display:flex; gap:8px;">
            <button class="btn secondary" type="button" data-list-page-prev="projects" ${projectsView.page <= 1 ? 'disabled' : ''}>Previous</button>
            <button class="btn secondary" type="button" data-list-page-next="projects" ${projectsView.page >= projectsView.totalPages ? 'disabled' : ''}>Next</button>
          </div>
        </article>

        ${
            project
                ? `
          <article class="card">
            <h3>Selected Project</h3>
            <p><strong>${project.name}</strong> (${project.key})</p>
            <form id="project-update-form" data-project-id="${project.id}">
              <div class="field"><label>Name</label><input class="input" name="name" value="${project.name ?? ''}"></div>
              <div class="field"><label>Description</label><textarea class="input" name="description">${project.description ?? ''}</textarea></div>
              <div class="field"><label>Key</label><input class="input" name="key" value="${project.key ?? ''}"></div>
              <button class="btn secondary" type="submit">Update project</button>
            </form>
            <button class="btn secondary" id="project-delete-button" data-project-id="${project.id}" type="button" style="margin-top:12px;">Delete project</button>
          </article>

          <article class="card">
            <h3>Labels</h3>
            <form id="label-create-form">
              <div class="field"><label>Name</label><input class="input" name="name" required></div>
              <div class="field"><label>Color</label><input class="input" name="color" placeholder="#aabbcc"></div>
              <button class="btn" type="submit">Create label</button>
            </form>
            ${state.labelsLoading ? '<p class="muted">Loading labels...</p>' : ''}
            <div class="cards" style="padding:16px 0 0;">${labelsMarkup || '<p class="muted">No labels.</p>'}</div>
          </article>

          <article class="card">
            <h3>Tickets</h3>
            <form id="ticket-create-form">
              <div class="field"><label>Title</label><input class="input" name="title" required></div>
              <div class="field"><label>Description</label><textarea class="input" name="description"></textarea></div>
              <div class="field">
                <label>Status</label>
                <select class="input" name="status">
                  <option value="todo" selected>todo</option>
                  <option value="in_progress">in_progress</option>
                  <option value="review">review</option>
                  <option value="done">done</option>
                </select>
              </div>
              <div class="field">
                <label>Priority</label>
                <select class="input" name="priority">
                  <option value="low">low</option>
                  <option value="medium" selected>medium</option>
                  <option value="high">high</option>
                  <option value="critical">critical</option>
                </select>
              </div>
              <div class="field">
                <label>Type</label>
                <select class="input" name="type">
                  <option value="task" selected>task</option>
                  <option value="bug">bug</option>
                  <option value="story">story</option>
                </select>
              </div>
              <div class="field"><label>Assignee ID (optional)</label><input class="input" name="assignee_id"></div>
              <div class="field"><label>Due date (optional)</label><input class="input" type="date" name="due_date"></div>
              <button class="btn" type="submit">Create ticket</button>
            </form>
            <div class="field">
              <label>Search tickets</label>
              <input class="input" data-list-search="tickets" value="${ticketsView.search}" placeholder="Search by title, status, priority, type">
            </div>
            <div class="field">
              <label>Items per page</label>
              <select class="input" data-list-per-page="tickets">
                <option value="4" ${ticketsView.perPage === 4 ? 'selected' : ''}>4</option>
                <option value="6" ${ticketsView.perPage === 6 ? 'selected' : ''}>6</option>
                <option value="10" ${ticketsView.perPage === 10 ? 'selected' : ''}>10</option>
              </select>
            </div>
            ${state.ticketsLoading ? '<p class="muted">Loading tickets...</p>' : ''}
            <div class="cards" style="padding:16px 0 0;">${ticketsMarkup || '<p class="muted">No tickets.</p>'}</div>
            <p class="muted">Page ${ticketsView.page} / ${ticketsView.totalPages} - ${ticketsView.total} result(s)</p>
            <div style="display:flex; gap:8px;">
              <button class="btn secondary" type="button" data-list-page-prev="tickets" ${ticketsView.page <= 1 ? 'disabled' : ''}>Previous</button>
              <button class="btn secondary" type="button" data-list-page-next="tickets" ${ticketsView.page >= ticketsView.totalPages ? 'disabled' : ''}>Next</button>
            </div>
          </article>
        `
                : `
          <article class="card">
            <h3>Select a project</h3>
            <p>Create or open a project to manage labels and tickets.</p>
          </article>
        `
        }

        ${
            ticket
                ? `
          <article class="card">
            <h3>Selected Ticket</h3>
            <p><strong>${ticket.title}</strong></p>
            <form id="ticket-update-form" data-ticket-id="${ticket.id}">
              <div class="field"><label>Title</label><input class="input" name="title" value="${ticket.title ?? ''}"></div>
              <div class="field"><label>Description</label><textarea class="input" name="description">${ticket.description ?? ''}</textarea></div>
              <div class="field">
                <label>Status</label>
                <select class="input" name="status">
                  <option value="todo" ${ticket.status === 'todo' ? 'selected' : ''}>todo</option>
                  <option value="in_progress" ${ticket.status === 'in_progress' ? 'selected' : ''}>in_progress</option>
                  <option value="review" ${ticket.status === 'review' ? 'selected' : ''}>review</option>
                  <option value="done" ${ticket.status === 'done' ? 'selected' : ''}>done</option>
                </select>
              </div>
              <div class="field">
                <label>Priority</label>
                <select class="input" name="priority">
                  <option value="low" ${ticket.priority === 'low' ? 'selected' : ''}>low</option>
                  <option value="medium" ${ticket.priority === 'medium' ? 'selected' : ''}>medium</option>
                  <option value="high" ${ticket.priority === 'high' ? 'selected' : ''}>high</option>
                  <option value="critical" ${ticket.priority === 'critical' ? 'selected' : ''}>critical</option>
                </select>
              </div>
              <div class="field">
                <label>Type</label>
                <select class="input" name="type">
                  <option value="task" ${ticket.type === 'task' ? 'selected' : ''}>task</option>
                  <option value="bug" ${ticket.type === 'bug' ? 'selected' : ''}>bug</option>
                  <option value="story" ${ticket.type === 'story' ? 'selected' : ''}>story</option>
                </select>
              </div>
              <div class="field"><label>Assignee ID</label><input class="input" name="assignee_id" value="${ticket.assignee_id ?? ''}"></div>
              <div class="field"><label>Reporter ID</label><input class="input" name="reporter_id" value="${ticket.reporter_id ?? ''}"></div>
              <div class="field"><label>Due date</label><input class="input" type="date" name="due_date" value="${ticket.due_date ? String(ticket.due_date).slice(0, 10) : ''}"></div>
              <button class="btn secondary" type="submit">Update ticket</button>
            </form>
            <button class="btn secondary" id="ticket-delete-button" data-ticket-id="${ticket.id}" type="button" style="margin-top:12px;">Delete ticket</button>
          </article>

          <article class="card">
            <h3>Comments</h3>
            <form id="comment-create-form">
              <div class="field"><label>Content</label><textarea class="input" name="content" required></textarea></div>
              <button class="btn" type="submit">Create comment</button>
            </form>
            ${state.commentsLoading ? '<p class="muted">Loading comments...</p>' : ''}
            <div class="cards" style="padding:16px 0 0;">${commentsMarkup || '<p class="muted">No comments.</p>'}</div>
          </article>

          <article class="card">
            <h3>Ticket Labels</h3>
            <form id="ticket-label-create-form">
              <div class="field">
                <label>Label</label>
                <select class="input" name="label_id">
                  ${(state.projectLabels || [])
                      .map((label) => `<option value="${label.id}">${label.name}</option>`)
                      .join('')}
                </select>
              </div>
              <button class="btn" type="submit">Attach label</button>
            </form>
            ${state.ticketLabelsLoading ? '<p class="muted">Loading ticket labels...</p>' : ''}
            <div class="cards" style="padding:16px 0 0;">${ticketLabelsMarkup || '<p class="muted">No labels attached.</p>'}</div>
          </article>
        `
                : ''
        }
      </section>
    `;

    return withShell(content, {
        heroTitle: 'Projects Workspace',
        heroText: 'Manage projects, labels, tickets, comments and ticket labels.',
    });
}

function renderSessions() {
    if (!state.user) {
        return withShell(renderProtectedMessage(), {
            heroTitle: 'Sessions',
            heroText: 'Login required.',
        });
    }

    const sessionsView = applyListView(state.sessions, 'sessions', ['id', 'user.email', 'user_id', 'ip', 'agent']);
    const sessionRows = sessionsView.items
        .map(
            (session) => `
            <div class="card" style="padding:14px;">
              <p><strong>${session.user?.email ?? session.user_id}</strong></p>
              <p class="muted">${session.ip ?? '-'} | ${session.agent ?? '-'}</p>
              <p class="muted">Revoked: ${session.deleted_at ? 'Yes' : 'No'}</p>
              <button class="btn secondary session-open-btn" data-session-id="${session.id}" type="button">Open</button>
            </div>
        `,
        )
        .join('');

    const selected = state.selectedSession;

    const content = `
      <section class="cards">
        <article class="card">
          <h3>Sessions List</h3>
          <div class="field">
            <label>Search sessions</label>
            <input class="input" data-list-search="sessions" value="${sessionsView.search}" placeholder="Search by user, ip, agent">
          </div>
          <div class="field">
            <label>Items per page</label>
            <select class="input" data-list-per-page="sessions">
              <option value="4" ${sessionsView.perPage === 4 ? 'selected' : ''}>4</option>
              <option value="6" ${sessionsView.perPage === 6 ? 'selected' : ''}>6</option>
              <option value="10" ${sessionsView.perPage === 10 ? 'selected' : ''}>10</option>
            </select>
          </div>
          <button class="btn secondary" id="sessions-refresh-btn" type="button">Refresh sessions</button>
          ${state.sessionsLoading ? '<p class="muted">Loading sessions...</p>' : ''}
          <div class="cards" style="padding:16px 0 0;">
            ${sessionRows || '<p class="muted">No sessions found.</p>'}
          </div>
          <p class="muted">Page ${sessionsView.page} / ${sessionsView.totalPages} - ${sessionsView.total} result(s)</p>
          <div style="display:flex; gap:8px;">
            <button class="btn secondary" type="button" data-list-page-prev="sessions" ${sessionsView.page <= 1 ? 'disabled' : ''}>Previous</button>
            <button class="btn secondary" type="button" data-list-page-next="sessions" ${sessionsView.page >= sessionsView.totalPages ? 'disabled' : ''}>Next</button>
          </div>
        </article>

        <article class="card">
          <h3>Create Session (Manual)</h3>
          <form id="session-create-form">
            <div class="field"><label>User ID</label><input class="input" name="user_id" required></div>
            <div class="field"><label>Token</label><input class="input" name="token" minlength="16" required></div>
            <div class="field"><label>IP (optional)</label><input class="input" name="ip"></div>
            <div class="field"><label>Agent (optional)</label><input class="input" name="agent"></div>
            <button class="btn" type="submit">Create session</button>
          </form>
        </article>

        ${
            selected
                ? `
          <article class="card">
            <h3>Session Details</h3>
            <p><strong>ID:</strong> ${selected.id}</p>
            <p><strong>User:</strong> ${selected.user?.email ?? selected.user_id}</p>
            <p><strong>IP:</strong> ${selected.ip ?? '-'}</p>
            <p><strong>Agent:</strong> ${selected.agent ?? '-'}</p>
            <p><strong>Last used at:</strong> ${selected.last_used_at ?? '-'}</p>
            <p><strong>Created at:</strong> ${selected.created_at ?? '-'}</p>
            <p><strong>Revoked:</strong> ${selected.deleted_at ? 'Yes' : 'No'}</p>
            <button class="btn secondary" id="session-revoke-button" data-session-id="${selected.id}" type="button" ${selected.deleted_at ? 'disabled' : ''}>Revoke session</button>
          </article>
        `
                : `
          <article class="card">
            <h3>Select a session</h3>
            <p>Open one session from the list to inspect details.</p>
          </article>
        `
        }
      </section>
    `;

    return withShell(content, {
        heroTitle: 'Sessions Management',
        heroText: 'Inspect active/revoked sessions and revoke sessions when needed.',
    });
}

function renderAuditLogs() {
    if (!state.user || !state.isAdmin) {
        return withShell(renderProtectedMessage(), {
            heroTitle: 'Audit Logs',
            heroText: 'Admin access required.',
        });
    }

    const auditView = applyListView(state.auditLogs, 'auditLogs', [
        'id',
        'action',
        'entity_type',
        'entity_id',
        'performer.email',
        'performed_by',
    ]);
    const auditRows = auditView.items
        .map(
            (log) => `
            <div class="card" style="padding:14px;">
              <p><strong>${log.action}</strong></p>
              <p class="muted">${log.entity_type} / ${log.entity_id}</p>
              <p class="muted">By: ${log.performer?.email ?? log.performed_by ?? 'system'}</p>
              <button class="btn secondary audit-open-btn" data-audit-log-id="${log.id}" type="button">Open</button>
            </div>
        `,
        )
        .join('');

    const selected = state.selectedAuditLog;
    const selectedBefore = selected?.before ? JSON.stringify(selected.before, null, 2) : '{}';
    const selectedAfter = selected?.after ? JSON.stringify(selected.after, null, 2) : '{}';

    const content = `
      <section class="cards">
        <article class="card">
          <h3>Audit Logs</h3>
          <div class="field">
            <label>Search audit logs</label>
            <input class="input" data-list-search="auditLogs" value="${auditView.search}" placeholder="Search by action, entity, performer">
          </div>
          <div class="field">
            <label>Items per page</label>
            <select class="input" data-list-per-page="auditLogs">
              <option value="4" ${auditView.perPage === 4 ? 'selected' : ''}>4</option>
              <option value="6" ${auditView.perPage === 6 ? 'selected' : ''}>6</option>
              <option value="10" ${auditView.perPage === 10 ? 'selected' : ''}>10</option>
            </select>
          </div>
          <button class="btn secondary" id="audit-logs-refresh-btn" type="button">Refresh logs</button>
          ${state.auditLogsLoading ? '<p class="muted">Loading audit logs...</p>' : ''}
          <div class="cards" style="padding:16px 0 0;">
            ${auditRows || '<p class="muted">No audit logs found.</p>'}
          </div>
          <p class="muted">Page ${auditView.page} / ${auditView.totalPages} - ${auditView.total} result(s)</p>
          <div style="display:flex; gap:8px;">
            <button class="btn secondary" type="button" data-list-page-prev="auditLogs" ${auditView.page <= 1 ? 'disabled' : ''}>Previous</button>
            <button class="btn secondary" type="button" data-list-page-next="auditLogs" ${auditView.page >= auditView.totalPages ? 'disabled' : ''}>Next</button>
          </div>
        </article>

        ${
            selected
                ? `
          <article class="card">
            <h3>Audit Log Details</h3>
            <p><strong>ID:</strong> ${selected.id}</p>
            <p><strong>Action:</strong> ${selected.action}</p>
            <p><strong>Entity:</strong> ${selected.entity_type} (${selected.entity_id})</p>
            <p><strong>Performer:</strong> ${selected.performer?.email ?? selected.performed_by ?? 'system'}</p>
            <p><strong>IP Address:</strong> ${selected.ip_address ?? '-'}</p>
            <p><strong>Created at:</strong> ${selected.created_at ?? '-'}</p>
            <p><strong>Deleted:</strong> ${selected.is_deleted ? 'Yes' : 'No'}</p>
          </article>
          <article class="card">
            <h3>Before</h3>
            <pre style="white-space:pre-wrap; overflow:auto;">${selectedBefore}</pre>
          </article>
          <article class="card">
            <h3>After</h3>
            <pre style="white-space:pre-wrap; overflow:auto;">${selectedAfter}</pre>
          </article>
        `
                : `
          <article class="card">
            <h3>Select a log entry</h3>
            <p>Open one audit log to inspect full payload changes.</p>
          </article>
        `
        }
      </section>
    `;

    return withShell(content, {
        heroTitle: 'Audit Logs',
        heroText: 'Track security-sensitive and data mutation events.',
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
        resetUserAdminCollections();
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

async function handleResetPasswordSubmit(event) {
    event.preventDefault();
    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());

    try {
        await api('/api/password/reset', {
            method: 'POST',
            body: JSON.stringify(payload),
        });
        setFlash('success', 'Password updated successfully. You can now login.');
        navigate('#/login');
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleUserProfileSubmit(event) {
    event.preventDefault();
    const userId = event.currentTarget.dataset.userId;
    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());

    try {
        await api(`/api/users/${userId}/profile`, {
            method: 'PATCH',
            body: JSON.stringify(payload),
        });
        await fetchUsers(true);
        await fetchUserById(userId);
        setFlash('success', 'User profile updated successfully.');
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleUserPasswordSubmit(event) {
    event.preventDefault();
    const userId = event.currentTarget.dataset.userId;
    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());

    try {
        await api(`/api/users/${userId}/password`, {
            method: 'PATCH',
            body: JSON.stringify(payload),
        });
        setFlash('success', 'User password updated successfully.');
        event.currentTarget.reset();
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleUserAdminSubmit(event) {
    event.preventDefault();
    const userId = event.currentTarget.dataset.userId;
    const formData = new FormData(event.currentTarget);
    const payload = {
        is_active: String(formData.get('is_active')) === 'true',
    };

    try {
        await api(`/api/users/${userId}/admin`, {
            method: 'PATCH',
            body: JSON.stringify(payload),
        });
        await fetchUsers(true);
        await fetchUserById(userId);
        setFlash('success', 'User status updated successfully.');
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleUserDeleteClick(event) {
    event.preventDefault();
    const userId = event.currentTarget.dataset.userId;
    if (!window.confirm('Delete this user?')) {
        return;
    }

    const previousUsers = [...state.users];
    const previousSelectedUser = state.selectedUser;
    state.users = state.users.filter((user) => user.id !== userId);
    if (state.selectedUser?.id === userId) {
        state.selectedUser = null;
    }
    render();

    try {
        await api(`/api/users/${userId}`, { method: 'DELETE' });
        state.selectedUser = null;
        await fetchUsers(true);
        setFlash('success', 'User deleted successfully.');
    } catch (error) {
        state.users = previousUsers;
        state.selectedUser = previousSelectedUser;
        setFlash('error', error.message);
    }
}

async function handleAdminCreateSubmit(event) {
    event.preventDefault();
    const formData = new FormData(event.currentTarget);
    const payload = {
        user_id: String(formData.get('user_id') || ''),
        is_active: String(formData.get('is_active')) === 'true',
    };

    try {
        await api('/api/admins', {
            method: 'POST',
            body: JSON.stringify(payload),
        });
        await fetchAdmins(true);
        setFlash('success', 'Admin created successfully.');
        event.currentTarget.reset();
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleAdminUpdateSubmit(event) {
    event.preventDefault();
    const adminId = event.currentTarget.dataset.adminId;
    const formData = new FormData(event.currentTarget);
    const payload = {
        is_active: String(formData.get('is_active')) === 'true',
    };

    try {
        await api(`/api/admins/${adminId}`, {
            method: 'PATCH',
            body: JSON.stringify(payload),
        });
        await fetchAdmins(true);
        setFlash('success', 'Admin updated successfully.');
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleAdminDeleteClick(event) {
    event.preventDefault();
    const adminId = event.currentTarget.dataset.adminId;

    if (!window.confirm('Delete this admin assignment?')) {
        return;
    }

    try {
        await api(`/api/admins/${adminId}`, {
            method: 'DELETE',
        });
        await fetchAdmins(true);
        setFlash('success', 'Admin deleted successfully.');
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleOrganizationCreateSubmit(event) {
    event.preventDefault();
    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());

    try {
        await api('/api/organizations', {
            method: 'POST',
            body: JSON.stringify(payload),
        });
        await fetchOrganizations(true);
        setFlash('success', 'Organization created successfully.');
        event.currentTarget.reset();
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleOrganizationUpdateSubmit(event) {
    event.preventDefault();
    const organizationId = event.currentTarget.dataset.organizationId;
    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());

    try {
        await api(`/api/organizations/${organizationId}`, {
            method: 'PATCH',
            body: JSON.stringify(payload),
        });
        await fetchOrganizations(true);
        await loadOrganizationDetails(organizationId);
        setFlash('success', 'Organization updated successfully.');
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleOrganizationPlanSubmit(event) {
    event.preventDefault();
    const organizationId = event.currentTarget.dataset.organizationId;
    const formData = new FormData(event.currentTarget);
    const payload = {
        plan: String(formData.get('plan') || ''),
    };

    try {
        await api(`/api/organizations/${organizationId}/plan`, {
            method: 'PATCH',
            body: JSON.stringify(payload),
        });
        await fetchOrganizations(true);
        await loadOrganizationDetails(organizationId);
        setFlash('success', 'Organization plan updated successfully.');
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleOrganizationDeleteClick(event) {
    event.preventDefault();
    const organizationId = event.currentTarget.dataset.organizationId;
    if (!window.confirm('Delete this organization?')) {
        return;
    }

    const previousOrganizations = [...state.organizations];
    const previousSelectedOrganization = state.selectedOrganization;
    state.organizations = state.organizations.filter((organization) => organization.id !== organizationId);
    if (state.selectedOrganization?.id === organizationId) {
        state.selectedOrganization = null;
    }
    render();

    try {
        await api(`/api/organizations/${organizationId}`, {
            method: 'DELETE',
        });
        state.selectedOrganization = null;
        await fetchOrganizations(true);
        setFlash('success', 'Organization deleted successfully.');
    } catch (error) {
        state.organizations = previousOrganizations;
        state.selectedOrganization = previousSelectedOrganization;
        setFlash('error', error.message);
    }
}

async function handleMemberCreateSubmit(event) {
    event.preventDefault();
    const organizationId = event.currentTarget.dataset.organizationId;
    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());

    try {
        await api(`/api/organizations/${organizationId}/members`, {
            method: 'POST',
            body: JSON.stringify(payload),
        });
        await loadOrganizationDetails(organizationId);
        setFlash('success', 'Member added successfully.');
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleMemberUpdateSubmit(event) {
    event.preventDefault();
    const organizationId = event.currentTarget.dataset.organizationId;
    const userId = event.currentTarget.dataset.userId;
    const formData = new FormData(event.currentTarget);
    const payload = {
        role: String(formData.get('role') || ''),
    };

    try {
        await api(`/api/organizations/${organizationId}/members/${userId}`, {
            method: 'PATCH',
            body: JSON.stringify(payload),
        });
        await loadOrganizationDetails(organizationId);
        setFlash('success', 'Member role updated successfully.');
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleMemberDeleteClick(event) {
    event.preventDefault();
    const organizationId = event.currentTarget.dataset.organizationId;
    const userId = event.currentTarget.dataset.userId;
    if (!window.confirm('Remove this member?')) {
        return;
    }

    try {
        await api(`/api/organizations/${organizationId}/members/${userId}`, {
            method: 'DELETE',
        });
        await loadOrganizationDetails(organizationId);
        setFlash('success', 'Member removed successfully.');
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleInvitationCreateSubmit(event) {
    event.preventDefault();
    const organizationId = event.currentTarget.dataset.organizationId;
    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());
    if (!payload.expires_at) {
        delete payload.expires_at;
    }

    try {
        await api(`/api/organizations/${organizationId}/invitations`, {
            method: 'POST',
            body: JSON.stringify(payload),
        });
        await loadOrganizationDetails(organizationId);
        setFlash('success', 'Invitation created successfully.');
        event.currentTarget.reset();
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleInvitationDeleteClick(event) {
    event.preventDefault();
    const organizationId = event.currentTarget.dataset.organizationId;
    const invitationId = event.currentTarget.dataset.invitationId;
    if (!window.confirm('Delete this invitation?')) {
        return;
    }

    try {
        await api(`/api/organizations/${organizationId}/invitations/${invitationId}`, {
            method: 'DELETE',
        });
        await loadOrganizationDetails(organizationId);
        setFlash('success', 'Invitation deleted successfully.');
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleInvitationAcceptSubmit(event) {
    event.preventDefault();
    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());

    try {
        await api('/api/invitations/accept', {
            method: 'POST',
            body: JSON.stringify(payload),
        });
        setFlash('success', 'Invitation accepted successfully.');
        event.currentTarget.reset();
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleProjectOrganizationChange(event) {
    const organizationId = event.currentTarget.value;
    const organization = state.organizations.find((item) => item.id === organizationId) ?? null;
    state.selectedOrganization = organization;
    state.projects = [];
    state.projectsLoaded = false;
    state.selectedProject = null;
    state.projectLabels = [];
    state.projectTickets = [];
    state.selectedTicket = null;
    state.ticketComments = [];
    state.ticketLabels = [];
    await fetchProjects(true);
}

async function handleProjectCreateSubmit(event) {
    event.preventDefault();
    const organizationId = state.selectedOrganization?.id;
    if (!organizationId) {
        setFlash('error', 'Please select an organization first.');
        return;
    }

    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());

    try {
        await api(`/api/organizations/${organizationId}/projects`, {
            method: 'POST',
            body: JSON.stringify(payload),
        });
        await fetchProjects(true);
        setFlash('success', 'Project created successfully.');
        event.currentTarget.reset();
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleProjectUpdateSubmit(event) {
    event.preventDefault();
    const organizationId = state.selectedOrganization?.id;
    const projectId = event.currentTarget.dataset.projectId;
    if (!organizationId || !projectId) {
        return;
    }

    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());

    try {
        await api(`/api/organizations/${organizationId}/projects/${projectId}`, {
            method: 'PATCH',
            body: JSON.stringify(payload),
        });
        await fetchProjects(true);
        await loadProjectWorkspace(projectId);
        setFlash('success', 'Project updated successfully.');
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleProjectDeleteClick(event) {
    event.preventDefault();
    const organizationId = state.selectedOrganization?.id;
    const projectId = event.currentTarget.dataset.projectId;
    if (!organizationId || !projectId) {
        return;
    }

    if (!window.confirm('Delete this project?')) {
        return;
    }

    const previousProjects = [...state.projects];
    const previousSelectedProject = state.selectedProject;
    state.projects = state.projects.filter((project) => project.id !== projectId);
    if (state.selectedProject?.id === projectId) {
        state.selectedProject = null;
    }
    render();

    try {
        await api(`/api/organizations/${organizationId}/projects/${projectId}`, { method: 'DELETE' });
        state.selectedProject = null;
        state.selectedTicket = null;
        await fetchProjects(true);
        setFlash('success', 'Project deleted successfully.');
    } catch (error) {
        state.projects = previousProjects;
        state.selectedProject = previousSelectedProject;
        setFlash('error', error.message);
    }
}

async function handleLabelCreateSubmit(event) {
    event.preventDefault();
    const organizationId = state.selectedOrganization?.id;
    const projectId = state.selectedProject?.id;
    if (!organizationId || !projectId) {
        return;
    }

    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());

    try {
        await api(`/api/organizations/${organizationId}/projects/${projectId}/labels`, {
            method: 'POST',
            body: JSON.stringify(payload),
        });
        await loadProjectWorkspace(projectId);
        setFlash('success', 'Label created successfully.');
        event.currentTarget.reset();
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleLabelUpdateSubmit(event) {
    event.preventDefault();
    const organizationId = state.selectedOrganization?.id;
    const projectId = state.selectedProject?.id;
    const labelId = event.currentTarget.dataset.labelId;
    if (!organizationId || !projectId || !labelId) {
        return;
    }

    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());

    try {
        await api(`/api/organizations/${organizationId}/projects/${projectId}/labels/${labelId}`, {
            method: 'PATCH',
            body: JSON.stringify(payload),
        });
        await loadProjectWorkspace(projectId);
        setFlash('success', 'Label updated successfully.');
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleLabelDeleteClick(event) {
    event.preventDefault();
    const organizationId = state.selectedOrganization?.id;
    const projectId = state.selectedProject?.id;
    const labelId = event.currentTarget.dataset.labelId;
    if (!organizationId || !projectId || !labelId) {
        return;
    }

    if (!window.confirm('Delete this label?')) {
        return;
    }

    try {
        await api(`/api/organizations/${organizationId}/projects/${projectId}/labels/${labelId}`, {
            method: 'DELETE',
        });
        await loadProjectWorkspace(projectId);
        setFlash('success', 'Label deleted successfully.');
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleTicketCreateSubmit(event) {
    event.preventDefault();
    const organizationId = state.selectedOrganization?.id;
    const projectId = state.selectedProject?.id;
    if (!organizationId || !projectId) {
        return;
    }

    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());
    if (!payload.assignee_id) {
        delete payload.assignee_id;
    }
    if (!payload.due_date) {
        delete payload.due_date;
    }

    try {
        await api(`/api/organizations/${organizationId}/projects/${projectId}/tickets`, {
            method: 'POST',
            body: JSON.stringify(payload),
        });
        await loadProjectWorkspace(projectId);
        setFlash('success', 'Ticket created successfully.');
        event.currentTarget.reset();
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleTicketUpdateSubmit(event) {
    event.preventDefault();
    const organizationId = state.selectedOrganization?.id;
    const projectId = state.selectedProject?.id;
    const ticketId = event.currentTarget.dataset.ticketId;
    if (!organizationId || !projectId || !ticketId) {
        return;
    }

    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());
    if (!payload.assignee_id) {
        payload.assignee_id = null;
    }
    if (!payload.reporter_id) {
        payload.reporter_id = null;
    }
    if (!payload.due_date) {
        payload.due_date = null;
    }

    try {
        await api(`/api/organizations/${organizationId}/projects/${projectId}/tickets/${ticketId}`, {
            method: 'PATCH',
            body: JSON.stringify(payload),
        });
        await loadProjectWorkspace(projectId);
        await loadTicketWorkspace(ticketId);
        setFlash('success', 'Ticket updated successfully.');
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleTicketDeleteClick(event) {
    event.preventDefault();
    const organizationId = state.selectedOrganization?.id;
    const projectId = state.selectedProject?.id;
    const ticketId = event.currentTarget.dataset.ticketId;
    if (!organizationId || !projectId || !ticketId) {
        return;
    }

    if (!window.confirm('Delete this ticket?')) {
        return;
    }

    const previousTickets = [...state.projectTickets];
    const previousSelectedTicket = state.selectedTicket;
    state.projectTickets = state.projectTickets.filter((item) => item.id !== ticketId);
    if (state.selectedTicket?.id === ticketId) {
        state.selectedTicket = null;
    }
    render();

    try {
        await api(`/api/organizations/${organizationId}/projects/${projectId}/tickets/${ticketId}`, {
            method: 'DELETE',
        });
        state.selectedTicket = null;
        state.ticketComments = [];
        state.ticketLabels = [];
        await loadProjectWorkspace(projectId);
        setFlash('success', 'Ticket deleted successfully.');
    } catch (error) {
        state.projectTickets = previousTickets;
        state.selectedTicket = previousSelectedTicket;
        setFlash('error', error.message);
    }
}

async function handleCommentCreateSubmit(event) {
    event.preventDefault();
    const organizationId = state.selectedOrganization?.id;
    const projectId = state.selectedProject?.id;
    const ticketId = state.selectedTicket?.id;
    if (!organizationId || !projectId || !ticketId) {
        return;
    }

    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());

    try {
        await api(`/api/organizations/${organizationId}/projects/${projectId}/tickets/${ticketId}/comments`, {
            method: 'POST',
            body: JSON.stringify(payload),
        });
        await loadTicketWorkspace(ticketId);
        setFlash('success', 'Comment created successfully.');
        event.currentTarget.reset();
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleCommentUpdateSubmit(event) {
    event.preventDefault();
    const organizationId = state.selectedOrganization?.id;
    const projectId = state.selectedProject?.id;
    const ticketId = state.selectedTicket?.id;
    const commentId = event.currentTarget.dataset.commentId;
    if (!organizationId || !projectId || !ticketId || !commentId) {
        return;
    }

    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());

    try {
        await api(`/api/organizations/${organizationId}/projects/${projectId}/tickets/${ticketId}/comments/${commentId}`, {
            method: 'PATCH',
            body: JSON.stringify(payload),
        });
        await loadTicketWorkspace(ticketId);
        setFlash('success', 'Comment updated successfully.');
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleCommentDeleteClick(event) {
    event.preventDefault();
    const organizationId = state.selectedOrganization?.id;
    const projectId = state.selectedProject?.id;
    const ticketId = state.selectedTicket?.id;
    const commentId = event.currentTarget.dataset.commentId;
    if (!organizationId || !projectId || !ticketId || !commentId) {
        return;
    }

    if (!window.confirm('Delete this comment?')) {
        return;
    }

    try {
        await api(`/api/organizations/${organizationId}/projects/${projectId}/tickets/${ticketId}/comments/${commentId}`, {
            method: 'DELETE',
        });
        await loadTicketWorkspace(ticketId);
        setFlash('success', 'Comment deleted successfully.');
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleTicketLabelCreateSubmit(event) {
    event.preventDefault();
    const organizationId = state.selectedOrganization?.id;
    const projectId = state.selectedProject?.id;
    const ticketId = state.selectedTicket?.id;
    if (!organizationId || !projectId || !ticketId) {
        return;
    }

    const formData = new FormData(event.currentTarget);
    const payload = {
        label_id: String(formData.get('label_id') || ''),
    };

    try {
        await api(`/api/organizations/${organizationId}/projects/${projectId}/tickets/${ticketId}/labels`, {
            method: 'POST',
            body: JSON.stringify(payload),
        });
        await loadTicketWorkspace(ticketId);
        setFlash('success', 'Label attached to ticket successfully.');
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleTicketLabelDeleteClick(event) {
    event.preventDefault();
    const organizationId = state.selectedOrganization?.id;
    const projectId = state.selectedProject?.id;
    const ticketId = state.selectedTicket?.id;
    const labelId = event.currentTarget.dataset.labelId;
    if (!organizationId || !projectId || !ticketId || !labelId) {
        return;
    }

    try {
        await api(`/api/organizations/${organizationId}/projects/${projectId}/tickets/${ticketId}/labels/${labelId}`, {
            method: 'DELETE',
        });
        await loadTicketWorkspace(ticketId);
        setFlash('success', 'Label removed from ticket successfully.');
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleSessionCreateSubmit(event) {
    event.preventDefault();
    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());
    if (!payload.ip) {
        delete payload.ip;
    }
    if (!payload.agent) {
        delete payload.agent;
    }

    try {
        await api('/api/sessions', {
            method: 'POST',
            body: JSON.stringify(payload),
        });
        await fetchSessions(true);
        setFlash('success', 'Session created successfully.');
        event.currentTarget.reset();
    } catch (error) {
        setFlash('error', error.message);
    }
}

async function handleSessionRevokeClick(event) {
    event.preventDefault();
    const sessionId = event.currentTarget.dataset.sessionId;
    if (!sessionId) {
        return;
    }

    if (!window.confirm('Revoke this session?')) {
        return;
    }

    const previousSessions = [...state.sessions];
    const previousSelectedSession = state.selectedSession ? { ...state.selectedSession } : null;
    state.sessions = state.sessions.map((session) =>
        session.id === sessionId ? { ...session, deleted_at: session.deleted_at ?? new Date().toISOString() } : session,
    );
    if (state.selectedSession?.id === sessionId) {
        state.selectedSession = {
            ...state.selectedSession,
            deleted_at: state.selectedSession.deleted_at ?? new Date().toISOString(),
        };
    }
    render();

    try {
        await api(`/api/sessions/${sessionId}`, { method: 'DELETE' });
        await fetchSessions(true);
        await fetchSessionById(sessionId);
        setFlash('success', 'Session revoked successfully.');
    } catch (error) {
        state.sessions = previousSessions;
        state.selectedSession = previousSelectedSession;
        setFlash('error', error.message);
    }
}

function handleListSearchInput(event) {
    const listKey = event.currentTarget.dataset.listSearch;
    setListSearch(listKey, event.currentTarget.value);
}

function handleListPerPageChange(event) {
    const listKey = event.currentTarget.dataset.listPerPage;
    setListPerPage(listKey, event.currentTarget.value);
}

function handleListPrevPageClick(event) {
    const listKey = event.currentTarget.dataset.listPagePrev;
    updateListPage(listKey, -1);
}

function handleListNextPageClick(event) {
    const listKey = event.currentTarget.dataset.listPageNext;
    updateListPage(listKey, 1);
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
    state.isAdmin = false;
    invalidateCache();
    resetUserAdminCollections();
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

    const resetPasswordForm = document.querySelector('#reset-password-form');
    if (resetPasswordForm) {
        resetPasswordForm.addEventListener('submit', handleResetPasswordSubmit);
    }

    const userProfileForm = document.querySelector('#user-profile-form');
    if (userProfileForm) {
        userProfileForm.addEventListener('submit', handleUserProfileSubmit);
    }

    const userPasswordForm = document.querySelector('#user-password-form');
    if (userPasswordForm) {
        userPasswordForm.addEventListener('submit', handleUserPasswordSubmit);
    }

    const userAdminForm = document.querySelector('#user-admin-form');
    if (userAdminForm) {
        userAdminForm.addEventListener('submit', handleUserAdminSubmit);
    }

    const userDeleteButton = document.querySelector('#user-delete-button');
    if (userDeleteButton) {
        userDeleteButton.addEventListener('click', handleUserDeleteClick);
    }

    document.querySelectorAll('.user-open-btn').forEach((button) => {
        button.addEventListener('click', (event) => {
            fetchUserById(event.currentTarget.dataset.userId);
        });
    });

    const usersRefreshButton = document.querySelector('#users-refresh-btn');
    if (usersRefreshButton) {
        usersRefreshButton.addEventListener('click', () => {
            fetchUsers(true);
        });
    }

    const adminCreateForm = document.querySelector('#admin-create-form');
    if (adminCreateForm) {
        adminCreateForm.addEventListener('submit', handleAdminCreateSubmit);
    }

    document.querySelectorAll('.admin-update-form').forEach((form) => {
        form.addEventListener('submit', handleAdminUpdateSubmit);
    });

    document.querySelectorAll('.admin-delete-btn').forEach((button) => {
        button.addEventListener('click', handleAdminDeleteClick);
    });

    const adminsRefreshButton = document.querySelector('#admins-refresh-btn');
    if (adminsRefreshButton) {
        adminsRefreshButton.addEventListener('click', () => {
            fetchAdmins(true);
        });
    }

    const organizationCreateForm = document.querySelector('#organization-create-form');
    if (organizationCreateForm) {
        organizationCreateForm.addEventListener('submit', handleOrganizationCreateSubmit);
    }

    const organizationUpdateForm = document.querySelector('#organization-update-form');
    if (organizationUpdateForm) {
        organizationUpdateForm.addEventListener('submit', handleOrganizationUpdateSubmit);
    }

    const organizationPlanForm = document.querySelector('#organization-plan-form');
    if (organizationPlanForm) {
        organizationPlanForm.addEventListener('submit', handleOrganizationPlanSubmit);
    }

    const organizationDeleteButton = document.querySelector('#organization-delete-button');
    if (organizationDeleteButton) {
        organizationDeleteButton.addEventListener('click', handleOrganizationDeleteClick);
    }

    const memberCreateForm = document.querySelector('#member-create-form');
    if (memberCreateForm) {
        memberCreateForm.addEventListener('submit', handleMemberCreateSubmit);
    }

    document.querySelectorAll('.member-update-form').forEach((form) => {
        form.addEventListener('submit', handleMemberUpdateSubmit);
    });

    document.querySelectorAll('.member-delete-btn').forEach((button) => {
        button.addEventListener('click', handleMemberDeleteClick);
    });

    const invitationCreateForm = document.querySelector('#invitation-create-form');
    if (invitationCreateForm) {
        invitationCreateForm.addEventListener('submit', handleInvitationCreateSubmit);
    }

    document.querySelectorAll('.invitation-delete-btn').forEach((button) => {
        button.addEventListener('click', handleInvitationDeleteClick);
    });

    const invitationAcceptForm = document.querySelector('#invitation-accept-form');
    if (invitationAcceptForm) {
        invitationAcceptForm.addEventListener('submit', handleInvitationAcceptSubmit);
    }

    const organizationsRefreshButton = document.querySelector('#organizations-refresh-btn');
    if (organizationsRefreshButton) {
        organizationsRefreshButton.addEventListener('click', () => {
            fetchOrganizations(true);
        });
    }

    document.querySelectorAll('.organization-open-btn').forEach((button) => {
        button.addEventListener('click', (event) => {
            loadOrganizationDetails(event.currentTarget.dataset.organizationId);
        });
    });

    const projectsOrganizationSelect = document.querySelector('#projects-organization-select');
    if (projectsOrganizationSelect) {
        projectsOrganizationSelect.addEventListener('change', handleProjectOrganizationChange);
    }

    const projectsRefreshButton = document.querySelector('#projects-refresh-btn');
    if (projectsRefreshButton) {
        projectsRefreshButton.addEventListener('click', () => {
            fetchProjects(true);
        });
    }

    const projectCreateForm = document.querySelector('#project-create-form');
    if (projectCreateForm) {
        projectCreateForm.addEventListener('submit', handleProjectCreateSubmit);
    }

    const projectUpdateForm = document.querySelector('#project-update-form');
    if (projectUpdateForm) {
        projectUpdateForm.addEventListener('submit', handleProjectUpdateSubmit);
    }

    const projectDeleteButton = document.querySelector('#project-delete-button');
    if (projectDeleteButton) {
        projectDeleteButton.addEventListener('click', handleProjectDeleteClick);
    }

    document.querySelectorAll('.project-open-btn').forEach((button) => {
        button.addEventListener('click', (event) => {
            const projectId = event.currentTarget.dataset.projectId;
            state.selectedTicket = null;
            state.ticketComments = [];
            state.ticketLabels = [];
            loadProjectWorkspace(projectId);
        });
    });

    const labelCreateForm = document.querySelector('#label-create-form');
    if (labelCreateForm) {
        labelCreateForm.addEventListener('submit', handleLabelCreateSubmit);
    }

    document.querySelectorAll('.label-update-form').forEach((form) => {
        form.addEventListener('submit', handleLabelUpdateSubmit);
    });

    document.querySelectorAll('.label-delete-btn').forEach((button) => {
        button.addEventListener('click', handleLabelDeleteClick);
    });

    const ticketCreateForm = document.querySelector('#ticket-create-form');
    if (ticketCreateForm) {
        ticketCreateForm.addEventListener('submit', handleTicketCreateSubmit);
    }

    document.querySelectorAll('.ticket-open-btn').forEach((button) => {
        button.addEventListener('click', (event) => {
            loadTicketWorkspace(event.currentTarget.dataset.ticketId);
        });
    });

    const ticketUpdateForm = document.querySelector('#ticket-update-form');
    if (ticketUpdateForm) {
        ticketUpdateForm.addEventListener('submit', handleTicketUpdateSubmit);
    }

    const ticketDeleteButton = document.querySelector('#ticket-delete-button');
    if (ticketDeleteButton) {
        ticketDeleteButton.addEventListener('click', handleTicketDeleteClick);
    }

    const commentCreateForm = document.querySelector('#comment-create-form');
    if (commentCreateForm) {
        commentCreateForm.addEventListener('submit', handleCommentCreateSubmit);
    }

    document.querySelectorAll('.comment-update-form').forEach((form) => {
        form.addEventListener('submit', handleCommentUpdateSubmit);
    });

    document.querySelectorAll('.comment-delete-btn').forEach((button) => {
        button.addEventListener('click', handleCommentDeleteClick);
    });

    const ticketLabelCreateForm = document.querySelector('#ticket-label-create-form');
    if (ticketLabelCreateForm) {
        ticketLabelCreateForm.addEventListener('submit', handleTicketLabelCreateSubmit);
    }

    document.querySelectorAll('.ticket-label-delete-btn').forEach((button) => {
        button.addEventListener('click', handleTicketLabelDeleteClick);
    });

    const sessionCreateForm = document.querySelector('#session-create-form');
    if (sessionCreateForm) {
        sessionCreateForm.addEventListener('submit', handleSessionCreateSubmit);
    }

    const sessionsRefreshButton = document.querySelector('#sessions-refresh-btn');
    if (sessionsRefreshButton) {
        sessionsRefreshButton.addEventListener('click', () => {
            fetchSessions(true);
        });
    }

    document.querySelectorAll('.session-open-btn').forEach((button) => {
        button.addEventListener('click', (event) => {
            fetchSessionById(event.currentTarget.dataset.sessionId);
        });
    });

    const sessionRevokeButton = document.querySelector('#session-revoke-button');
    if (sessionRevokeButton) {
        sessionRevokeButton.addEventListener('click', handleSessionRevokeClick);
    }

    const auditLogsRefreshButton = document.querySelector('#audit-logs-refresh-btn');
    if (auditLogsRefreshButton) {
        auditLogsRefreshButton.addEventListener('click', () => {
            fetchAuditLogs(true);
        });
    }

    document.querySelectorAll('.audit-open-btn').forEach((button) => {
        button.addEventListener('click', (event) => {
            fetchAuditLogById(event.currentTarget.dataset.auditLogId);
        });
    });

    document.querySelectorAll('[data-list-search]').forEach((input) => {
        input.addEventListener('input', handleListSearchInput);
    });

    document.querySelectorAll('[data-list-per-page]').forEach((select) => {
        select.addEventListener('change', handleListPerPageChange);
    });

    document.querySelectorAll('[data-list-page-prev]').forEach((button) => {
        button.addEventListener('click', handleListPrevPageClick);
    });

    document.querySelectorAll('[data-list-page-next]').forEach((button) => {
        button.addEventListener('click', handleListNextPageClick);
    });

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
    const path = currentHashPath() || '#/';
    const page = routes[path] || renderHome;
    app.innerHTML = page();
    bindPageEvents();

    if (path === '#/users') {
        fetchUsers();
    }

    if (path === '#/admins') {
        fetchAdmins();
    }

    if (path === '#/organizations') {
        if (!state.organizationsError) {
            fetchOrganizations();
        }
    }

    if (path === '#/projects') {
        fetchProjects();
    }

    if (path === '#/sessions') {
        fetchSessions();
    }

    if (path === '#/audit-logs') {
        fetchAuditLogs();
    }
}

window.addEventListener('hashchange', render);

(async () => {
    await loadMe();
    if (!window.location.hash) {
        window.location.hash = '#/';
    }
    render();
})();
