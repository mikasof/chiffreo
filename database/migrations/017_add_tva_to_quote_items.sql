-- ============================================
-- Chiffreo - Migration 017
-- Ajout du taux TVA par ligne de devis
-- ============================================

USE chiffreo;

-- Ajouter la colonne taux_tva à quote_items
ALTER TABLE quote_items
ADD COLUMN taux_tva DECIMAL(4,2) DEFAULT 20.00 AFTER total_ht;
