// =========================================================
// APP.JS - احراز هویت، کاربران و دسترسی نقش‌ها
// نسخه متصل به PHP / MySQL
// =========================================================
(function () {
    'use strict';

    var SESSION_KEY = 'user';
    var ARTICLES_KEY = 'hy_articles';
    var API_BASE = 'backend/api';

    function apiUrl(path) {
        return API_BASE + '/' + path.replace(/^\/+/, '');
    }

    function readJSON(key, fallback) {
        try {
            var value = localStorage.getItem(key);
            return value ? JSON.parse(value) : fallback;
        } catch (e) {
            return fallback;
        }
    }

    function writeJSON(key, value) {
        localStorage.setItem(key, JSON.stringify(value));
    }

    function getUser() {
        return readJSON(SESSION_KEY, null);
    }

    async function apiRequest(path, options) {
        options = options || {};
        var response;
        try {
            response = await fetch(apiUrl(path), Object.assign({
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }
            }, options));
        } catch (error) {
            return { ok: false, status: 0, message: 'ارتباط با سرور برقرار نشد. مطمئن شوید پروژه از طریق PHP اجرا شده است.' };
        }

        var data = {};
        try { data = await response.json(); } catch (e) { data = {}; }

        return Object.assign({
            ok: response.ok && data.ok !== false,
            status: response.status,
            message: data.message || (response.ok ? '' : 'خطایی در سرور رخ داد.')
        }, data);
    }

    function saveUser(userData) {
        var sessionUser = Object.assign({}, userData || {});
        writeJSON(SESSION_KEY, sessionUser);
        return sessionUser;
    }

    async function registerUser(userData) {
        return apiRequest('auth/register.php', {
            method: 'POST',
            body: JSON.stringify({
                name: userData.name,
                email: userData.email,
                phone: userData.phone || '',
                role: 'author',
                password: userData.password
            })
        });
    }

    async function authenticateUser(identifier, password) {
        return apiRequest('auth/login.php', {
            method: 'POST',
            body: JSON.stringify({ email: identifier, password: password })
        });
    }

    async function refreshCurrentUser() {
        var result = await apiRequest('auth/me.php', { method: 'GET' });
        if (result.ok && result.user) {
            saveUser(result.user);
            return result.user;
        }
        localStorage.removeItem(SESSION_KEY);
        return null;
    }

    async function logoutUser(force) {
        if (!force && !confirm('آیا از خروج از حساب کاربری اطمینان دارید؟')) return;
        await apiRequest('auth/logout.php', { method: 'POST', body: '{}' });
        localStorage.removeItem(SESSION_KEY);
        window.location.href = 'login.html';
    }

    function requireRole(role) {
        var user = getUser();
        if (!user) {
            window.location.replace('login.html');
            return false;
        }
        if (user.role !== role) {
            window.location.replace(dashboardFor(user.role));
            return false;
        }
        return true;
    }

    function dashboardFor(role) {
        return role === 'reviewer' ? 'reviewer-dashboard.html'
            : role === 'secretariat' || role === 'admin' ? 'secretariat-dashboard.html'
            : 'author-dashboard.html';
    }

    function getArticles() {
        var stored = readJSON(ARTICLES_KEY, null);
        if (Array.isArray(stored)) {
            window.articlesData = stored;
            return stored;
        }
        return Array.isArray(window.articlesData) ? window.articlesData : [];
    }

    function saveArticles(articles) {
        writeJSON(ARTICLES_KEY, articles);
        window.articlesData = articles;
        return articles;
    }

    window.getUser = getUser;
    window.saveUser = saveUser;
    window.registerUser = registerUser;
    window.authenticateUser = authenticateUser;
    window.refreshCurrentUser = refreshCurrentUser;
    window.logoutUser = logoutUser;
    window.requireRole = requireRole;
    window.getArticles = getArticles;
    window.saveArticles = saveArticles;
    window.dashboardForRole = dashboardFor;
    window.apiRequest = apiRequest;

    document.addEventListener('DOMContentLoaded', function () {
        var page = (window.location.pathname.split('/').pop() || 'index.html').toLowerCase();
        var publicPages = ['index.html', 'login.html', 'register.html', 'events.html', 'event-details.html', 'schedule.html', 'authors.html', 'guest-schedule.html', 'survey.html'];
        var protectedRoles = {
            'author-dashboard.html': 'author',
            'author-panel.html': 'author',
            'reviewer-dashboard.html': 'reviewer',
            'secretariat-dashboard.html': 'secretariat',
            'add-reviewer.html': 'secretariat',
            'reports.html': 'secretariat',
            'events-admin.html': 'secretariat',
            'registrations-admin.html': 'secretariat',
            'profile.html': null,
            'notifications.html': null
        };

        if (publicPages.indexOf(page) !== -1) return;

        if (Object.prototype.hasOwnProperty.call(protectedRoles, page)) {
            var requiredRole = protectedRoles[page];
            var user = getUser();
            if (!user) {
                window.location.replace('login.html');
                return;
            }

            refreshCurrentUser().then(function (freshUser) {
                if (!freshUser) {
                    window.location.replace('login.html');
                    return;
                }
                if (requiredRole && freshUser.role !== requiredRole && !(requiredRole === 'secretariat' && freshUser.role === 'admin')) {
                    window.location.replace(dashboardFor(freshUser.role));
                }
            });
        }
    });
})();
