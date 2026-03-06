-- ============================================
-- Chiffreo - Migration 002
-- Table utilisateurs (users)
-- ============================================

USE chiffreo;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,

    -- Identité
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) DEFAULT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,

    -- Rôle dans l'entreprise
    role ENUM('owner', 'admin', 'member') DEFAULT 'owner',

    -- Onboarding tracking (par utilisateur)
    onboarding_step TINYINT DEFAULT 0,
    onboarding_completed BOOLEAN DEFAULT FALSE,
    pwa_installed BOOLEAN DEFAULT FALSE,
    notifications_enabled BOOLEAN DEFAULT FALSE,
    first_quote_done BOOLEAN DEFAULT FALSE,

    -- Statut
    is_active BOOLEAN DEFAULT TRUE,
    email_verified_at DATETIME DEFAULT NULL,

    -- Timestamps
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login_at DATETIME DEFAULT NULL,

    -- Relations
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_email (email),
    INDEX idx_company (company_id),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
