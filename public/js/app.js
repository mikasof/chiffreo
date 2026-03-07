/**
 * Chiffreo - Application JavaScript
 * PWA pour la génération de devis électriques
 */

// === Configuration (depuis config.js dynamique) ===
const CONFIG = window.CHIFFREO_CONFIG || { BASE_PATH: '', API_BASE: '/api' };
const BASE_PATH = CONFIG.BASE_PATH;
const API_BASE = CONFIG.API_BASE;

// === Constantes Auth ===
const TOKEN_KEY = 'chiffreo_token';
const USER_KEY = 'chiffreo_user';

// === État de l'application ===
const state = {
    currentStep: 1,
    isRecording: false,
    mediaRecorder: null,
    audioChunks: [],
    recordingStartTime: null,
    recordingTimer: null,
    transcription: '',
    appendMode: false, // Mode compléter transcription
    images: [],
    currentQuote: null,
    editMode: false, // Mode édition d'un devis existant
    editQuoteId: null, // ID du devis en cours d'édition
    hasChanges: false, // Flag pour savoir si des modifications ont été faites
    // Authentification
    isAuthenticated: false,
    user: null,
    quota: null,
    // Données client (jamais envoyées à l'IA)
    client: {
        civilite: 'M.',
        nom: '',
        prenom: '',
        societe: '',
        email: '',
        telephone: '',
        adresse: '',
        codePostal: '',
        ville: ''
    },
    chantier: {
        adresse: '',
        codePostal: '',
        ville: ''
    }
};

// === Module Auth ===
const Auth = {
    getToken() {
        return localStorage.getItem(TOKEN_KEY);
    },

    getUser() {
        const user = localStorage.getItem(USER_KEY);
        return user ? JSON.parse(user) : null;
    },

    isLoggedIn() {
        return !!this.getToken();
    },

    async checkAuth() {
        const token = this.getToken();
        if (!token) {
            state.isAuthenticated = false;
            state.user = null;
            this.updateUI();
            return false;
        }

        try {
            const response = await fetch(`${API_BASE}/auth/me`, {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const data = await response.json();

            if (data.success) {
                state.isAuthenticated = true;
                state.user = data.data.user;
                state.quota = data.data.quota;
                localStorage.setItem(USER_KEY, JSON.stringify(data.data.user));
                this.updateUI();
                return true;
            } else {
                this.logout();
                return false;
            }
        } catch (e) {
            console.error('Auth check failed:', e);
            return false;
        }
    },

    async logout() {
        const token = this.getToken();
        if (token) {
            try {
                await fetch(`${API_BASE}/auth/logout`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${token}` }
                });
            } catch (e) {}
        }

        localStorage.removeItem(TOKEN_KEY);
        localStorage.removeItem(USER_KEY);
        state.isAuthenticated = false;
        state.user = null;
        state.quota = null;
        document.cookie = 'auth_token=; path=/; max-age=0';

        window.location.href = BASE_PATH + '/auth';
    },

    updateUI() {
        const userMenu = document.getElementById('user-menu');
        const loginBtn = document.getElementById('login-btn');
        const quotaBar = document.getElementById('quota-bar');
        const trialBanner = document.getElementById('trial-banner');
        const planBadge = document.getElementById('plan-badge');

        if (state.isAuthenticated && state.user) {
            const company = state.user.company || {};

            // Afficher le menu utilisateur
            if (userMenu) {
                userMenu.classList.add('visible');
                const userName = userMenu.querySelector('.user-name');
                if (userName) {
                    userName.textContent = state.user.first_name || state.user.email.split('@')[0];
                }
            }
            if (loginBtn) loginBtn.style.display = 'none';

            // Badge plan
            if (planBadge) {
                if (company.trial_active) {
                    planBadge.textContent = `PRO - ${company.days_remaining}j`;
                    planBadge.className = 'plan-badge trial';
                } else if (company.plan === 'pro' || company.plan === 'equipe') {
                    planBadge.textContent = company.plan === 'equipe' ? 'ÉQUIPE' : 'PRO';
                    planBadge.className = 'plan-badge pro';
                } else {
                    planBadge.textContent = 'DÉCOUVERTE';
                    planBadge.className = 'plan-badge free';
                }
                planBadge.style.display = '';
            }

            // Afficher la barre de quota si plan découverte et pas en trial
            if (quotaBar) {
                if (company.plan === 'decouverte' && !company.trial_active) {
                    quotaBar.classList.add('visible');
                    const fill = quotaBar.querySelector('.quota-fill');
                    const text = quotaBar.querySelector('.quota-text');
                    const used = company.quotes_this_month || 0;
                    const limit = 10;
                    const percentage = (used / limit) * 100;

                    if (fill) fill.style.width = `${Math.min(100, percentage)}%`;
                    if (text) text.textContent = `${used}/${limit} devis ce mois`;

                    // Alerte si proche de la limite
                    quotaBar.classList.toggle('warning', used >= 8);
                } else {
                    quotaBar.classList.remove('visible');
                }
            }

            // Afficher le bandeau d'essai si applicable
            if (trialBanner) {
                if (company.trial_active) {
                    trialBanner.classList.add('visible');
                    const days = trialBanner.querySelector('.trial-days');
                    if (days) days.textContent = company.days_remaining;
                } else {
                    trialBanner.classList.remove('visible');
                }
            }
        } else {
            // Masquer les éléments auth
            if (userMenu) userMenu.classList.remove('visible');
            if (loginBtn) loginBtn.style.display = '';
            if (quotaBar) quotaBar.classList.remove('visible');
            if (trialBanner) trialBanner.classList.remove('visible');
            if (planBadge) planBadge.style.display = 'none';
        }
    },

    getAuthHeader() {
        const token = this.getToken();
        return token ? { 'Authorization': `Bearer ${token}` } : {};
    }
};

// === Éléments DOM ===
const elements = {
    // Formulaire
    form: document.getElementById('quoteForm'),
    description: document.getElementById('description'),
    charCount: document.getElementById('charCount'),

    // Étapes
    step1: document.getElementById('step1'),
    step2: document.getElementById('step2'),
    stepsIndicator: document.querySelectorAll('.step'),
    nextStepBtn: document.getElementById('nextStepBtn'),
    prevStepBtn: document.getElementById('prevStepBtn'),
    editClientBtn: document.getElementById('editClientBtn'),
    clientSummaryText: document.getElementById('clientSummaryText'),

    // Client
    clientCivilite: document.getElementById('clientCivilite'),
    clientNom: document.getElementById('clientNom'),
    clientPrenom: document.getElementById('clientPrenom'),
    clientSociete: document.getElementById('clientSociete'),
    clientEmail: document.getElementById('clientEmail'),
    clientTelephone: document.getElementById('clientTelephone'),
    clientAdresse: document.getElementById('clientAdresse'),
    clientCodePostal: document.getElementById('clientCodePostal'),
    clientVille: document.getElementById('clientVille'),

    // Chantier
    chantierDifferent: document.getElementById('chantierDifferent'),
    chantierFields: document.getElementById('chantierFields'),
    chantierAdresse: document.getElementById('chantierAdresse'),
    chantierCodePostal: document.getElementById('chantierCodePostal'),
    chantierVille: document.getElementById('chantierVille'),

    // Audio travaux
    recordBtn: document.getElementById('recordBtn'),
    stopBtn: document.getElementById('stopBtn'),
    recordingStatus: document.getElementById('recordingStatus'),
    recordingTime: document.querySelector('.recording-time'),
    transcriptionResult: document.getElementById('transcriptionResult'),
    transcriptionText: document.getElementById('transcriptionText'),
    appendTranscriptionBtn: document.getElementById('appendTranscriptionBtn'),
    resetTranscriptionBtn: document.getElementById('resetTranscriptionBtn'),

    // Images
    addImageBtn: document.getElementById('addImageBtn'),
    imageInput: document.getElementById('imageInput'),
    imagePreview: document.getElementById('imagePreview'),

    // Actions
    generateBtn: document.getElementById('generateBtn'),
    loadingState: document.getElementById('loadingState'),
    newQuoteBtn: document.getElementById('newQuoteBtn'),

    // Résultats
    resultSection: document.getElementById('resultSection'),
    quoteTitle: document.getElementById('quoteTitle'),
    quoteRef: document.getElementById('quoteRef'),
    pdfLink: document.getElementById('pdfLink'),
    saveQuoteBtn: document.getElementById('saveQuoteBtn'),
    chantierPerimetre: document.getElementById('chantierPerimetre'),
    chantierHypotheses: document.getElementById('chantierHypotheses'),
    questionsCard: document.getElementById('questionsCard'),
    questionsList: document.getElementById('questionsList'),
    tachesList: document.getElementById('tachesList'),
    lignesTable: document.getElementById('lignesTable').querySelector('tbody'),
    totalHT: document.getElementById('totalHT'),
    tauxTVA: document.getElementById('tauxTVA'),
    totalTVA: document.getElementById('totalTVA'),
    totalTTC: document.getElementById('totalTTC'),
    exclusionsCard: document.getElementById('exclusionsCard'),
    exclusionsList: document.getElementById('exclusionsList'),

    // PWA
    installBtn: document.getElementById('installBtn'),
    toastContainer: document.getElementById('toastContainer'),

    // Modal édition
    editModal: document.getElementById('editLineModal'),
    modalTitle: document.getElementById('modalTitle'),
    editLineForm: document.getElementById('editLineForm'),
    editLineIndex: document.getElementById('editLineIndex'),
    editCategorie: document.getElementById('editCategorie'),
    editDesignation: document.getElementById('editDesignation'),
    editQuantite: document.getElementById('editQuantite'),
    editUnite: document.getElementById('editUnite'),
    editPrixUnitaire: document.getElementById('editPrixUnitaire'),
    editTotalLigne: document.getElementById('editTotalLigne'),
    closeModalBtn: document.getElementById('closeModalBtn'),
    cancelEditBtn: document.getElementById('cancelEditBtn'),
    saveLineBtn: document.getElementById('saveLineBtn'),
    addLineBtn: document.getElementById('addLineBtn'),

    // Enregistrement modal
    modalRecordBtn: document.getElementById('modalRecordBtn'),
    modalRecordingStatus: document.getElementById('modalRecordingStatus'),
    modalRecordingTime: document.querySelector('.modal-recording-time'),
    modalStopBtn: document.getElementById('modalStopBtn')
};

// État pour l'enregistrement dans la modale
const modalRecordingState = {
    isRecording: false,
    mediaRecorder: null,
    audioChunks: [],
    recordingStartTime: null,
    recordingTimer: null
};

// === Initialisation ===
document.addEventListener('DOMContentLoaded', init);

async function init() {
    // Vérifier l'authentification
    await Auth.checkAuth();

    setupEventListeners();
    setupAuthUI();
    setupPWA();
    registerServiceWorker();

    // Vérifier si on est en mode édition
    const urlParams = new URLSearchParams(window.location.search);
    const editId = urlParams.get('edit');

    console.log('[Chiffreo] Mode édition détecté:', editId);

    if (editId) {
        console.log('[Chiffreo] Chargement du devis:', editId);
        await loadQuoteForEdit(editId);
    }
}

function setupAuthUI() {
    // Bouton de connexion dans le header
    const loginBtn = document.getElementById('login-btn');
    if (loginBtn) {
        loginBtn.addEventListener('click', () => {
            window.location.href = BASE_PATH + '/auth';
        });
    }

    // Menu utilisateur
    const userMenuToggle = document.querySelector('.user-menu-toggle');
    const userDropdown = document.querySelector('.user-dropdown');
    if (userMenuToggle && userDropdown) {
        userMenuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('visible');
        });

        // Fermer le dropdown quand on clique ailleurs
        document.addEventListener('click', () => {
            userDropdown.classList.remove('visible');
        });
    }

    // Bouton déconnexion
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            Auth.logout();
        });
    }

    // Liens vers paramètres (avec BASE_PATH)
    const settingsLink = document.getElementById('settings-link');
    const quotesLink = document.getElementById('quotes-link');
    if (settingsLink) {
        settingsLink.href = BASE_PATH + '/settings';
    }
    if (quotesLink) {
        quotesLink.href = BASE_PATH + '/settings#quotes';
    }
}

function setupEventListeners() {
    // Compteur de caractères
    elements.description.addEventListener('input', () => {
        elements.charCount.textContent = elements.description.value.length;
    });

    // Navigation étapes
    elements.nextStepBtn.addEventListener('click', goToStep2);
    elements.prevStepBtn.addEventListener('click', goToStep1);
    elements.editClientBtn.addEventListener('click', goToStep1);

    // Chantier différent
    elements.chantierDifferent.addEventListener('change', toggleChantierFields);

    // Enregistrement audio travaux
    elements.recordBtn.addEventListener('click', toggleRecording);
    elements.stopBtn.addEventListener('click', stopRecording);

    // Gestion transcription
    elements.appendTranscriptionBtn.addEventListener('click', appendTranscription);
    elements.resetTranscriptionBtn.addEventListener('click', resetTranscription);

    // Images
    elements.addImageBtn.addEventListener('click', () => elements.imageInput.click());
    elements.imageInput.addEventListener('change', handleImageSelect);

    // Formulaire
    elements.form.addEventListener('submit', handleSubmit);

    // Nouveau devis
    elements.newQuoteBtn.addEventListener('click', resetForm);

    // Sauvegarde du devis (mode édition)
    if (elements.saveQuoteBtn) {
        elements.saveQuoteBtn.addEventListener('click', saveQuote);
    }

    // Édition lignes
    elements.addLineBtn.addEventListener('click', () => openEditModal(-1));
    elements.closeModalBtn.addEventListener('click', closeEditModal);
    elements.cancelEditBtn.addEventListener('click', closeEditModal);
    elements.saveLineBtn.addEventListener('click', saveLine);
    elements.editModal.querySelector('.modal-backdrop').addEventListener('click', closeEditModal);

    // Calcul temps réel dans la modale
    elements.editQuantite.addEventListener('input', updateModalTotal);
    elements.editPrixUnitaire.addEventListener('input', updateModalTotal);

    // Enregistrement vocal dans la modale
    elements.modalRecordBtn.addEventListener('click', toggleModalRecording);
    elements.modalStopBtn.addEventListener('click', stopModalRecording);
}

// === Enregistrement Audio ===
async function toggleRecording() {
    if (state.isRecording) {
        stopRecording();
    } else {
        await startRecording();
    }
}

async function startRecording() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });

        state.mediaRecorder = new MediaRecorder(stream, {
            mimeType: 'audio/webm;codecs=opus'
        });

        state.audioChunks = [];

        state.mediaRecorder.ondataavailable = (e) => {
            if (e.data.size > 0) {
                state.audioChunks.push(e.data);
            }
        };

        state.mediaRecorder.onstop = async () => {
            const audioBlob = new Blob(state.audioChunks, { type: 'audio/webm' });
            await transcribeAudio(audioBlob);

            // Arrêter les tracks
            stream.getTracks().forEach(track => track.stop());
        };

        state.mediaRecorder.start();
        state.isRecording = true;
        state.recordingStartTime = Date.now();

        // UI - masquer le bouton micro et afficher le status
        elements.recordBtn.classList.add('recording');
        elements.recordBtn.style.display = 'none';
        elements.recordingStatus.style.display = 'inline-flex';

        // Timer
        state.recordingTimer = setInterval(updateRecordingTime, 1000);

    } catch (error) {
        console.error('Erreur accès micro:', error);
        showToast('Impossible d\'accéder au microphone', 'error');
    }
}

function stopRecording() {
    if (state.mediaRecorder && state.isRecording) {
        state.mediaRecorder.stop();
        state.isRecording = false;

        // UI - réafficher le bouton micro et masquer le status
        elements.recordBtn.classList.remove('recording');
        elements.recordBtn.style.display = 'flex';
        elements.recordingStatus.style.display = 'none';

        clearInterval(state.recordingTimer);
    }
}

function updateRecordingTime() {
    const elapsed = Math.floor((Date.now() - state.recordingStartTime) / 1000);
    const minutes = Math.floor(elapsed / 60).toString().padStart(2, '0');
    const seconds = (elapsed % 60).toString().padStart(2, '0');
    elements.recordingTime.textContent = `${minutes}:${seconds}`;
}

async function transcribeAudio(audioBlob) {
    try {
        showLoading('Transcription en cours...');

        const formData = new FormData();
        formData.append('audio', audioBlob, 'recording.webm');

        const response = await fetch(BASE_PATH + '/api/transcribe', {
            method: 'POST',
            headers: Auth.getAuthHeader(),
            body: formData
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.error || 'Erreur de transcription');
        }

        // Mode compléter: concaténer au lieu de remplacer
        const wasAppendMode = state.appendMode;
        if (state.appendMode && state.transcription) {
            state.transcription = state.transcription + ' ' + result.data.text;
            state.appendMode = false; // Reset le mode
        } else {
            state.transcription = result.data.text;
        }

        // Afficher la transcription
        elements.transcriptionText.textContent = state.transcription;
        elements.transcriptionResult.style.display = 'block';

        // Mettre à jour le champ description
        elements.description.value = state.transcription;
        elements.charCount.textContent = state.transcription.length;

        hideLoading();
        showToast(wasAppendMode ? 'Transcription complétée' : 'Transcription terminée', 'success');

    } catch (error) {
        console.error('Erreur transcription:', error);
        state.appendMode = false; // Reset en cas d'erreur
        hideLoading();
        showToast(error.message, 'error');
    }
}

// === Gestion de la Transcription ===
function appendTranscription() {
    // Activer le mode append et démarrer l'enregistrement
    state.appendMode = true;
    toggleRecording();
}

function resetTranscription() {
    // Réinitialiser la transcription
    state.transcription = '';
    state.appendMode = false;

    // Masquer le résultat
    elements.transcriptionResult.style.display = 'none';
    elements.transcriptionText.textContent = '';

    // Vider le champ description
    elements.description.value = '';
    elements.charCount.textContent = '0';

    showToast('Transcription réinitialisée', 'success');
}

// === Navigation entre étapes ===
function goToStep1() {
    state.currentStep = 1;
    elements.step1.style.display = 'block';
    elements.step1.classList.add('active');
    elements.step2.style.display = 'none';
    elements.step2.classList.remove('active');
    updateStepsIndicator();
}

function goToStep2() {
    // Valider les champs obligatoires
    if (!elements.clientNom.value.trim()) {
        showToast('Le nom du client est obligatoire', 'error');
        elements.clientNom.focus();
        return;
    }
    if (!elements.clientTelephone.value.trim()) {
        showToast('Le téléphone est obligatoire', 'error');
        elements.clientTelephone.focus();
        return;
    }

    // Sauvegarder les données client dans l'état
    saveClientData();

    // Mettre à jour le résumé client
    updateClientSummary();

    // Passer à l'étape 2
    state.currentStep = 2;
    elements.step1.style.display = 'none';
    elements.step1.classList.remove('active');
    elements.step2.style.display = 'block';
    elements.step2.classList.add('active');
    updateStepsIndicator();
}

function updateStepsIndicator() {
    elements.stepsIndicator.forEach((step, index) => {
        const stepNum = index + 1;
        step.classList.remove('active', 'completed');
        if (stepNum === state.currentStep) {
            step.classList.add('active');
        } else if (stepNum < state.currentStep) {
            step.classList.add('completed');
        }
    });
}

function saveClientData() {
    state.client = {
        civilite: elements.clientCivilite.value,
        nom: elements.clientNom.value.trim(),
        prenom: elements.clientPrenom.value.trim(),
        societe: elements.clientSociete.value.trim(),
        email: elements.clientEmail.value.trim(),
        telephone: elements.clientTelephone.value.trim(),
        adresse: elements.clientAdresse.value.trim(),
        codePostal: elements.clientCodePostal.value.trim(),
        ville: elements.clientVille.value.trim()
    };

    if (elements.chantierDifferent.checked) {
        state.chantier = {
            adresse: elements.chantierAdresse.value.trim(),
            codePostal: elements.chantierCodePostal.value.trim(),
            ville: elements.chantierVille.value.trim()
        };
    } else {
        state.chantier = {
            adresse: state.client.adresse,
            codePostal: state.client.codePostal,
            ville: state.client.ville
        };
    }
}

function updateClientSummary() {
    const c = state.client;
    let summary = `${c.civilite} ${c.prenom} ${c.nom}`.trim();
    if (c.societe) {
        summary += ` (${c.societe})`;
    }
    if (c.telephone) {
        summary += ` • ${c.telephone}`;
    }
    if (c.ville) {
        summary += ` • ${c.ville}`;
    }
    elements.clientSummaryText.textContent = summary || 'Client non renseigné';
}

function toggleChantierFields() {
    elements.chantierFields.style.display = elements.chantierDifferent.checked ? 'block' : 'none';
}

// === Gestion des Images ===
function handleImageSelect(e) {
    const files = Array.from(e.target.files);

    // Limiter à 4 images
    const remaining = 4 - state.images.length;
    const toAdd = files.slice(0, remaining);

    toAdd.forEach(file => {
        if (file.size > 5 * 1024 * 1024) {
            showToast(`${file.name} est trop volumineux (max 5 MB)`, 'error');
            return;
        }

        state.images.push(file);
        renderImagePreview(file);
    });

    // Reset input
    e.target.value = '';

    // Masquer le bouton si 4 images
    if (state.images.length >= 4) {
        elements.addImageBtn.style.display = 'none';
    }
}

function renderImagePreview(file) {
    const reader = new FileReader();
    reader.onload = (e) => {
        const div = document.createElement('div');
        div.className = 'image-preview-item';
        div.innerHTML = `
            <img src="${e.target.result}" alt="Preview">
            <button type="button" class="remove-btn" data-name="${file.name}">
                <i class="ph ph-x"></i>
            </button>
        `;

        div.querySelector('.remove-btn').addEventListener('click', () => {
            state.images = state.images.filter(f => f.name !== file.name);
            div.remove();
            elements.addImageBtn.style.display = 'flex';
        });

        elements.imagePreview.appendChild(div);
    };
    reader.readAsDataURL(file);
}

// === Soumission du formulaire ===
async function handleSubmit(e) {
    e.preventDefault();

    const description = elements.description.value.trim();
    const transcription = state.transcription;

    if (!description && !transcription) {
        showToast('Veuillez saisir une description ou dicter votre demande', 'error');
        return;
    }

    // Vérifier que les données client sont renseignées
    if (!state.client.nom) {
        showToast('Veuillez renseigner les informations client', 'error');
        goToStep1();
        return;
    }

    try {
        showLoading('Génération du devis...');
        elements.generateBtn.disabled = true;

        const formData = new FormData();
        formData.append('description', description);

        if (transcription) {
            formData.append('transcription', transcription);
        }

        // Données client (stockées en BDD, JAMAIS envoyées à l'IA)
        formData.append('client', JSON.stringify(state.client));

        // Données chantier
        formData.append('chantier', JSON.stringify(state.chantier));

        state.images.forEach(file => {
            formData.append('images[]', file);
        });

        const response = await fetch(BASE_PATH + '/api/generate', {
            method: 'POST',
            headers: Auth.getAuthHeader(),
            body: formData
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.error || 'Erreur lors de la génération');
        }

        state.currentQuote = result.data;
        renderQuoteResult(result.data);

        // Activer le mode édition immédiatement après création
        // pour que les modifications soient auto-sauvegardées
        if (result.data.id) {
            state.editMode = true;
            state.editQuoteId = result.data.id;
            state.hasChanges = false;
            console.log('[Chiffreo] Mode édition activé pour devis ID:', result.data.id);
        }

        hideLoading();
        elements.resultSection.style.display = 'block';
        if (elements.saveQuoteBtn) {
            elements.saveQuoteBtn.style.display = 'flex';
        }
        elements.resultSection.scrollIntoView({ behavior: 'smooth' });

        showToast('Devis généré avec succès', 'success');

    } catch (error) {
        console.error('Erreur génération:', error);
        hideLoading();
        showToast(error.message, 'error');
    } finally {
        elements.generateBtn.disabled = false;
    }
}

// === Affichage des résultats ===
function renderQuoteResult(data) {
    const quote = data.quote;

    // En-tête
    elements.quoteTitle.textContent = quote.chantier?.titre || 'Devis travaux électriques';
    elements.quoteRef.textContent = `Réf: ${data.reference}`;
    elements.pdfLink.href = BASE_PATH + data.pdf_url;

    // Chantier
    elements.chantierPerimetre.textContent = quote.chantier?.perimetre || '';

    elements.chantierHypotheses.innerHTML = '';
    (quote.chantier?.hypotheses || []).forEach(hyp => {
        const span = document.createElement('span');
        span.textContent = hyp;
        elements.chantierHypotheses.appendChild(span);
    });

    // Questions
    if (quote.questions_a_poser?.length > 0) {
        elements.questionsCard.style.display = 'block';
        elements.questionsList.innerHTML = '';
        quote.questions_a_poser.forEach(q => {
            const li = document.createElement('li');
            li.textContent = q.question;
            elements.questionsList.appendChild(li);
        });
    } else {
        elements.questionsCard.style.display = 'none';
    }

    // Tâches
    elements.tachesList.innerHTML = '';
    (quote.taches || []).forEach(tache => {
        const div = document.createElement('div');
        div.className = 'tache-item';
        div.innerHTML = `
            <span class="tache-num">${tache.ordre}</span>
            <div class="tache-content">
                <h4>${escapeHtml(tache.titre)}</h4>
                <p>${escapeHtml(tache.details)}</p>
                <span class="tache-duration">${tache.duree_estimee_h}h estimées</span>
            </div>
        `;
        elements.tachesList.appendChild(div);
    });

    // Lignes de devis
    elements.lignesTable.innerHTML = '';

    // Grouper par catégorie
    const categories = {
        materiel: { label: 'FOURNITURES', items: [] },
        main_oeuvre: { label: 'MAIN D\'ŒUVRE', items: [] },
        forfait: { label: 'FORFAITS', items: [] }
    };

    (quote.lignes || []).forEach(ligne => {
        if (categories[ligne.categorie]) {
            categories[ligne.categorie].items.push(ligne);
        }
    });

    Object.values(categories).forEach(cat => {
        if (cat.items.length === 0) return;

        // Ligne catégorie
        const catRow = document.createElement('tr');
        catRow.className = 'category-row';
        catRow.innerHTML = `<td colspan="5">${cat.label}</td>`;
        elements.lignesTable.appendChild(catRow);

        // Lignes
        cat.items.forEach(ligne => {
            const ligneIndex = (quote.lignes || []).indexOf(ligne);
            const tr = document.createElement('tr');
            tr.dataset.index = ligneIndex;
            tr.innerHTML = `
                <td>${escapeHtml(ligne.designation)}</td>
                <td class="right">${ligne.quantite}</td>
                <td class="right">${formatPrice(ligne.prix_unitaire_ht)}</td>
                <td class="right">${formatPrice(ligne.total_ligne_ht)}</td>
                <td class="actions-col">
                    <div class="row-actions">
                        <button type="button" class="btn-edit" title="Modifier">
                            <i class="ph ph-pencil-simple"></i>
                        </button>
                        <button type="button" class="btn-delete" title="Supprimer">
                            <i class="ph ph-trash"></i>
                        </button>
                    </div>
                </td>
            `;

            // Event listeners pour les boutons
            tr.querySelector('.btn-edit').addEventListener('click', () => openEditModal(ligneIndex));
            tr.querySelector('.btn-delete').addEventListener('click', () => deleteLine(ligneIndex));

            elements.lignesTable.appendChild(tr);
        });
    });

    // Totaux
    const totaux = quote.totaux || {};
    elements.totalHT.textContent = formatPrice(totaux.total_ht || 0);
    elements.tauxTVA.textContent = totaux.taux_tva || 20;
    elements.totalTVA.textContent = formatPrice(totaux.montant_tva || 0);
    elements.totalTTC.textContent = formatPrice(totaux.total_ttc || 0);

    // Exclusions
    if (quote.exclusions?.length > 0) {
        elements.exclusionsCard.style.display = 'block';
        elements.exclusionsList.innerHTML = '';
        quote.exclusions.forEach(excl => {
            const li = document.createElement('li');
            li.textContent = excl;
            elements.exclusionsList.appendChild(li);
        });
    } else {
        elements.exclusionsCard.style.display = 'none';
    }
}

function resetForm() {
    elements.form.reset();
    elements.charCount.textContent = '0';
    state.transcription = '';
    state.images = [];
    state.editMode = false;
    state.editQuoteId = null;
    state.hasChanges = false;

    elements.transcriptionResult.style.display = 'none';
    elements.imagePreview.innerHTML = '';
    elements.addImageBtn.style.display = 'flex';
    elements.resultSection.style.display = 'none';
    if (elements.saveQuoteBtn) {
        elements.saveQuoteBtn.style.display = 'none';
    }

    // Nettoyer l'URL
    const url = new URL(window.location);
    url.searchParams.delete('edit');
    window.history.replaceState({}, '', url);

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// === Mode Édition ===
async function loadQuoteForEdit(quoteId) {
    try {
        console.log('[Chiffreo] loadQuoteForEdit appelé avec ID:', quoteId);
        showLoading('Chargement du devis...');

        const url = `${BASE_PATH}/api/quote/${quoteId}`;
        console.log('[Chiffreo] Fetch URL:', url);

        const response = await fetch(url, {
            headers: Auth.getAuthHeader()
        });

        console.log('[Chiffreo] Réponse status:', response.status);
        const result = await response.json();
        console.log('[Chiffreo] Réponse data:', result);

        if (!result.success) {
            throw new Error(result.error || 'Devis non trouvé');
        }

        const data = result.data;

        // Activer le mode édition
        state.editMode = true;
        state.editQuoteId = parseInt(quoteId);
        state.hasChanges = false;

        // Charger les données client
        if (data.client) {
            // Parser le nom complet en prénom/nom si possible
            const fullName = data.client.nom || '';
            const nameParts = fullName.split(' ');

            state.client = {
                civilite: 'M.',
                nom: nameParts.length > 1 ? nameParts.slice(1).join(' ') : fullName,
                prenom: nameParts.length > 1 ? nameParts[0] : '',
                societe: data.client.societe || '',
                email: data.client.email || '',
                telephone: data.client.telephone || '',
                adresse: data.client.adresse || '',
                codePostal: '',
                ville: ''
            };

            // Peupler les champs client
            elements.clientNom.value = state.client.nom;
            elements.clientPrenom.value = state.client.prenom;
            elements.clientSociete.value = state.client.societe;
            elements.clientEmail.value = state.client.email;
            elements.clientTelephone.value = state.client.telephone;
            elements.clientAdresse.value = state.client.adresse;
        }

        // Charger les données chantier
        if (data.chantier) {
            state.chantier = {
                adresse: data.chantier.adresse || '',
                codePostal: data.chantier.codePostal || '',
                ville: data.chantier.ville || ''
            };

            if (data.chantier.adresse || data.chantier.ville) {
                elements.chantierDifferent.checked = true;
                elements.chantierFields.style.display = 'block';
                elements.chantierAdresse.value = state.chantier.adresse;
                elements.chantierCodePostal.value = state.chantier.codePostal;
                elements.chantierVille.value = state.chantier.ville;
            }
        }

        // Charger le devis
        state.currentQuote = {
            id: data.id,
            reference: data.reference,
            quote: data.quote,
            pdf_url: data.pdf_url
        };

        // Mettre à jour le résumé client
        updateClientSummary();

        // Afficher le résultat
        renderQuoteResult(state.currentQuote);

        // Masquer le formulaire, afficher la section résultat
        hideLoading();
        elements.form.style.display = 'none';
        elements.resultSection.style.display = 'block';
        if (elements.saveQuoteBtn) {
            elements.saveQuoteBtn.style.display = 'flex';
        }
        console.log('[Chiffreo] Mode édition activé, formulaire masqué, résultat affiché');

        // Mettre à jour le titre
        const sectionHeader = document.querySelector('.section-header h1');
        if (sectionHeader) {
            sectionHeader.textContent = 'Modifier le devis';
        }

        showToast('Devis chargé pour modification', 'success');

    } catch (error) {
        console.error('Erreur chargement devis:', error);
        hideLoading();
        showToast(error.message, 'error');

        // Rediriger vers les paramètres si le devis n'existe pas
        setTimeout(() => {
            window.location.href = BASE_PATH + '/settings#quotes';
        }, 2000);
    }
}

async function saveQuote() {
    if (!state.editMode || !state.editQuoteId) {
        showToast('Aucun devis à sauvegarder', 'error');
        return;
    }

    try {
        elements.saveQuoteBtn.disabled = true;
        elements.saveQuoteBtn.innerHTML = '<span class="loading-spinner" style="width:16px;height:16px;border-width:2px;"></span> Enregistrement...';

        const payload = {
            client: state.client,
            chantier: state.chantier,
            quote: state.currentQuote.quote
        };

        const response = await fetch(`${BASE_PATH}/api/quote/${state.editQuoteId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                ...Auth.getAuthHeader()
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.error || 'Erreur lors de la sauvegarde');
        }

        state.hasChanges = false;
        showToast('Devis enregistré avec succès', 'success');

    } catch (error) {
        console.error('Erreur sauvegarde:', error);
        showToast(error.message, 'error');
    } finally {
        elements.saveQuoteBtn.disabled = false;
        elements.saveQuoteBtn.innerHTML = '<i class="ph ph-floppy-disk"></i> Enregistrer';
    }
}

// Marquer le devis comme modifié après une action d'édition
// Et sauvegarder automatiquement avec un délai (debounce)
let autoSaveTimeout = null;

function markAsChanged() {
    if (state.editMode) {
        state.hasChanges = true;

        // Auto-save avec debounce de 1 seconde
        if (autoSaveTimeout) {
            clearTimeout(autoSaveTimeout);
        }
        autoSaveTimeout = setTimeout(() => {
            autoSaveQuote();
        }, 1000);
    }
}

// Sauvegarde automatique silencieuse
async function autoSaveQuote() {
    if (!state.editMode || !state.editQuoteId || !state.hasChanges) {
        return;
    }

    try {
        const payload = {
            client: state.client,
            chantier: state.chantier,
            quote: state.currentQuote.quote
        };

        const response = await fetch(`${BASE_PATH}/api/quote/${state.editQuoteId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                ...Auth.getAuthHeader()
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();
        if (data.success) {
            state.hasChanges = false;
            console.log('[Chiffreo] Auto-save réussi');
        } else {
            console.error('[Chiffreo] Auto-save échoué:', data.error);
        }
    } catch (error) {
        console.error('[Chiffreo] Erreur auto-save:', error);
    }
}

// === Utilitaires ===
function showLoading(message = 'Chargement...') {
    elements.form.style.display = 'none';
    elements.loadingState.style.display = 'block';
    elements.loadingState.querySelector('p').textContent = message;
}

function hideLoading() {
    elements.form.style.display = 'block';
    elements.loadingState.style.display = 'none';
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <i class="ph ph-${type === 'success' ? 'check-circle' : 'warning-circle'}"></i>
        <span>${escapeHtml(message)}</span>
    `;

    elements.toastContainer.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

function formatPrice(amount) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// === Enregistrement vocal dans la modale ===
async function toggleModalRecording() {
    if (modalRecordingState.isRecording) {
        stopModalRecording();
    } else {
        await startModalRecording();
    }
}

async function startModalRecording() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });

        modalRecordingState.mediaRecorder = new MediaRecorder(stream, {
            mimeType: 'audio/webm;codecs=opus'
        });

        modalRecordingState.audioChunks = [];

        modalRecordingState.mediaRecorder.ondataavailable = (e) => {
            if (e.data.size > 0) {
                modalRecordingState.audioChunks.push(e.data);
            }
        };

        modalRecordingState.mediaRecorder.onstop = async () => {
            const audioBlob = new Blob(modalRecordingState.audioChunks, { type: 'audio/webm' });
            await parseVoiceLine(audioBlob);
            stream.getTracks().forEach(track => track.stop());
        };

        modalRecordingState.mediaRecorder.start();
        modalRecordingState.isRecording = true;
        modalRecordingState.recordingStartTime = Date.now();

        // UI
        elements.modalRecordBtn.classList.add('recording');
        elements.modalRecordBtn.style.display = 'none';
        elements.modalRecordingStatus.style.display = 'inline-flex';

        // Timer
        modalRecordingState.recordingTimer = setInterval(updateModalRecordingTime, 1000);

    } catch (error) {
        console.error('Erreur accès micro:', error);
        showToast('Impossible d\'accéder au microphone', 'error');
    }
}

function stopModalRecording() {
    if (modalRecordingState.mediaRecorder && modalRecordingState.isRecording) {
        modalRecordingState.mediaRecorder.stop();
        modalRecordingState.isRecording = false;

        // UI
        elements.modalRecordBtn.classList.remove('recording');
        elements.modalRecordBtn.style.display = 'inline-flex';
        elements.modalRecordingStatus.style.display = 'none';

        clearInterval(modalRecordingState.recordingTimer);
    }
}

function updateModalRecordingTime() {
    const elapsed = Math.floor((Date.now() - modalRecordingState.recordingStartTime) / 1000);
    const minutes = Math.floor(elapsed / 60).toString().padStart(2, '0');
    const seconds = (elapsed % 60).toString().padStart(2, '0');
    elements.modalRecordingTime.textContent = `${minutes}:${seconds}`;
}

async function parseVoiceLine(audioBlob) {
    try {
        // Afficher un état de chargement dans la modale
        elements.modalRecordBtn.disabled = true;
        elements.modalRecordBtn.innerHTML = '<i class="ph ph-spinner"></i> <span>Analyse...</span>';

        const formData = new FormData();
        formData.append('audio', audioBlob, 'line_recording.webm');

        const response = await fetch(BASE_PATH + '/api/parse-line', {
            method: 'POST',
            headers: Auth.getAuthHeader(),
            body: formData
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.error || 'Erreur d\'analyse');
        }

        // Remplir les champs avec les données extraites
        const data = result.data;

        if (data.categorie) {
            elements.editCategorie.value = data.categorie;
        }
        if (data.designation) {
            elements.editDesignation.value = data.designation;
        }
        if (data.quantite) {
            elements.editQuantite.value = data.quantite;
        }
        if (data.unite) {
            elements.editUnite.value = data.unite;
        }
        if (data.prix_unitaire_ht) {
            elements.editPrixUnitaire.value = data.prix_unitaire_ht;
        }

        updateModalTotal();

        // Construire le message de confirmation avec les détails
        let toastMsg = 'Ligne analysée';
        const details = [];

        if (data.marque) {
            details.push(`Marque: ${data.marque}`);
        }
        if (data.reference) {
            details.push(`Réf: ${data.reference}`);
        }
        if (data.gamme) {
            const gammeLabels = { low: 'Entrée de gamme', mid: 'Milieu de gamme', high: 'Haut de gamme' };
            details.push(gammeLabels[data.gamme] || data.gamme);
        }
        if (data.prix_source === 'web') {
            details.push('Prix web');
        }

        if (details.length > 0) {
            toastMsg += ` (${details.join(' • ')})`;
        }

        showToast(toastMsg, 'success');

    } catch (error) {
        console.error('Erreur analyse ligne:', error);
        showToast(error.message, 'error');
    } finally {
        // Restaurer le bouton
        elements.modalRecordBtn.disabled = false;
        elements.modalRecordBtn.innerHTML = '<i class="ph ph-microphone"></i> <span>Dicter la ligne</span>';
    }
}

// === Édition des lignes ===
// Stocke les données originales pour détecter les changements de prix
let editingLineOriginal = null;

function openEditModal(index) {
    const isNew = index === -1;

    elements.modalTitle.textContent = isNew ? 'Ajouter une ligne' : 'Modifier la ligne';
    elements.editLineIndex.value = index;

    if (isNew) {
        // Nouvelle ligne - valeurs par défaut
        elements.editCategorie.value = 'materiel';
        elements.editDesignation.value = '';
        elements.editQuantite.value = '1';
        elements.editUnite.value = 'u';
        elements.editPrixUnitaire.value = '0';
        editingLineOriginal = null;
    } else {
        // Édition - charger les valeurs existantes
        const ligne = state.currentQuote.quote.lignes[index];
        elements.editCategorie.value = ligne.categorie || 'materiel';
        elements.editDesignation.value = ligne.designation || '';
        elements.editQuantite.value = ligne.quantite || 1;
        elements.editUnite.value = ligne.unite || 'u';
        elements.editPrixUnitaire.value = ligne.prix_unitaire_ht || 0;

        // Stocker les données originales pour détecter les changements de prix
        editingLineOriginal = {
            designation: ligne.designation,
            marque: ligne.marque || null,
            reference: ligne.reference || null,
            prix_unitaire_ht: ligne.prix_unitaire_ht || 0,
            categorie: ligne.categorie
        };
    }

    updateModalTotal();
    elements.editModal.style.display = 'flex';
    elements.editDesignation.focus();
}

function closeEditModal() {
    elements.editModal.style.display = 'none';
    elements.editLineForm.reset();
}

function updateModalTotal() {
    const quantite = parseFloat(elements.editQuantite.value) || 0;
    const prixUnitaire = parseFloat(elements.editPrixUnitaire.value) || 0;
    const total = quantite * prixUnitaire;
    elements.editTotalLigne.textContent = formatPrice(total);
}

function saveLine() {
    const index = parseInt(elements.editLineIndex.value);
    const isNew = index === -1;

    // Validation
    const designation = elements.editDesignation.value.trim();
    if (!designation) {
        showToast('Veuillez saisir une désignation', 'error');
        elements.editDesignation.focus();
        return;
    }

    const quantite = parseFloat(elements.editQuantite.value) || 0;
    const prixUnitaire = parseFloat(elements.editPrixUnitaire.value) || 0;

    if (quantite <= 0) {
        showToast('La quantité doit être supérieure à 0', 'error');
        elements.editQuantite.focus();
        return;
    }

    // Créer l'objet ligne
    const ligne = {
        categorie: elements.editCategorie.value,
        designation: designation,
        quantite: quantite,
        unite: elements.editUnite.value || 'u',
        prix_unitaire_ht: prixUnitaire,
        total_ligne_ht: quantite * prixUnitaire
    };

    // Ajouter ou modifier
    if (isNew) {
        if (!state.currentQuote.quote.lignes) {
            state.currentQuote.quote.lignes = [];
        }
        state.currentQuote.quote.lignes.push(ligne);
        showToast('Ligne ajoutée', 'success');
    } else {
        // Conserver marque et référence de la ligne originale
        if (editingLineOriginal) {
            ligne.marque = editingLineOriginal.marque;
            ligne.reference = editingLineOriginal.reference;
        }

        state.currentQuote.quote.lignes[index] = ligne;
        showToast('Ligne modifiée', 'success');

        // Si le prix a changé et c'est du matériel, enregistrer la correction
        if (editingLineOriginal &&
            editingLineOriginal.categorie === 'materiel' &&
            editingLineOriginal.prix_unitaire_ht !== prixUnitaire) {

            recordPriceCorrection(
                editingLineOriginal.marque,
                editingLineOriginal.reference,
                editingLineOriginal.designation,
                editingLineOriginal.prix_unitaire_ht,
                prixUnitaire
            );
        }
    }

    // Reset des données originales
    editingLineOriginal = null;

    // Marquer comme modifié en mode édition
    markAsChanged();

    // Recalculer et rafraîchir
    recalculateTotals();
    renderQuoteLines();
    closeEditModal();
}

// Enregistrer une correction de prix dans la grille
async function recordPriceCorrection(marque, reference, designation, prixInitial, prixCorrige) {
    // Si pas de marque, on ne peut pas enregistrer (trop générique)
    if (!marque && !reference) {
        console.log('[Chiffreo] Correction non enregistrée: pas de marque/référence');
        return;
    }

    try {
        const payload = {
            marque: marque || 'Générique',
            reference: reference,
            designation: designation,
            prix_initial: prixInitial,
            prix_corrige: prixCorrige,
            gamme: 'mid',
            quote_id: state.editQuoteId,
            source: 'user_correction'
        };

        const response = await fetch(`${BASE_PATH}/api/price-correction`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                ...Auth.getAuthHeader()
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();
        if (data.success) {
            console.log('[Chiffreo] Correction de prix enregistrée:', payload.designation);
            showToast('Prix corrigé et mémorisé pour les futurs devis', 'info');
        }
    } catch (error) {
        console.error('[Chiffreo] Erreur enregistrement correction:', error);
    }
}

function deleteLine(index) {
    if (!confirm('Supprimer cette ligne ?')) {
        return;
    }

    state.currentQuote.quote.lignes.splice(index, 1);

    // Marquer comme modifié en mode édition
    markAsChanged();

    recalculateTotals();
    renderQuoteLines();
    showToast('Ligne supprimée', 'success');
}

function recalculateTotals() {
    const lignes = state.currentQuote.quote.lignes || [];
    const tauxTVA = state.currentQuote.quote.totaux?.taux_tva || 20;

    // Calculer le total HT
    const totalHT = lignes.reduce((sum, ligne) => {
        return sum + (ligne.total_ligne_ht || 0);
    }, 0);

    // Calculer TVA et TTC
    const montantTVA = totalHT * (tauxTVA / 100);
    const totalTTC = totalHT + montantTVA;

    // Mettre à jour l'état
    if (!state.currentQuote.quote.totaux) {
        state.currentQuote.quote.totaux = {};
    }
    state.currentQuote.quote.totaux.total_ht = totalHT;
    state.currentQuote.quote.totaux.taux_tva = tauxTVA;
    state.currentQuote.quote.totaux.montant_tva = montantTVA;
    state.currentQuote.quote.totaux.total_ttc = totalTTC;

    // Mettre à jour l'affichage
    elements.totalHT.textContent = formatPrice(totalHT);
    elements.tauxTVA.textContent = tauxTVA;
    elements.totalTVA.textContent = formatPrice(montantTVA);
    elements.totalTTC.textContent = formatPrice(totalTTC);
}

function renderQuoteLines() {
    const quote = state.currentQuote.quote;

    // Vider le tableau
    elements.lignesTable.innerHTML = '';

    // Grouper par catégorie
    const categories = {
        materiel: { label: 'FOURNITURES', items: [] },
        main_oeuvre: { label: 'MAIN D\'ŒUVRE', items: [] },
        forfait: { label: 'FORFAITS', items: [] }
    };

    (quote.lignes || []).forEach((ligne, originalIndex) => {
        if (categories[ligne.categorie]) {
            categories[ligne.categorie].items.push({ ...ligne, originalIndex });
        }
    });

    Object.values(categories).forEach(cat => {
        if (cat.items.length === 0) return;

        // Ligne catégorie
        const catRow = document.createElement('tr');
        catRow.className = 'category-row';
        catRow.innerHTML = `<td colspan="5">${cat.label}</td>`;
        elements.lignesTable.appendChild(catRow);

        // Lignes
        cat.items.forEach(item => {
            const tr = document.createElement('tr');
            tr.dataset.index = item.originalIndex;
            tr.innerHTML = `
                <td>${escapeHtml(item.designation)}</td>
                <td class="right">${item.quantite}</td>
                <td class="right">${formatPrice(item.prix_unitaire_ht)}</td>
                <td class="right">${formatPrice(item.total_ligne_ht)}</td>
                <td class="actions-col">
                    <div class="row-actions">
                        <button type="button" class="btn-edit" title="Modifier">
                            <i class="ph ph-pencil-simple"></i>
                        </button>
                        <button type="button" class="btn-delete" title="Supprimer">
                            <i class="ph ph-trash"></i>
                        </button>
                    </div>
                </td>
            `;

            tr.querySelector('.btn-edit').addEventListener('click', () => openEditModal(item.originalIndex));
            tr.querySelector('.btn-delete').addEventListener('click', () => deleteLine(item.originalIndex));

            elements.lignesTable.appendChild(tr);
        });
    });
}

// === PWA ===
let deferredPrompt;

function setupPWA() {
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        elements.installBtn.style.display = 'flex';
    });

    elements.installBtn.addEventListener('click', async () => {
        if (!deferredPrompt) return;

        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;

        if (outcome === 'accepted') {
            elements.installBtn.style.display = 'none';
        }

        deferredPrompt = null;
    });

    window.addEventListener('appinstalled', () => {
        elements.installBtn.style.display = 'none';
        showToast('Application installée !', 'success');
    });
}

async function registerServiceWorker() {
    if ('serviceWorker' in navigator) {
        try {
            const registration = await navigator.serviceWorker.register(BASE_PATH + '/service-worker.js');
            console.log('Service Worker enregistré:', registration.scope);
        } catch (error) {
            console.error('Erreur Service Worker:', error);
        }
    }
}
