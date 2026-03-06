-- ============================================
-- Chiffreo - Migration 006
-- Table invitations (préparation plan Équipe)
-- ============================================

USE chiffreo;

CREATE TABLE invitations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    invited_by INT NOT NULL,

    -- Destinataire
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,

    -- Rôle attribué
    role ENUM('admin', 'member') DEFAULT 'member',

    -- Statut
    status ENUM('pending', 'accepted', 'expired', 'cancelled') DEFAULT 'pending',

    -- Expiration
    expires_at DATETIME NOT NULL,

    -- Timestamps
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    accepted_at DATETIME DEFAULT NULL,

    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_email (email),
    INDEX idx_company (company_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
