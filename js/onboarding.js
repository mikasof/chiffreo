/**
 * Chiffreo - Onboarding Module
 * Parcours d'onboarding en 4 étapes
 */

(function() {
    'use strict';

    // ============================================
    // Configuration (depuis config.js dynamique)
    // ============================================
    const CONFIG = window.CHIFFREO_CONFIG || { BASE_PATH: '', API_BASE: '/api' };
    const BASE_PATH = CONFIG.BASE_PATH;
    const API_BASE = CONFIG.API_BASE;
    const TOKEN_KEY = 'chiffreo_token';
    const USER_KEY = 'chiffreo_user';

    let currentStep = 1;
    let totalSteps = 4;
    let onboardingData = {};
    let deferredInstallPrompt = null;

    // ============================================
    // DOM Elements
    // ============================================
    const steps = document.querySelectorAll('.step');
    const progressFill = document.getElementById('progress-fill');
    const progressText = document.getElementById('progress-text');
    const skipBtn = document.getElementById('skip-btn');
    const trialDays = document.getElementById('trial-days');

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

        // Initialiser les étapes
        initStep1();
        initStep2();
        initStep3();
        initStep4();

        // Skip button
        skipBtn.addEventListener('click', skipOnboarding);

        // PWA install prompt
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredInstallPrompt = e;
        });

        // Afficher la première étape
        showStep(1);
    }

    // ============================================
    // Auth Check
    // ============================================
    function checkAuth() {
        const token = localStorage.getItem(TOKEN_KEY);
        return !!token;
    }

    function getToken() {
        return localStorage.getItem(TOKEN_KEY);
    }

    function loadUserData() {
        const user = localStorage.getItem(USER_KEY);
        if (user) {
            const userData = JSON.parse(user);

            // Afficher le prénom
            const userNameEl = document.getElementById('user-name');
            if (userNameEl && userData.first_name) {
                userNameEl.textContent = userData.first_name;
            } else if (userNameEl) {
                userNameEl.style.display = 'none';
            }

            // Afficher les jours d'essai restants (dans user.company)
            const company = userData.company || {};
            if (trialDays && company.days_remaining) {
                trialDays.textContent = company.days_remaining;
            }

            // Reprendre là où on s'est arrêté
            if (userData.onboarding_step > 0 && userData.onboarding_step < 4) {
                currentStep = userData.onboarding_step + 1;
            }
        }
    }

    // ============================================
    // Step Navigation
    // ============================================
    function showStep(step) {
        currentStep = step;

        // Update steps visibility
        steps.forEach(s => {
            s.classList.toggle('active', parseInt(s.dataset.step) === step);
        });

        // Update progress
        const progress = (step / totalSteps) * 100;
        progressFill.style.width = `${progress}%`;
        progressText.textContent = `Étape ${step} sur ${totalSteps}`;

        // Hide skip on last step
        skipBtn.style.display = step === 4 ? 'none' : 'block';

        // Scroll to top
        window.scrollTo(0, 0);
    }

    function nextStep() {
        if (currentStep < totalSteps) {
            saveProgress(currentStep);
            showStep(currentStep + 1);
        }
    }

    function prevStep() {
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    }

    async function saveProgress(step) {
        try {
            // Construire le payload avec toutes les données
            const payload = {
                onboarding_step: step,
                onboarding_completed: step >= 4,
                ...onboardingData
            };

            await fetch(`${API_BASE}/auth/onboarding`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${getToken()}`
                },
                body: JSON.stringify(payload)
            });
        } catch (e) {
            console.error('Error saving progress:', e);
        }
    }

    function skipOnboarding() {
        if (confirm('Êtes-vous sûr de vouloir passer l\'onboarding ? Vous pourrez y revenir plus tard.')) {
            finishOnboarding();
        }
    }

    async function finishOnboarding() {
        await saveProgress(4);
        window.location.href = BASE_PATH + '/app';
    }

    // ============================================
    // Step 1: Bienvenue
    // ============================================
    function initStep1() {
        const nextBtn = document.getElementById('next-1');
        const optionBtns = document.querySelectorAll('#step-1 .option-btn');
        let selections = { metier: null, volume: null };

        optionBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const parent = btn.closest('.question-group');
                const siblings = parent.querySelectorAll('.option-btn');

                // Toggle selection
                siblings.forEach(s => s.classList.remove('selected'));
                btn.classList.add('selected');

                // Store value
                const isMetier = parent.querySelector('.option-grid') !== null;
                if (isMetier) {
                    selections.metier = btn.dataset.value;
                    onboardingData.metier = btn.dataset.value;
                } else {
                    selections.volume = btn.dataset.value;
                    onboardingData.volume_devis = btn.dataset.value;
                }

                // Enable next button when both selected
                nextBtn.disabled = !(selections.metier && selections.volume);
            });
        });

        nextBtn.addEventListener('click', nextStep);
    }

    // ============================================
    // Step 2: Infos entreprise + Tarification
    // ============================================
    function initStep2() {
        const nextBtn = document.getElementById('next-2');
        const backBtn = document.getElementById('back-2');
        const companyInput = document.getElementById('company-name');
        const siretInput = document.getElementById('siret');
        const phoneInput = document.getElementById('phone');

        // Pricing fields
        const hourlyRateInput = document.getElementById('hourly-rate');
        const productMarginInput = document.getElementById('product-margin');
        const supplierDiscountInput = document.getElementById('supplier-discount');
        const travelBtns = document.querySelectorAll('.travel-options .option-btn');
        const travelDetails = document.getElementById('travel-details');
        const travelFixedGroup = document.getElementById('travel-fixed-group');
        const travelKmGroup = document.getElementById('travel-km-group');
        const travelFixedAmount = document.getElementById('travel-fixed-amount');
        const travelPerKm = document.getElementById('travel-per-km');
        const travelFreeRadius = document.getElementById('travel-free-radius');

        // Company validation
        companyInput.addEventListener('input', () => {
            nextBtn.disabled = !companyInput.value.trim();
            onboardingData.company_name = companyInput.value.trim();
        });

        siretInput.addEventListener('input', () => {
            let value = siretInput.value.replace(/\D/g, '');
            if (value.length > 14) value = value.slice(0, 14);
            siretInput.value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{0,5})/, '$1 $2 $3 $4').trim();
            onboardingData.siret = value;
        });

        phoneInput.addEventListener('input', () => {
            onboardingData.phone = phoneInput.value;
        });

        // Pricing fields - set defaults
        onboardingData.hourly_rate = parseFloat(hourlyRateInput.value) || 45;
        onboardingData.product_margin = parseFloat(productMarginInput.value) || 20;
        onboardingData.supplier_discount = parseFloat(supplierDiscountInput.value) || 0;
        onboardingData.travel_type = 'free';
        onboardingData.travel_fixed_amount = parseFloat(travelFixedAmount.value) || 30;
        onboardingData.travel_per_km = parseFloat(travelPerKm.value) || 0.50;
        onboardingData.travel_free_radius = parseInt(travelFreeRadius.value) || 20;

        hourlyRateInput.addEventListener('input', () => {
            onboardingData.hourly_rate = parseFloat(hourlyRateInput.value) || 0;
        });

        productMarginInput.addEventListener('input', () => {
            onboardingData.product_margin = parseFloat(productMarginInput.value) || 0;
        });

        supplierDiscountInput.addEventListener('input', () => {
            onboardingData.supplier_discount = parseFloat(supplierDiscountInput.value) || 0;
        });

        // Travel type selection
        travelBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                travelBtns.forEach(b => b.classList.remove('selected'));
                btn.classList.add('selected');

                const travelType = btn.dataset.value;
                onboardingData.travel_type = travelType;

                // Show/hide travel details
                if (travelType === 'free') {
                    travelDetails.style.display = 'none';
                } else {
                    travelDetails.style.display = 'block';
                    travelFixedGroup.style.display = travelType === 'fixed' ? 'block' : 'none';
                    travelKmGroup.style.display = travelType === 'per_km' ? 'block' : 'none';
                }
            });
        });

        travelFixedAmount.addEventListener('input', () => {
            onboardingData.travel_fixed_amount = parseFloat(travelFixedAmount.value) || 0;
        });

        travelPerKm.addEventListener('input', () => {
            onboardingData.travel_per_km = parseFloat(travelPerKm.value) || 0;
        });

        travelFreeRadius.addEventListener('input', () => {
            onboardingData.travel_free_radius = parseInt(travelFreeRadius.value) || 0;
        });

        nextBtn.addEventListener('click', nextStep);
        backBtn.addEventListener('click', prevStep);
    }

    // ============================================
    // Step 3: Premier devis (démo)
    // ============================================
    function initStep3() {
        const nextBtn = document.getElementById('next-3');
        const backBtn = document.getElementById('back-3');
        const voiceBtn = document.getElementById('demo-voice-btn');
        const textarea = document.getElementById('demo-description');
        const generateBtn = document.getElementById('demo-generate');
        const resultDiv = document.getElementById('demo-result');

        let mediaRecorder = null;
        let audioChunks = [];

        // Enable generate when text entered
        textarea.addEventListener('input', () => {
            generateBtn.disabled = !textarea.value.trim();
        });

        // Voice recording
        voiceBtn.addEventListener('click', async () => {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
                voiceBtn.classList.remove('recording');
                voiceBtn.innerHTML = '<i class="ph ph-microphone"></i><span>Dicter</span>';
                return;
            }

            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];

                mediaRecorder.ondataavailable = (e) => {
                    audioChunks.push(e.data);
                };

                mediaRecorder.onstop = async () => {
                    stream.getTracks().forEach(t => t.stop());

                    const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                    const formData = new FormData();
                    formData.append('audio', audioBlob, 'recording.webm');

                    try {
                        const response = await fetch(`${API_BASE}/transcribe`, {
                            method: 'POST',
                            headers: { 'Authorization': `Bearer ${getToken()}` },
                            body: formData
                        });

                        const data = await response.json();
                        if (data.success) {
                            textarea.value = data.data.text;
                            generateBtn.disabled = false;
                        }
                    } catch (e) {
                        console.error('Transcription error:', e);
                    }
                };

                mediaRecorder.start();
                voiceBtn.classList.add('recording');
                voiceBtn.innerHTML = '<i class="ph ph-stop"></i><span>Stop</span>';

                // Auto-stop after 30 seconds
                setTimeout(() => {
                    if (mediaRecorder.state === 'recording') {
                        mediaRecorder.stop();
                        voiceBtn.classList.remove('recording');
                        voiceBtn.innerHTML = '<i class="ph ph-microphone"></i><span>Dicter</span>';
                    }
                }, 30000);

            } catch (e) {
                console.error('Microphone error:', e);
                alert('Impossible d\'accéder au microphone');
            }
        });

        // Generate demo quote
        generateBtn.addEventListener('click', async () => {
            const description = textarea.value.trim();
            if (!description) return;

            generateBtn.disabled = true;
            generateBtn.innerHTML = '<i class="ph ph-spinner"></i><span>Génération...</span>';

            try {
                const response = await fetch(`${API_BASE}/generate`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${getToken()}`
                    },
                    body: JSON.stringify({ description })
                });

                const data = await response.json();

                if (data.success) {
                    const quote = data.data.quote;
                    document.getElementById('result-title').textContent = quote.chantier?.titre || 'Devis travaux';
                    document.getElementById('result-total').textContent = `${quote.totaux.total_ttc.toFixed(2)} € TTC`;
                    resultDiv.classList.add('visible');
                    onboardingData.first_quote_id = data.data.id;
                }
            } catch (e) {
                console.error('Generate error:', e);
            } finally {
                generateBtn.disabled = false;
                generateBtn.innerHTML = '<i class="ph ph-sparkle"></i><span>Générer le devis</span>';
            }
        });

        nextBtn.addEventListener('click', nextStep);
        backBtn.addEventListener('click', prevStep);
    }

    // ============================================
    // Step 4: PWA + Notifications
    // ============================================
    function initStep4() {
        const finishBtn = document.getElementById('finish-btn');
        const backBtn = document.getElementById('back-4');
        const installPwaBtn = document.getElementById('install-pwa-btn');
        const enableNotifBtn = document.getElementById('enable-notif-btn');
        const pwaCard = document.getElementById('pwa-card');
        const notifCard = document.getElementById('notif-card');

        // Check if already installed as PWA
        if (window.matchMedia('(display-mode: standalone)').matches) {
            pwaCard.classList.add('completed');
        }

        // Check notification permission
        if ('Notification' in window && Notification.permission === 'granted') {
            notifCard.classList.add('completed');
        }

        // PWA Install
        installPwaBtn.addEventListener('click', async () => {
            if (deferredInstallPrompt) {
                deferredInstallPrompt.prompt();
                const result = await deferredInstallPrompt.userChoice;

                if (result.outcome === 'accepted') {
                    pwaCard.classList.add('completed');
                    onboardingData.pwa_installed = true;
                }

                deferredInstallPrompt = null;
            } else {
                // Show manual install instructions
                alert('Pour installer l\'application :\n\nSur Chrome : Menu ⋮ > "Installer l\'application"\nSur Safari iOS : Partager > "Sur l\'écran d\'accueil"');
            }
        });

        // Enable notifications
        enableNotifBtn.addEventListener('click', async () => {
            if (!('Notification' in window)) {
                alert('Les notifications ne sont pas supportées par votre navigateur');
                return;
            }

            try {
                const permission = await Notification.requestPermission();

                if (permission === 'granted') {
                    notifCard.classList.add('completed');
                    onboardingData.notifications_enabled = true;

                    // Register for push notifications
                    await registerPushNotifications();
                }
            } catch (e) {
                console.error('Notification error:', e);
            }
        });

        finishBtn.addEventListener('click', finishOnboarding);
        backBtn.addEventListener('click', prevStep);
    }

    async function registerPushNotifications() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            return;
        }

        try {
            const registration = await navigator.serviceWorker.ready;

            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(window.VAPID_PUBLIC_KEY)
            });

            // Send subscription to server
            await fetch(`${API_BASE}/push/subscribe`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${getToken()}`
                },
                body: JSON.stringify(subscription.toJSON())
            });

        } catch (e) {
            console.error('Push registration error:', e);
        }
    }

    function urlBase64ToUint8Array(base64String) {
        if (!base64String) return new Uint8Array();

        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }

        return outputArray;
    }

    // ============================================
    // Init on DOM ready
    // ============================================
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
