/**
 * Chiffreo - Auth Module
 * Gestion de l'inscription et de la connexion
 */

(function() {
    'use strict';

    // ============================================
    // Configuration
    // ============================================
    const BASE_PATH = '/chiffreo/public';
    const API_BASE = BASE_PATH + '/api/auth';
    const TOKEN_KEY = 'chiffreo_token';
    const USER_KEY = 'chiffreo_user';

    // ============================================
    // DOM Elements
    // ============================================
    const tabs = document.querySelectorAll('.auth-tab');
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const loginError = document.getElementById('login-error');
    const registerError = document.getElementById('register-error');

    // ============================================
    // Initialization
    // ============================================
    function init() {
        // Vérifier si déjà connecté
        checkExistingAuth();

        // Tabs
        tabs.forEach(tab => {
            tab.addEventListener('click', () => switchTab(tab.dataset.tab));
        });

        // Forms
        loginForm.addEventListener('submit', handleLogin);
        registerForm.addEventListener('submit', handleRegister);

        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(btn => {
            btn.addEventListener('click', togglePasswordVisibility);
        });

        // Password strength indicator
        const passwordInput = document.getElementById('register-password');
        if (passwordInput) {
            passwordInput.addEventListener('input', updatePasswordStrength);
        }

        // Check URL params for tab
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('tab') === 'register') {
            switchTab('register');
        }
    }

    // ============================================
    // Auth State
    // ============================================
    function checkExistingAuth() {
        const token = localStorage.getItem(TOKEN_KEY);
        if (token) {
            // Vérifier si le token est valide
            fetch(`${API_BASE}/me`, {
                headers: { 'Authorization': `Bearer ${token}` }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Déjà connecté, rediriger
                    const redirect = data.data.user.onboarding_completed ? BASE_PATH + '/app' : BASE_PATH + '/onboarding';
                    window.location.href = redirect;
                }
            })
            .catch(() => {
                // Token invalide, le supprimer
                localStorage.removeItem(TOKEN_KEY);
                localStorage.removeItem(USER_KEY);
            });
        }
    }

    // ============================================
    // Tab Switching
    // ============================================
    function switchTab(tabName) {
        // Update tabs
        tabs.forEach(tab => {
            tab.classList.toggle('active', tab.dataset.tab === tabName);
        });

        // Update forms
        loginForm.classList.toggle('active', tabName === 'login');
        registerForm.classList.toggle('active', tabName === 'register');

        // Update URL without reload
        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        window.history.replaceState({}, '', url);

        // Clear errors
        hideError(loginError);
        hideError(registerError);
    }

    // ============================================
    // Login Handler
    // ============================================
    async function handleLogin(e) {
        e.preventDefault();
        hideError(loginError);

        const submitBtn = loginForm.querySelector('.btn-submit');
        const email = document.getElementById('login-email').value.trim();
        const password = document.getElementById('login-password').value;

        if (!email || !password) {
            showError(loginError, 'Veuillez remplir tous les champs');
            return;
        }

        setLoading(submitBtn, true);

        try {
            const response = await fetch(`${API_BASE}/login`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password })
            });

            const data = await response.json();

            if (data.success) {
                // Sauvegarder le token et l'utilisateur
                localStorage.setItem(TOKEN_KEY, data.data.token);
                localStorage.setItem(USER_KEY, JSON.stringify(data.data.user));

                // Optionnel: cookie pour le fallback
                if (document.getElementById('remember-me').checked) {
                    document.cookie = `auth_token=${data.data.token}; path=/; max-age=${30 * 24 * 60 * 60}; SameSite=Strict`;
                }

                // Rediriger
                window.location.href = data.data.redirect || BASE_PATH + '/app';
            } else {
                showError(loginError, data.error || 'Erreur de connexion');
            }
        } catch (error) {
            console.error('Login error:', error);
            showError(loginError, 'Erreur de connexion. Veuillez réessayer.');
        } finally {
            setLoading(submitBtn, false);
        }
    }

    // ============================================
    // Register Handler
    // ============================================
    async function handleRegister(e) {
        e.preventDefault();
        hideError(registerError);

        const submitBtn = registerForm.querySelector('.btn-submit');
        const firstName = document.getElementById('register-name').value.trim();
        const email = document.getElementById('register-email').value.trim();
        const password = document.getElementById('register-password').value;
        const acceptTerms = document.getElementById('accept-terms').checked;

        // Validation
        if (!email) {
            showError(registerError, 'L\'email est requis');
            return;
        }

        if (!isValidEmail(email)) {
            showError(registerError, 'Adresse email invalide');
            return;
        }

        if (password.length < 8) {
            showError(registerError, 'Le mot de passe doit contenir au moins 8 caractères');
            return;
        }

        if (!acceptTerms) {
            showError(registerError, 'Veuillez accepter les conditions d\'utilisation');
            return;
        }

        setLoading(submitBtn, true);

        try {
            const response = await fetch(`${API_BASE}/register`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    first_name: firstName || null,
                    email,
                    password
                })
            });

            const data = await response.json();

            if (data.success) {
                // Sauvegarder le token et l'utilisateur
                localStorage.setItem(TOKEN_KEY, data.data.token);
                localStorage.setItem(USER_KEY, JSON.stringify(data.data.user));

                // Cookie
                document.cookie = `auth_token=${data.data.token}; path=/; max-age=${30 * 24 * 60 * 60}; SameSite=Strict`;

                // Rediriger vers l'onboarding
                window.location.href = data.data.redirect || BASE_PATH + '/onboarding';
            } else {
                showError(registerError, data.error || 'Erreur lors de l\'inscription');
            }
        } catch (error) {
            console.error('Register error:', error);
            showError(registerError, 'Erreur lors de l\'inscription. Veuillez réessayer.');
        } finally {
            setLoading(submitBtn, false);
        }
    }

    // ============================================
    // Password Visibility Toggle
    // ============================================
    function togglePasswordVisibility(e) {
        const btn = e.currentTarget;
        const input = btn.parentElement.querySelector('input');
        const icon = btn.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('ph-eye');
            icon.classList.add('ph-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('ph-eye-slash');
            icon.classList.add('ph-eye');
        }
    }

    // ============================================
    // Password Strength
    // ============================================
    function updatePasswordStrength(e) {
        const password = e.target.value;
        const strengthBar = document.querySelector('.strength-bar');

        if (!strengthBar) return;

        // Reset
        strengthBar.className = 'strength-bar';

        if (password.length === 0) {
            strengthBar.style.width = '0';
            return;
        }

        let score = 0;

        // Length
        if (password.length >= 8) score++;
        if (password.length >= 12) score++;

        // Contains
        if (/[a-z]/.test(password)) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^a-zA-Z0-9]/.test(password)) score++;

        // Classify
        if (score <= 2) {
            strengthBar.classList.add('weak');
        } else if (score <= 4) {
            strengthBar.classList.add('medium');
        } else {
            strengthBar.classList.add('strong');
        }
    }

    // ============================================
    // Utilities
    // ============================================
    function showError(element, message) {
        element.textContent = message;
        element.classList.add('visible');
    }

    function hideError(element) {
        element.textContent = '';
        element.classList.remove('visible');
    }

    function setLoading(button, loading) {
        button.disabled = loading;
        button.classList.toggle('loading', loading);
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    // ============================================
    // Public API
    // ============================================
    window.ChiffreoAuth = {
        // Obtenir le token
        getToken: () => localStorage.getItem(TOKEN_KEY),

        // Obtenir l'utilisateur
        getUser: () => {
            const user = localStorage.getItem(USER_KEY);
            return user ? JSON.parse(user) : null;
        },

        // Vérifier si connecté
        isAuthenticated: () => !!localStorage.getItem(TOKEN_KEY),

        // Déconnexion
        logout: async () => {
            const token = localStorage.getItem(TOKEN_KEY);

            if (token) {
                try {
                    await fetch(`${API_BASE}/logout`, {
                        method: 'POST',
                        headers: { 'Authorization': `Bearer ${token}` }
                    });
                } catch (e) {
                    console.error('Logout error:', e);
                }
            }

            localStorage.removeItem(TOKEN_KEY);
            localStorage.removeItem(USER_KEY);
            document.cookie = 'auth_token=; path=/; max-age=0';

            window.location.href = BASE_PATH + '/auth';
        },

        // Rafraîchir les données utilisateur
        refreshUser: async () => {
            const token = localStorage.getItem(TOKEN_KEY);
            if (!token) return null;

            try {
                const response = await fetch(`${API_BASE}/me`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const data = await response.json();

                if (data.success) {
                    localStorage.setItem(USER_KEY, JSON.stringify(data.data.user));
                    return data.data.user;
                }
            } catch (e) {
                console.error('Refresh user error:', e);
            }

            return null;
        }
    };

    // Init on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
