-- ============================================
-- Chiffreo - Migration 004
-- Table devis (quotes) et éléments
-- ============================================

USE chiffreo;

-- Table principale des devis
CREATE TABLE quotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT DEFAULT NULL,
    user_id INT DEFAULT NULL,

    -- Référence unique
    reference VARCHAR(20) NOT NULL UNIQUE,

    -- Infos client (stockées localement, jamais envoyées à l'IA)
    client_name VARCHAR(255) DEFAULT NULL,
    client_company VARCHAR(255) DEFAULT NULL,
    client_email VARCHAR(255) DEFAULT NULL,
    client_phone VARCHAR(20) DEFAULT NULL,
    client_address TEXT DEFAULT NULL,

    -- Infos chantier
    titre VARCHAR(255) DEFAULT NULL,
    localisation VARCHAR(255) DEFAULT NULL,
    perimetre TEXT DEFAULT NULL,
    chantier_adresse VARCHAR(255) DEFAULT NULL,
    chantier_code_postal VARCHAR(10) DEFAULT NULL,
    chantier_ville VARCHAR(100) DEFAULT NULL,

    -- Contenu original
    description_originale TEXT DEFAULT NULL,
    transcription_audio TEXT DEFAULT NULL,

    -- Réponse IA (JSON complet)
    ai_response JSON DEFAULT NULL,
    quote_data JSON DEFAULT NULL,

    -- Totaux
    total_ht DECIMAL(10,2) DEFAULT 0,
    total_tva DECIMAL(10,2) DEFAULT 0,
    total_ttc DECIMAL(10,2) DEFAULT 0,
    taux_tva DECIMAL(4,2) DEFAULT 20.00,

    -- Statut et validité
    status ENUM('draft', 'sent', 'accepted', 'refused', 'expired') DEFAULT 'draft',
    expires_at DATE DEFAULT NULL,

    -- PDF
    pdf_path VARCHAR(255) DEFAULT NULL,

    -- Timestamps
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_company (company_id),
    INDEX idx_user (user_id),
    INDEX idx_reference (reference),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des lignes de devis
CREATE TABLE quote_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_id INT NOT NULL,

    designation VARCHAR(500) NOT NULL,
    categorie VARCHAR(100) DEFAULT NULL,
    unite VARCHAR(20) DEFAULT NULL,
    quantite DECIMAL(10,2) DEFAULT 1,
    prix_unitaire_ht DECIMAL(10,2) DEFAULT 0,
    total_ht DECIMAL(10,2) DEFAULT 0,
    prix_ref_code VARCHAR(50) DEFAULT NULL,
    commentaire TEXT DEFAULT NULL,
    ordre INT DEFAULT 0,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE,
    INDEX idx_quote (quote_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des pièces jointes
CREATE TABLE attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_id INT NOT NULL,

    type ENUM('image', 'document', 'audio') DEFAULT 'image',
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100) DEFAULT NULL,
    file_size INT DEFAULT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE,
    INDEX idx_quote (quote_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des logs
CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level ENUM('DEBUG', 'INFO', 'WARNING', 'ERROR') DEFAULT 'INFO',
    action VARCHAR(100) NOT NULL,
    message TEXT DEFAULT NULL,
    context JSON DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_level (level),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
