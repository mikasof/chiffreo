-- Migration: Add legal form, capital, RCS and insurance coverage fields
-- Date: 2026-03-07

-- Champs juridiques et assurance pour companies
ALTER TABLE companies
    ADD COLUMN legal_form VARCHAR(50) DEFAULT NULL COMMENT 'Forme juridique: EI, EIRL, Auto-entrepreneur, EURL, SARL, SAS, SASU, SA',
    ADD COLUMN capital VARCHAR(50) DEFAULT NULL COMMENT 'Capital social',
    ADD COLUMN rcs_number VARCHAR(50) DEFAULT NULL COMMENT 'Numéro RCS',
    ADD COLUMN rcs_city VARCHAR(100) DEFAULT NULL COMMENT 'Ville du greffe RCS',
    ADD COLUMN insurance_coverage VARCHAR(255) DEFAULT NULL COMMENT 'Couverture géographique assurance décennale';
