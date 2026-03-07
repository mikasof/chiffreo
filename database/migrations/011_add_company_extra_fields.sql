-- Migration: Add extra company fields and user default_tier
-- Date: 2026-03-07

-- Champs additionnels pour companies
ALTER TABLE companies
    ADD COLUMN IF NOT EXISTS vat_number VARCHAR(20) DEFAULT NULL COMMENT 'Numéro TVA intracommunautaire',
    ADD COLUMN IF NOT EXISTS insurance_name VARCHAR(255) DEFAULT NULL COMMENT 'Nom assurance décennale',
    ADD COLUMN IF NOT EXISTS insurance_number VARCHAR(100) DEFAULT NULL COMMENT 'Numéro police assurance';

-- Gamme de prix par défaut pour users
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS default_tier VARCHAR(10) DEFAULT 'mid' COMMENT 'Gamme par défaut: low, mid, high';
