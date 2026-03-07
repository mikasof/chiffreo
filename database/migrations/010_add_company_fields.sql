-- Migration: Add company/business fields to users
-- Date: 2026-03-07
-- Description: Informations légales de l'entreprise pour les devis

ALTER TABLE users
    ADD COLUMN company_name VARCHAR(255) DEFAULT NULL COMMENT 'Raison sociale',
    ADD COLUMN siret VARCHAR(14) DEFAULT NULL COMMENT 'Numéro SIRET (14 chiffres)',
    ADD COLUMN vat_number VARCHAR(20) DEFAULT NULL COMMENT 'Numéro TVA intracommunautaire',
    ADD COLUMN address_line1 VARCHAR(255) DEFAULT NULL COMMENT 'Adresse ligne 1',
    ADD COLUMN address_line2 VARCHAR(255) DEFAULT NULL COMMENT 'Adresse ligne 2',
    ADD COLUMN postal_code VARCHAR(10) DEFAULT NULL COMMENT 'Code postal',
    ADD COLUMN city VARCHAR(100) DEFAULT NULL COMMENT 'Ville',
    ADD COLUMN phone VARCHAR(20) DEFAULT NULL COMMENT 'Téléphone',
    ADD COLUMN insurance_name VARCHAR(255) DEFAULT NULL COMMENT 'Nom assurance décennale',
    ADD COLUMN insurance_number VARCHAR(100) DEFAULT NULL COMMENT 'Numéro police assurance',
    ADD COLUMN logo_path VARCHAR(255) DEFAULT NULL COMMENT 'Chemin vers le logo';

-- Index sur SIRET pour recherche
CREATE INDEX idx_users_siret ON users(siret);
