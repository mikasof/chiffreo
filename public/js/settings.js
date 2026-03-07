/**
 * Chiffreo - Settings Page
 * Gestion du compte utilisateur
 */

(function() {
    'use strict';

    // ============================================
    // Configuration
    // ============================================
    const CONFIG = window.CHIFFREO_CONFIG || { BASE_PATH: '', API_BASE: '/api' };
    const BASE_PATH = CONFIG.BASE_PATH;
    const API_BASE = CONFIG.API_BASE;
    const TOKEN_KEY = 'chiffreo_token';
    const USER_KEY = 'chiffreo_user';

    // ============================================
    // State
    // ============================================
    let currentUser = null;
    let quotes = [];

    // ============================================
    // DOM Elements
    // ============================================
    const tabs = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    // Forms
    const companyForm = document.getElementById('company-form');
    const pricingForm = document.getElementById('pricing-form');
    const travelForm = document.getElementById('travel-form');

    // ============================================
    // Initialization
    // ============================================
    function init() {
        // Vérifier l'authentification
        if (!checkAuth()) {
            window.location.href = BASE_PATH + '/auth';
            return;
        }

        // Charger les données utilisateur
        loadUserData();

        // Tabs
        tabs.forEach(tab => {
            tab.addEventListener('click', () => switchTab(tab.dataset.tab));
        });

        // Forms
        companyForm.addEventListener('submit', handleCompanySubmit);
        pricingForm.addEventListener('submit', handlePricingSubmit);
        travelForm.addEventListener('submit', handleTravelSubmit);

        // Travel type radio
        document.querySelectorAll('input[name="travel-type"]').forEach(radio => {
            radio.addEventListener('change', handleTravelTypeChange);
        });

        // Pricing example update
        document.getElementById('supplier-discount').addEventListener('input', updatePricingExample);
        document.getElementById('product-margin').addEventListener('input', updatePricingExample);

        // Logo upload
        document.getElementById('upload-logo-btn').addEventListener('click', () => {
            document.getElementById('logo-input').click();
        });
        document.getElementById('logo-input').addEventListener('change', handleLogoUpload);
        document.getElementById('remove-logo-btn').addEventListener('click', handleLogoRemove);

        // Logout
        document.getElementById('logout-btn').addEventListener('click', handleLogout);

        // User menu toggle
        const userMenuToggle = document.querySelector('.user-menu-toggle');
        if (userMenuToggle) {
            userMenuToggle.addEventListener('click', toggleUserMenu);
        }

        // Check URL hash for initial tab
        const hash = window.location.hash.replace('#', '');
        if (hash && document.getElementById('tab-' + hash)) {
            switchTab(hash);
        }
    }

    // ============================================
    // Auth
    // ============================================
    function checkAuth() {
        const token = localStorage.getItem(TOKEN_KEY);
        return !!token;
    }

    function getToken() {
        return localStorage.getItem(TOKEN_KEY);
    }

    async function handleLogout() {
        const token = getToken();

        if (token) {
            try {
                await fetch(`${API_BASE}/auth/logout`, {
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
    }

    // ============================================
    // User Data
    // ============================================
    async function loadUserData() {
        const token = getToken();

        try {
            const response = await fetch(`${API_BASE}/user/profile`, {
                headers: { 'Authorization': `Bearer ${token}` }
            });

            const data = await response.json();

            if (data.success) {
                currentUser = data.data.user;
                populateForms(currentUser);
                updateUserMenu(currentUser);
                loadQuotes();
            } else {
                showToast('Erreur de chargement du profil', 'error');
            }
        } catch (error) {
            console.error('Load user error:', error);
            showToast('Erreur de connexion', 'error');
        }
    }

    function populateForms(user) {
        // Company form
        setValue('company-name', user.company_name);
        setValue('siret', user.siret);
        setValue('vat-number', user.vat_number);
        setValue('address-line1', user.address_line1);
        setValue('address-line2', user.address_line2);
        setValue('postal-code', user.postal_code);
        setValue('city', user.city);
        setValue('phone', user.phone);
        setValue('email-pro', user.email);
        setValue('insurance-name', user.insurance_name);
        setValue('insurance-number', user.insurance_number);

        // Logo
        if (user.logo_path) {
            setLogoPreview(user.logo_path);
        }

        // Pricing form
        setValue('hourly-rate', user.hourly_rate);
        setValue('supplier-discount', user.supplier_discount || 0);
        setValue('product-margin', user.product_margin || 20);

        // Default tier
        const tier = user.default_tier || 'mid';
        const tierRadio = document.querySelector(`input[name="default-tier"][value="${tier}"]`);
        if (tierRadio) tierRadio.checked = true;

        // Travel form
        const travelType = user.travel_type || 'none';
        const travelRadio = document.querySelector(`input[name="travel-type"][value="${travelType}"]`);
        if (travelRadio) {
            travelRadio.checked = true;
            handleTravelTypeChange();
        }

        setValue('travel-fixed', user.travel_fixed_amount || 30);
        setValue('travel-per-km', user.travel_per_km || 0.5);
        setValue('travel-free-radius', user.travel_free_radius || 10);

        // Update pricing example
        updatePricingExample();
    }

    function setValue(id, value) {
        const el = document.getElementById(id);
        if (el && value !== null && value !== undefined) {
            el.value = value;
        }
    }

    function updateUserMenu(user) {
        const userName = document.querySelector('.user-name');
        if (userName) {
            userName.textContent = user.first_name || user.company_name || 'Mon compte';
        }
    }

    // ============================================
    // Tabs
    // ============================================
    function switchTab(tabName) {
        // Update tabs
        tabs.forEach(tab => {
            tab.classList.toggle('active', tab.dataset.tab === tabName);
        });

        // Update content
        tabContents.forEach(content => {
            content.classList.toggle('active', content.id === 'tab-' + tabName);
        });

        // Update URL
        window.location.hash = tabName;
    }

    // ============================================
    // Quotes
    // ============================================
    async function loadQuotes() {
        const token = getToken();
        const loadingEl = document.getElementById('quotes-loading');
        const emptyEl = document.getElementById('quotes-empty');
        const listEl = document.getElementById('quotes-list');

        try {
            const response = await fetch(`${API_BASE}/quotes`, {
                headers: { 'Authorization': `Bearer ${token}` }
            });

            const data = await response.json();

            loadingEl.style.display = 'none';

            if (data.success && data.data.quotes && data.data.quotes.length > 0) {
                quotes = data.data.quotes;
                renderQuotes(quotes);
                listEl.style.display = 'flex';
            } else {
                emptyEl.style.display = 'block';
            }
        } catch (error) {
            console.error('Load quotes error:', error);
            loadingEl.style.display = 'none';
            emptyEl.style.display = 'block';
        }
    }

    function renderQuotes(quotes) {
        const listEl = document.getElementById('quotes-list');
        listEl.innerHTML = quotes.map(quote => `
            <div class="quote-card" data-id="${quote.id}">
                <div class="quote-icon">
                    <i class="ph ph-file-text"></i>
                </div>
                <div class="quote-info">
                    <div class="quote-ref">${quote.reference || 'Devis #' + quote.id}</div>
                    <div class="quote-client">${quote.client_name || 'Client non renseigné'}</div>
                    <div class="quote-date">${formatDate(quote.created_at)}</div>
                </div>
                <div class="quote-amount">
                    ${formatAmount(quote.total_ttc)}
                    <div class="quote-amount-label">TTC</div>
                </div>
                <div class="quote-actions">
                    <a href="${BASE_PATH}/app?edit=${quote.id}" class="btn-icon" title="Modifier">
                        <i class="ph ph-pencil-simple"></i>
                    </a>
                    <a href="${BASE_PATH}/pdf/quote/${quote.id}" target="_blank" class="btn-icon" title="Télécharger PDF">
                        <i class="ph ph-file-pdf"></i>
                    </a>
                    <button class="btn-icon btn-danger" onclick="deleteQuote('${quote.id}')" title="Supprimer">
                        <i class="ph ph-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    }

    function formatAmount(amount) {
        if (!amount) return '0,00 €';
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR'
        }).format(amount);
    }

    // Global function for delete button
    window.deleteQuote = async function(quoteId) {
        if (!confirm('Supprimer ce devis ?')) return;

        const token = getToken();

        try {
            const response = await fetch(`${API_BASE}/quotes/${quoteId}`, {
                method: 'DELETE',
                headers: { 'Authorization': `Bearer ${token}` }
            });

            const data = await response.json();

            if (data.success) {
                showToast('Devis supprimé', 'success');
                loadQuotes();
            } else {
                showToast(data.error || 'Erreur lors de la suppression', 'error');
            }
        } catch (error) {
            console.error('Delete quote error:', error);
            showToast('Erreur lors de la suppression', 'error');
        }
    };

    // ============================================
    // Company Form
    // ============================================
    async function handleCompanySubmit(e) {
        e.preventDefault();

        const token = getToken();
        const btn = companyForm.querySelector('button[type="submit"]');
        setLoading(btn, true);

        const data = {
            company_name: document.getElementById('company-name').value,
            siret: document.getElementById('siret').value,
            vat_number: document.getElementById('vat-number').value,
            address_line1: document.getElementById('address-line1').value,
            address_line2: document.getElementById('address-line2').value,
            postal_code: document.getElementById('postal-code').value,
            city: document.getElementById('city').value,
            phone: document.getElementById('phone').value,
            insurance_name: document.getElementById('insurance-name').value,
            insurance_number: document.getElementById('insurance-number').value
        };

        try {
            const response = await fetch(`${API_BASE}/user/company`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                showToast('Informations enregistrées', 'success');
                Object.assign(currentUser, data);
            } else {
                showToast(result.error || 'Erreur lors de l\'enregistrement', 'error');
            }
        } catch (error) {
            console.error('Save company error:', error);
            showToast('Erreur lors de l\'enregistrement', 'error');
        } finally {
            setLoading(btn, false);
        }
    }

    // ============================================
    // Pricing Form
    // ============================================
    async function handlePricingSubmit(e) {
        e.preventDefault();

        const token = getToken();
        const btn = pricingForm.querySelector('button[type="submit"]');
        setLoading(btn, true);

        const tierRadio = document.querySelector('input[name="default-tier"]:checked');

        const data = {
            hourly_rate: parseFloat(document.getElementById('hourly-rate').value) || null,
            supplier_discount: parseFloat(document.getElementById('supplier-discount').value) || 0,
            product_margin: parseFloat(document.getElementById('product-margin').value) || 20,
            default_tier: tierRadio ? tierRadio.value : 'mid'
        };

        try {
            const response = await fetch(`${API_BASE}/user/pricing`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                showToast('Paramètres enregistrés', 'success');
                Object.assign(currentUser, data);
            } else {
                showToast(result.error || 'Erreur lors de l\'enregistrement', 'error');
            }
        } catch (error) {
            console.error('Save pricing error:', error);
            showToast('Erreur lors de l\'enregistrement', 'error');
        } finally {
            setLoading(btn, false);
        }
    }

    function updatePricingExample() {
        const discount = parseFloat(document.getElementById('supplier-discount').value) || 0;
        const margin = parseFloat(document.getElementById('product-margin').value) || 0;

        const publicPrice = 100;
        const discountAmount = publicPrice * (discount / 100);
        const purchasePrice = publicPrice - discountAmount;
        const marginAmount = purchasePrice * (margin / 100);
        const salePrice = purchasePrice + marginAmount;

        document.getElementById('ex-discount-pct').textContent = discount;
        document.getElementById('ex-margin-pct').textContent = margin;
        document.getElementById('ex-discount').textContent = '- ' + formatAmount(discountAmount);
        document.getElementById('ex-purchase').textContent = formatAmount(purchasePrice);
        document.getElementById('ex-margin').textContent = '+ ' + formatAmount(marginAmount);
        document.getElementById('ex-sale').textContent = formatAmount(salePrice);
    }

    // ============================================
    // Travel Form
    // ============================================
    function handleTravelTypeChange() {
        const type = document.querySelector('input[name="travel-type"]:checked').value;

        document.getElementById('travel-fixed-section').style.display =
            type === 'fixed' ? 'block' : 'none';
        document.getElementById('travel-km-section').style.display =
            type === 'per_km' ? 'block' : 'none';
    }

    async function handleTravelSubmit(e) {
        e.preventDefault();

        const token = getToken();
        const btn = travelForm.querySelector('button[type="submit"]');
        setLoading(btn, true);

        const typeRadio = document.querySelector('input[name="travel-type"]:checked');

        const data = {
            travel_type: typeRadio ? typeRadio.value : 'none',
            travel_fixed_amount: parseFloat(document.getElementById('travel-fixed').value) || 0,
            travel_per_km: parseFloat(document.getElementById('travel-per-km').value) || 0,
            travel_free_radius: parseFloat(document.getElementById('travel-free-radius').value) || 0
        };

        try {
            const response = await fetch(`${API_BASE}/user/pricing`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                showToast('Paramètres enregistrés', 'success');
                Object.assign(currentUser, data);
            } else {
                showToast(result.error || 'Erreur lors de l\'enregistrement', 'error');
            }
        } catch (error) {
            console.error('Save travel error:', error);
            showToast('Erreur lors de l\'enregistrement', 'error');
        } finally {
            setLoading(btn, false);
        }
    }

    // ============================================
    // Logo Upload
    // ============================================
    async function handleLogoUpload(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Validate
        if (!file.type.startsWith('image/')) {
            showToast('Le fichier doit être une image', 'error');
            return;
        }

        if (file.size > 500 * 1024) {
            showToast('L\'image ne doit pas dépasser 500 KB', 'error');
            return;
        }

        const token = getToken();
        const formData = new FormData();
        formData.append('logo', file);

        try {
            const response = await fetch(`${API_BASE}/user/logo`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`
                },
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                setLogoPreview(result.data.logo_path);
                showToast('Logo téléchargé', 'success');
            } else {
                showToast(result.error || 'Erreur lors du téléchargement', 'error');
            }
        } catch (error) {
            console.error('Upload logo error:', error);
            showToast('Erreur lors du téléchargement', 'error');
        }

        // Reset input
        e.target.value = '';
    }

    function setLogoPreview(path) {
        const preview = document.getElementById('logo-preview');
        preview.innerHTML = `<img src="${BASE_PATH}/${path}" alt="Logo">`;
        preview.classList.add('has-logo');
        document.getElementById('remove-logo-btn').style.display = 'inline-flex';
    }

    async function handleLogoRemove() {
        if (!confirm('Supprimer le logo ?')) return;

        const token = getToken();

        try {
            const response = await fetch(`${API_BASE}/user/logo`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });

            const result = await response.json();

            if (result.success) {
                const preview = document.getElementById('logo-preview');
                preview.innerHTML = `
                    <i class="ph ph-image"></i>
                    <span>Aucun logo</span>
                `;
                preview.classList.remove('has-logo');
                document.getElementById('remove-logo-btn').style.display = 'none';
                showToast('Logo supprimé', 'success');
            } else {
                showToast(result.error || 'Erreur lors de la suppression', 'error');
            }
        } catch (error) {
            console.error('Remove logo error:', error);
            showToast('Erreur lors de la suppression', 'error');
        }
    }

    // ============================================
    // User Menu
    // ============================================
    function toggleUserMenu() {
        const menu = document.getElementById('user-menu');
        menu.classList.toggle('open');
    }

    // Close menu on click outside
    document.addEventListener('click', (e) => {
        const menu = document.getElementById('user-menu');
        if (menu && !menu.contains(e.target)) {
            menu.classList.remove('open');
        }
    });

    // ============================================
    // Utilities
    // ============================================
    function setLoading(button, loading) {
        button.disabled = loading;
        if (loading) {
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = '<span class="loading-spinner" style="width:16px;height:16px;border-width:2px;"></span>';
        } else if (button.dataset.originalText) {
            button.innerHTML = button.dataset.originalText;
        }
    }

    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <i class="ph ph-${type === 'success' ? 'check-circle' : 'warning-circle'}"></i>
            <span>${message}</span>
        `;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // ============================================
    // Init
    // ============================================
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
