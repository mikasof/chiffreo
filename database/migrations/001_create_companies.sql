-- ============================================
-- Chiffreo - Migration 001
-- Table entreprises (companies)
-- ============================================

USE chiffreo;

-- Supprimer les anciennes tables (toutes vides)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS user_activity_log;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS push_subscriptions;
DROP TABLE IF EXISTS user_sessions;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS attachments;
DROP TABLE IF EXISTS quote_items;
DROP TABLE IF EXISTS quotes;
DROP TABLE IF EXISTS clients;
DROP TABLE IF EXISTS logs;
DROP TABLE IF EXISTS invitations;
DROP TABLE IF EXISTS companies;
SET FOREIGN_KEY_CHECKS = 1;

-- Table entreprises
CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) DEFAULT NULL,
    siret VARCHAR(14) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    email_contact VARCHAR(255) DEFAULT NULL,
    address_line1 VARCHAR(255) DEFAULT NULL,
    address_line2 VARCHAR(255) DEFAULT NULL,
    postal_code VARCHAR(10) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    logo_path VARCHAR(255) DEFAULT NULL,

    -- Plan & billing
    plan ENUM('decouverte', 'pro', 'equipe') DEFAULT 'pro',
    trial_ends_at DATETIME DEFAULT NULL,

    -- Quotas (au niveau entreprise)
    quotes_this_month INT DEFAULT 0,
    quotes_month_reset DATE DEFAULT NULL,

    -- Préférences devis
    default_tva_rate DECIMAL(4,2) DEFAULT 20.00,
    quote_validity_days INT DEFAULT 30,

    -- Flags
    profile_completed BOOLEAN DEFAULT FALSE,

    -- Timestamps
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_plan (plan),
    INDEX idx_trial (trial_ends_at),
    INDEX idx_siret (siret)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
