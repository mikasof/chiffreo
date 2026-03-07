-- Migration: Create price corrections table (user feedback on prices)
-- Date: 2026-03-07

-- Corrections de prix par les utilisateurs
-- Permet d'apprendre des modifications manuelles
CREATE TABLE IF NOT EXISTS price_corrections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    quote_id INT DEFAULT NULL COMMENT 'Devis concerné si applicable',

    -- Identification du produit
    marque VARCHAR(100) NOT NULL,
    reference VARCHAR(100) DEFAULT NULL,
    designation VARCHAR(500) NOT NULL,
    categorie VARCHAR(50) DEFAULT 'materiel',

    -- Prix
    prix_initial_ht DECIMAL(10,2) NOT NULL COMMENT 'Prix proposé par le système',
    prix_corrige_ht DECIMAL(10,2) NOT NULL COMMENT 'Prix corrigé par l utilisateur',
    gamme ENUM('low', 'mid', 'high') DEFAULT 'mid',

    -- Métadonnées
    source_initiale VARCHAR(50) DEFAULT NULL COMMENT 'Source du prix initial (gpt_estimate, price_grid, cache)',
    commentaire TEXT DEFAULT NULL COMMENT 'Commentaire utilisateur optionnel',

    -- Validation
    validated BOOLEAN DEFAULT FALSE COMMENT 'Correction validée pour mise à jour du modèle',
    validated_by INT DEFAULT NULL COMMENT 'Admin qui a validé',
    validated_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE SET NULL,
    INDEX idx_product (marque, reference),
    INDEX idx_user (user_id),
    INDEX idx_validated (validated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajouter champs marque/reference aux lignes de devis
-- (pour tracking dans quote_data JSON, pas besoin de colonnes SQL)
