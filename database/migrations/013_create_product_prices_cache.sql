-- Migration: Create product prices cache table
-- Date: 2026-03-07

CREATE TABLE IF NOT EXISTS product_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    marque VARCHAR(100) NOT NULL COMMENT 'Marque du produit',
    reference VARCHAR(100) NOT NULL COMMENT 'Référence produit',
    designation VARCHAR(500) DEFAULT NULL COMMENT 'Désignation complète',
    prix_achat_ht DECIMAL(10,2) DEFAULT NULL COMMENT 'Prix achat HT estimé',
    prix_vente_ht DECIMAL(10,2) NOT NULL COMMENT 'Prix vente HT',
    source VARCHAR(50) DEFAULT 'estimated' COMMENT 'Source: estimated, web, manual',
    url_source VARCHAR(500) DEFAULT NULL COMMENT 'URL source si trouvé sur le web',
    categorie VARCHAR(50) DEFAULT 'materiel' COMMENT 'Catégorie: materiel, domotique, chauffage, plomberie',
    sous_categorie VARCHAR(100) DEFAULT NULL COMMENT 'Sous-catégorie plus précise',
    unite VARCHAR(20) DEFAULT 'u' COMMENT 'Unité de vente',
    gamme ENUM('low', 'mid', 'high') DEFAULT 'mid' COMMENT 'Gamme de prix',
    fiabilite TINYINT DEFAULT 50 COMMENT 'Score de fiabilité 0-100',
    search_count INT DEFAULT 1 COMMENT 'Nombre de fois recherché',
    last_verified_at TIMESTAMP NULL COMMENT 'Dernière vérification du prix',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_product (marque, reference, gamme),
    INDEX idx_marque (marque),
    INDEX idx_reference (reference),
    INDEX idx_categorie (categorie),
    INDEX idx_search (marque, reference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les recherches échouées (éviter de rechercher plusieurs fois)
CREATE TABLE IF NOT EXISTS product_search_failures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    search_query VARCHAR(500) NOT NULL COMMENT 'Requête de recherche',
    marque VARCHAR(100) DEFAULT NULL,
    reference VARCHAR(100) DEFAULT NULL,
    failure_reason VARCHAR(255) DEFAULT NULL COMMENT 'Raison de l échec',
    retry_after TIMESTAMP NULL COMMENT 'Ne pas réessayer avant cette date',
    attempts INT DEFAULT 1 COMMENT 'Nombre de tentatives',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_query (search_query(255)),
    INDEX idx_retry (retry_after)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
