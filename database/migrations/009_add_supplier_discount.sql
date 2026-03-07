-- Migration: Add supplier discount to users
-- Date: 2026-03-07
-- Description: Remise fournisseur (carte pro) - pourcentage de réduction sur les prix publics

ALTER TABLE users
    ADD COLUMN supplier_discount DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Remise fournisseur en % (ex: 30 = -30% sur prix public)';

-- Valeurs typiques:
-- 0 = pas de carte pro (prix public)
-- 20-30 = petite entreprise
-- 30-40 = entreprise moyenne
-- 40-50 = gros volume
