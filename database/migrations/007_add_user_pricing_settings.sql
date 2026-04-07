-- ============================================
-- Chiffreo - Migration 007
-- Ajout paramètres de tarification utilisateur
-- ============================================

USE chiffreo;

-- Paramètres de tarification par utilisateur
ALTER TABLE users
    ADD COLUMN hourly_rate DECIMAL(10,2) DEFAULT 70.00 COMMENT 'Taux horaire main d''oeuvre' AFTER notifications_enabled,
    ADD COLUMN product_margin DECIMAL(5,2) DEFAULT 20.00 COMMENT 'Marge sur fournitures (%)' AFTER hourly_rate,
    ADD COLUMN travel_type ENUM('free', 'fixed', 'per_km') DEFAULT 'free' COMMENT 'Type facturation déplacement' AFTER product_margin,
    ADD COLUMN travel_fixed_amount DECIMAL(10,2) DEFAULT 30.00 COMMENT 'Forfait déplacement' AFTER travel_type,
    ADD COLUMN travel_per_km DECIMAL(5,2) DEFAULT 0.50 COMMENT 'Prix au km' AFTER travel_fixed_amount,
    ADD COLUMN travel_free_radius INT DEFAULT 20 COMMENT 'Rayon gratuit en km' AFTER travel_per_km;
