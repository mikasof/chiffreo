-- Migration: Seed product_prices with real references
-- Date: 2026-03-07
-- Sources: 123elec.com, idealo.fr, prix.net (Prix publics convertis HT)

-- ============================================
-- SCHNEIDER ODACE - Appareillage
-- ============================================
INSERT INTO product_prices (marque, reference, designation, prix_vente_ht, source, categorie, sous_categorie, gamme, fiabilite) VALUES
-- Schneider Odace Blanc
('Schneider', 'S520204', 'Interrupteur va et vient Odace blanc', 2.54, 'distributor', 'electricite', 'appareillage', 'mid', 85),
('Schneider', 'S520059', 'Prise de courant 2P+T Odace blanc', 2.04, 'distributor', 'electricite', 'appareillage', 'mid', 85),
('Schneider', 'S520052', 'Prise de courant 2P+T affleurante Odace blanc', 2.58, 'distributor', 'electricite', 'appareillage', 'mid', 85),
('Schneider', 'S520702', 'Plaque simple Odace blanc', 0.83, 'distributor', 'electricite', 'appareillage', 'mid', 85),
('Schneider', 'S520206', 'Bouton poussoir Odace blanc', 4.16, 'distributor', 'electricite', 'appareillage', 'mid', 85),
('Schneider', 'S520214', 'Interrupteur double va et vient Odace', 5.79, 'distributor', 'electricite', 'appareillage', 'mid', 85),
('Schneider', 'S520049', 'Prise de courant 2P+T renovation Odace', 2.49, 'distributor', 'electricite', 'appareillage', 'mid', 85),
('Schneider', 'S520033', 'Prise de courant 2P blanc a vis Odace', 3.67, 'distributor', 'electricite', 'appareillage', 'mid', 85),
('Schneider', 'S520089', 'Prise 2P+T + USB Type-C affleurante Odace', 15.75, 'distributor', 'electricite', 'appareillage', 'mid', 85),
('Schneider', 'S520662', 'Sortie de cable Odace blanc', 2.99, 'distributor', 'electricite', 'appareillage', 'mid', 85),
('Schneider', 'S520666', 'Obturateur Odace blanc', 2.67, 'distributor', 'electricite', 'appareillage', 'mid', 85),
('Schneider', 'S520524', 'Detecteur mouvement 2 fils renovation Odace', 44.33, 'distributor', 'electricite', 'appareillage', 'mid', 85),
('Schneider', 'S520523', 'Detecteur mouvement 3 fils Odace', 49.92, 'distributor', 'electricite', 'appareillage', 'mid', 85),
('Schneider', 'S520488', 'Prise haut-parleur Odace blanc', 9.16, 'distributor', 'electricite', 'appareillage', 'mid', 85),
('Schneider', 'S520462', 'Prise HDMI type A Odace blanc', 43.25, 'distributor', 'electricite', 'appareillage', 'mid', 85),
('Schneider', 'S520490', 'Prise RJ45 CPL 200MBP/S Odace', 20.79, 'distributor', 'electricite', 'appareillage', 'mid', 85),
('Schneider', 'S520401', 'Prise chargeur double USB A+C Odace', 19.92, 'distributor', 'electricite', 'appareillage', 'mid', 85),
('Schneider', 'S520243', 'Interrupteur VMC Odace blanc', 9.58, 'distributor', 'electricite', 'appareillage', 'mid', 85),
('Schneider', 'S520208', 'Interrupteur volets roulants Odace blanc', 12.50, 'distributor', 'electricite', 'appareillage', 'mid', 85),
('Schneider', 'S525052', 'Prise de courant 2P+T affleurante avec griffes Odace blanc', 3.50, 'distributor', 'electricite', 'appareillage', 'mid', 85),

-- Schneider Odace 2025 Blanc Craie
('Schneider', 'S920204', 'Interrupteur va et vient Odace 2025 blanc craie', 4.04, 'distributor', 'electricite', 'appareillage', 'high', 85),
('Schneider', 'S920052', 'Prise de courant 2P+T affleurante Odace 2025 blanc craie', 3.58, 'distributor', 'electricite', 'appareillage', 'high', 85),
('Schneider', 'S920702', 'Plaque simple Odace 2025 blanc craie', 1.38, 'distributor', 'electricite', 'appareillage', 'high', 85),

-- Schneider Odace Anthracite
('Schneider', 'S540204', 'Interrupteur va et vient Odace anthracite', 3.58, 'distributor', 'electricite', 'appareillage', 'mid', 85),
('Schneider', 'S540059', 'Prise de courant 2P+T Odace anthracite', 3.08, 'distributor', 'electricite', 'appareillage', 'mid', 85),
('Schneider', 'S540052', 'Prise de courant 2P+T affleurante Odace anthracite', 3.67, 'distributor', 'electricite', 'appareillage', 'mid', 85),
('Schneider', 'S540702', 'Plaque simple Odace anthracite', 1.65, 'distributor', 'electricite', 'appareillage', 'mid', 85),

-- Schneider Odace 2025 Noir Onyx
('Schneider', 'S940204', 'Interrupteur va et vient Odace 2025 noir onyx', 5.25, 'distributor', 'electricite', 'appareillage', 'high', 85),
('Schneider', 'S940203', 'Interrupteur va et vient a levier Odace 2025 noir onyx', 6.79, 'distributor', 'electricite', 'appareillage', 'high', 85),
('Schneider', 'S940702', 'Plaque simple Odace 2025 noir onyx', 1.96, 'distributor', 'electricite', 'appareillage', 'high', 85),
('Schneider', 'S940052', 'Prise de courant 2P+T affleurante Odace 2025 noir onyx', 4.75, 'distributor', 'electricite', 'appareillage', 'high', 85)
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- ============================================
-- LEGRAND - Protection modulaire
-- ============================================
INSERT INTO product_prices (marque, reference, designation, prix_vente_ht, source, categorie, sous_categorie, gamme, fiabilite) VALUES
-- Disjoncteurs DNX3
('Legrand', '406774', 'Disjoncteur DNX3 16A courbe C', 6.25, 'distributor', 'electricite', 'disjoncteur', 'high', 85),
('Legrand', '406775', 'Disjoncteur DNX3 20A courbe C', 6.25, 'distributor', 'electricite', 'disjoncteur', 'high', 85),
('Legrand', '406777', 'Disjoncteur DNX3 32A courbe C', 9.58, 'distributor', 'electricite', 'disjoncteur', 'high', 85),
('Legrand', '406771', 'Disjoncteur DNX3 2A courbe C', 8.33, 'distributor', 'electricite', 'disjoncteur', 'high', 85),
('Legrand', '406772', 'Disjoncteur DNX3 6A courbe C', 6.25, 'distributor', 'electricite', 'disjoncteur', 'high', 85),
('Legrand', '406773', 'Disjoncteur DNX3 10A courbe C', 6.25, 'distributor', 'electricite', 'disjoncteur', 'high', 85),
('Legrand', '406776', 'Disjoncteur DNX3 25A courbe C', 7.92, 'distributor', 'electricite', 'disjoncteur', 'high', 85),
('Legrand', '406778', 'Disjoncteur DNX3 40A courbe C', 15.00, 'distributor', 'electricite', 'disjoncteur', 'high', 85),

-- Disjoncteurs differentiels DX3
('Legrand', '410754', 'Disjoncteur differentiel DX3 20A 30mA type AC', 70.00, 'distributor', 'electricite', 'differentiel', 'high', 85),
('Legrand', '410755', 'Disjoncteur differentiel DX3 25A 30mA type AC', 70.00, 'distributor', 'electricite', 'differentiel', 'high', 85),
('Legrand', '410756', 'Disjoncteur differentiel DX3 32A 30mA type AC', 75.00, 'distributor', 'electricite', 'differentiel', 'high', 85),
('Legrand', '411631', 'Disjoncteur differentiel DX3 16A 30mA type A', 95.00, 'distributor', 'electricite', 'differentiel', 'high', 85),
('Legrand', '411632', 'Disjoncteur differentiel DX3 20A 30mA type A', 95.00, 'distributor', 'electricite', 'differentiel', 'high', 85),

-- Interrupteurs differentiels
('Legrand', '411611', 'Interrupteur differentiel DX3 40A 30mA type AC 2P', 35.00, 'distributor', 'electricite', 'differentiel', 'high', 85),
('Legrand', '411617', 'Interrupteur differentiel DX3 63A 30mA type AC 2P', 45.00, 'distributor', 'electricite', 'differentiel', 'high', 85),
('Legrand', '411638', 'Interrupteur differentiel DX3 40A 30mA type A 2P', 55.00, 'distributor', 'electricite', 'differentiel', 'high', 85),
('Legrand', '411639', 'Interrupteur differentiel DX3 63A 30mA type A 2P', 65.00, 'distributor', 'electricite', 'differentiel', 'high', 85),
('Legrand', '411650', 'Interrupteur differentiel DX3 40A 30mA type A-Si 2P', 85.00, 'distributor', 'electricite', 'differentiel', 'high', 85),

-- Appareillage Legrand Dooxie
('Legrand', '600001', 'Interrupteur va et vient Dooxie blanc', 4.50, 'distributor', 'electricite', 'appareillage', 'high', 80),
('Legrand', '600004', 'Double interrupteur Dooxie blanc', 8.50, 'distributor', 'electricite', 'appareillage', 'high', 80),
('Legrand', '600011', 'Prise de courant 2P+T Dooxie blanc', 4.20, 'distributor', 'electricite', 'appareillage', 'high', 80),
('Legrand', '600801', 'Plaque simple Dooxie blanc', 1.50, 'distributor', 'electricite', 'appareillage', 'high', 80),
('Legrand', '600802', 'Plaque double Dooxie blanc', 2.80, 'distributor', 'electricite', 'appareillage', 'high', 80),

-- Appareillage Legrand Celiane
('Legrand', '067001', 'Interrupteur va et vient Celiane', 8.50, 'distributor', 'electricite', 'appareillage', 'high', 80),
('Legrand', '067031', 'Prise de courant 2P+T Celiane', 8.00, 'distributor', 'electricite', 'appareillage', 'high', 80),
('Legrand', '068301', 'Plaque simple Celiane titane', 5.50, 'distributor', 'electricite', 'appareillage', 'high', 80)
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- ============================================
-- LEGRAND - Interphonie & Video
-- ============================================
INSERT INTO product_prices (marque, reference, designation, prix_vente_ht, source, categorie, sous_categorie, gamme, fiabilite) VALUES
('Legrand', '369220', 'Kit portier visiophone ecran miroir 7 pouces 2 fils', 195.00, 'distributor', 'interphonie', 'visiophone', 'high', 85),
('Legrand', '369320', 'Kit portier visiophone connecte Classe 100X', 320.00, 'distributor', 'interphonie', 'visiophone', 'high', 85),
('Legrand', '369110', 'Kit portier audio 2 fils', 85.00, 'distributor', 'interphonie', 'interphone', 'high', 85),
('Legrand', '369230', 'Kit visiophone 2 fils ecran 4.3 pouces', 150.00, 'distributor', 'interphonie', 'visiophone', 'high', 85),
('Legrand', '369580', 'Platine de rue video inox 2 fils', 120.00, 'distributor', 'interphonie', 'platine', 'high', 85),
('Legrand', '369100', 'Kit carillon filaire', 25.00, 'distributor', 'interphonie', 'sonnette', 'high', 80)
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- ============================================
-- SOMFY - Domotique
-- ============================================
INSERT INTO product_prices (marque, reference, designation, prix_vente_ht, source, categorie, sous_categorie, gamme, fiabilite) VALUES
-- Box et centrales
('Somfy', '1870595', 'Tahoma Switch box domotique connectee IO/RTS', 165.83, 'distributor', 'domotique', 'box', 'high', 85),
('Somfy', '1870755', 'Kit connectivite Tahoma', 57.50, 'distributor', 'domotique', 'box', 'high', 85),

-- Telecommandes
('Somfy', '1870880', 'Keygo IO Telecommande portail 4 canaux', 54.17, 'distributor', 'domotique', 'telecommande', 'high', 85),
('Somfy', '1870879', 'Keygo RTS Telecommande portail 4 canaux', 58.25, 'distributor', 'domotique', 'telecommande', 'high', 85),
('Somfy', '2401102', 'Smoove Origin telecommande murale volet 1 canal RTS', 58.25, 'distributor', 'domotique', 'telecommande', 'high', 85),
('Somfy', '1870648', 'Situo Telecommande variation RTS Pure 5 canaux', 108.25, 'distributor', 'domotique', 'telecommande', 'high', 85),
('Somfy', '1870647', 'Situo Telecommande variation RTS Pure 1 canal', 83.32, 'distributor', 'domotique', 'telecommande', 'high', 85),
('Somfy', '1870496', 'Situo Telecommande volets roulants RTS Pure 1 canal', 66.66, 'distributor', 'domotique', 'telecommande', 'high', 85),
('Somfy', '1870645', 'Situo Telecommande variation IO Pure 1 canal', 83.25, 'distributor', 'domotique', 'telecommande', 'high', 85),
('Somfy', '1870479', 'Situo Telecommande bi-radio IO et RTS 5 canaux', 108.25, 'distributor', 'domotique', 'telecommande', 'high', 85),
('Somfy', '1870495', 'Situo Telecommande centralisation volets RTS Pure 5 canaux', 87.46, 'distributor', 'domotique', 'telecommande', 'high', 85),

-- Micro-modules
('Somfy', '2401161', 'Micro-module radio eclairage 1 canal RTS', 52.92, 'distributor', 'domotique', 'micromodule', 'high', 85),
('Somfy', '2401162', 'Micro-module radio volet roulant 1 canal RTS', 62.42, 'distributor', 'domotique', 'micromodule', 'high', 85),
('Somfy', '2400583', 'Recepteur radio RTS 500W eclairage etanche', 91.67, 'distributor', 'domotique', 'recepteur', 'high', 85),

-- Capteurs
('Somfy', '2401220', 'Capteur de temperature exterieur Somfy', 83.29, 'distributor', 'domotique', 'capteur', 'high', 85),
('Somfy', '2401219', 'Capteur de soleil exterieur Somfy', 95.00, 'distributor', 'domotique', 'capteur', 'high', 85),

-- Motorisation volets
('Somfy', '2401529', 'Kit remplacement motorisation volet roulant radio IO 6Nm', 239.99, 'distributor', 'domotique', 'motorisation', 'high', 85),
('Somfy', '1240388', 'Kit remplacement moteur volet roulant radio RTS 10Nm', 191.63, 'distributor', 'domotique', 'motorisation', 'high', 85),
('Somfy', '2401581', 'SYNAPSIA 1000 IO Kit motorisation volets battants', 391.66, 'distributor', 'domotique', 'motorisation', 'high', 85)
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- ============================================
-- DELTA DORE - Thermostats
-- ============================================
INSERT INTO product_prices (marque, reference, designation, prix_vente_ht, source, categorie, sous_categorie, gamme, fiabilite) VALUES
('Delta Dore', 'TYBOX1137', 'Thermostat programmable sans fil Tybox 1137', 122.08, 'distributor', 'chauffage', 'thermostat', 'high', 80),
('Delta Dore', 'TYBOX117', 'Thermostat programmable filaire Tybox 117', 56.58, 'distributor', 'chauffage', 'thermostat', 'high', 80),
('Delta Dore', 'TYBOX127', 'Thermostat programmable sans fil Tybox 127', 81.00, 'distributor', 'chauffage', 'thermostat', 'high', 80),
('Delta Dore', 'TYBOX51', 'Thermostat ambiance Tybox 51', 37.21, 'distributor', 'chauffage', 'thermostat', 'high', 80),
('Delta Dore', 'TYBOX21', 'Thermostat ambiance Tybox 21', 35.74, 'distributor', 'chauffage', 'thermostat', 'high', 80),
('Delta Dore', 'TYBOX31', 'Thermostat ambiance digital Tybox 31', 51.16, 'distributor', 'chauffage', 'thermostat', 'high', 80),
('Delta Dore', 'TYBOX33', 'Thermostat programmable radio Tybox 33', 89.08, 'distributor', 'chauffage', 'thermostat', 'high', 80),
('Delta Dore', 'TYBOX5701FP', 'Gestionnaire energie fil pilote Tybox 5701 FP', 115.68, 'distributor', 'chauffage', 'gestionnaire', 'high', 80),
('Delta Dore', '6050636', 'Recepteur fil pilote RF6600 FP', 75.00, 'distributor', 'chauffage', 'recepteur', 'high', 80),
('Delta Dore', '6050634', 'Driver 620 pour PAC/Chaudiere', 120.00, 'distributor', 'chauffage', 'driver', 'high', 80)
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- ============================================
-- HAGER - Protection modulaire
-- ============================================
INSERT INTO product_prices (marque, reference, designation, prix_vente_ht, source, categorie, sous_categorie, gamme, fiabilite) VALUES
-- Disjoncteurs
('Hager', 'MFN716', 'Disjoncteur 16A courbe C 1P+N', 7.50, 'distributor', 'electricite', 'disjoncteur', 'high', 80),
('Hager', 'MFN720', 'Disjoncteur 20A courbe C 1P+N', 7.50, 'distributor', 'electricite', 'disjoncteur', 'high', 80),
('Hager', 'MFN732', 'Disjoncteur 32A courbe C 1P+N', 10.00, 'distributor', 'electricite', 'disjoncteur', 'high', 80),
('Hager', 'MFN710', 'Disjoncteur 10A courbe C 1P+N', 7.50, 'distributor', 'electricite', 'disjoncteur', 'high', 80),
('Hager', 'MFN706', 'Disjoncteur 6A courbe C 1P+N', 7.50, 'distributor', 'electricite', 'disjoncteur', 'high', 80),
('Hager', 'MFN702', 'Disjoncteur 2A courbe C 1P+N', 9.00, 'distributor', 'electricite', 'disjoncteur', 'high', 80),

-- Interrupteurs differentiels
('Hager', 'CDA743F', 'Interrupteur differentiel 40A 30mA type A 2P', 52.00, 'distributor', 'electricite', 'differentiel', 'high', 80),
('Hager', 'CDA763F', 'Interrupteur differentiel 63A 30mA type A 2P', 62.00, 'distributor', 'electricite', 'differentiel', 'high', 80),
('Hager', 'CDC742F', 'Interrupteur differentiel 40A 30mA type AC 2P', 32.00, 'distributor', 'electricite', 'differentiel', 'high', 80),
('Hager', 'CDC762F', 'Interrupteur differentiel 63A 30mA type AC 2P', 42.00, 'distributor', 'electricite', 'differentiel', 'high', 80)
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- ============================================
-- IRVE - Bornes de recharge
-- ============================================
INSERT INTO product_prices (marque, reference, designation, prix_vente_ht, source, categorie, sous_categorie, gamme, fiabilite) VALUES
-- Legrand Green'up
('Legrand', '090471', 'Prise Green Up Access 3.7kW', 65.00, 'distributor', 'irve', 'prise', 'high', 80),
('Legrand', '058003', 'Borne Green Up Premium 7.4kW monophasee', 650.00, 'distributor', 'irve', 'borne', 'high', 80),
('Legrand', '058004', 'Borne Green Up Premium 22kW triphasee', 950.00, 'distributor', 'irve', 'borne', 'high', 80),

-- Schneider EVlink
('Schneider', 'EVH2S7P04K', 'Borne EVlink Home 7.4kW T2', 700.00, 'distributor', 'irve', 'borne', 'high', 80),
('Schneider', 'EVH2S22P04K', 'Borne EVlink Home 22kW T2', 1050.00, 'distributor', 'irve', 'borne', 'high', 80),
('Schneider', 'EVB1A22P2RI', 'Borne EVlink Parking 22kW double T2', 1800.00, 'distributor', 'irve', 'borne', 'high', 80),

-- Hager Witty
('Hager', 'XEV1K07T2', 'Borne Witty Start 7kW T2', 600.00, 'distributor', 'irve', 'borne', 'high', 80),
('Hager', 'XEV1K22T2', 'Borne Witty Start 22kW T2', 900.00, 'distributor', 'irve', 'borne', 'high', 80),
('Hager', 'XEV1R07T2', 'Borne Witty Share 7kW T2 pilotable', 800.00, 'distributor', 'irve', 'borne', 'high', 80),
('Hager', 'XEV1R22T2', 'Borne Witty Share 22kW T2 pilotable', 1100.00, 'distributor', 'irve', 'borne', 'high', 80)
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- ============================================
-- CABLES ELECTRIQUES
-- ============================================
INSERT INTO product_prices (marque, reference, designation, prix_vente_ht, source, categorie, sous_categorie, gamme, fiabilite) VALUES
-- Cable R2V (prix au metre)
('Nexans', 'R2V-3G1.5', 'Cable R2V 3G1.5mm2 (metre)', 1.20, 'distributor', 'electricite', 'cable', 'mid', 75),
('Nexans', 'R2V-3G2.5', 'Cable R2V 3G2.5mm2 (metre)', 1.80, 'distributor', 'electricite', 'cable', 'mid', 75),
('Nexans', 'R2V-3G6', 'Cable R2V 3G6mm2 (metre)', 4.50, 'distributor', 'electricite', 'cable', 'mid', 75),
('Nexans', 'R2V-5G2.5', 'Cable R2V 5G2.5mm2 (metre)', 3.20, 'distributor', 'electricite', 'cable', 'mid', 75),
('Nexans', 'R2V-5G6', 'Cable R2V 5G6mm2 (metre)', 7.50, 'distributor', 'electricite', 'cable', 'mid', 75),
('Nexans', 'R2V-5G10', 'Cable R2V 5G10mm2 (metre)', 12.00, 'distributor', 'electricite', 'cable', 'mid', 75),

-- Fil H07VU (prix au metre)
('Nexans', 'H07VU-1.5', 'Fil H07VU 1.5mm2 (metre)', 0.35, 'distributor', 'electricite', 'fil', 'mid', 75),
('Nexans', 'H07VU-2.5', 'Fil H07VU 2.5mm2 (metre)', 0.55, 'distributor', 'electricite', 'fil', 'mid', 75),
('Nexans', 'H07VU-6', 'Fil H07VU 6mm2 (metre)', 1.20, 'distributor', 'electricite', 'fil', 'mid', 75),
('Nexans', 'H07VU-10', 'Fil H07VU 10mm2 (metre)', 2.00, 'distributor', 'electricite', 'fil', 'mid', 75),

-- Gaine ICTA
('Arnould', 'ICTA3420', 'Gaine ICTA D20 (100m)', 25.00, 'distributor', 'electricite', 'gaine', 'mid', 75),
('Arnould', 'ICTA3425', 'Gaine ICTA D25 (100m)', 35.00, 'distributor', 'electricite', 'gaine', 'mid', 75),
('Arnould', 'ICTA3432', 'Gaine ICTA D32 (50m)', 30.00, 'distributor', 'electricite', 'gaine', 'mid', 75)
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- ============================================
-- RADIATEURS ELECTRIQUES
-- ============================================
INSERT INTO product_prices (marque, reference, designation, prix_vente_ht, source, categorie, sous_categorie, gamme, fiabilite) VALUES
-- Atlantic
('Atlantic', '503110', 'Radiateur Nirvana Digital 1000W', 450.00, 'distributor', 'chauffage', 'radiateur', 'high', 75),
('Atlantic', '503115', 'Radiateur Nirvana Digital 1500W', 550.00, 'distributor', 'chauffage', 'radiateur', 'high', 75),
('Atlantic', '503120', 'Radiateur Nirvana Digital 2000W', 650.00, 'distributor', 'chauffage', 'radiateur', 'high', 75),
('Atlantic', 'F117-10', 'Radiateur Agilia Connecte 1000W', 380.00, 'distributor', 'chauffage', 'radiateur', 'high', 75),
('Atlantic', 'F117-15', 'Radiateur Agilia Connecte 1500W', 480.00, 'distributor', 'chauffage', 'radiateur', 'high', 75),
('Atlantic', 'F117-20', 'Radiateur Agilia Connecte 2000W', 580.00, 'distributor', 'chauffage', 'radiateur', 'high', 75),

-- Thermor
('Thermor', '490551', 'Radiateur Equateur 4 1000W', 420.00, 'distributor', 'chauffage', 'radiateur', 'high', 75),
('Thermor', '490556', 'Radiateur Equateur 4 1500W', 520.00, 'distributor', 'chauffage', 'radiateur', 'high', 75),
('Thermor', '490561', 'Radiateur Equateur 4 2000W', 620.00, 'distributor', 'chauffage', 'radiateur', 'high', 75),

-- Noirot
('Noirot', 'AXIOM1000', 'Radiateur Axiom Smart ECOcontrol 1000W', 550.00, 'distributor', 'chauffage', 'radiateur', 'high', 75),
('Noirot', 'AXIOM1500', 'Radiateur Axiom Smart ECOcontrol 1500W', 680.00, 'distributor', 'chauffage', 'radiateur', 'high', 75),
('Noirot', 'AXIOM2000', 'Radiateur Axiom Smart ECOcontrol 2000W', 800.00, 'distributor', 'chauffage', 'radiateur', 'high', 75),

-- Gamme economique
('Cayenne', 'RAD1000', 'Radiateur inertie ceramique 1000W', 180.00, 'distributor', 'chauffage', 'radiateur', 'low', 70),
('Cayenne', 'RAD1500', 'Radiateur inertie ceramique 1500W', 220.00, 'distributor', 'chauffage', 'radiateur', 'low', 70),
('Cayenne', 'RAD2000', 'Radiateur inertie ceramique 2000W', 280.00, 'distributor', 'chauffage', 'radiateur', 'low', 70)
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- ============================================
-- PLOMBERIE - Robinetterie
-- ============================================
INSERT INTO product_prices (marque, reference, designation, prix_vente_ht, source, categorie, sous_categorie, gamme, fiabilite) VALUES
-- Grohe
('Grohe', '23445001', 'Mitigeur lavabo Eurosmart', 85.00, 'distributor', 'plomberie', 'mitigeur', 'high', 75),
('Grohe', '33265002', 'Mitigeur douche Eurosmart', 95.00, 'distributor', 'plomberie', 'mitigeur', 'high', 75),
('Grohe', '34558001', 'Mitigeur thermostatique douche Grohtherm 800', 180.00, 'distributor', 'plomberie', 'thermostatique', 'high', 75),
('Grohe', '34569001', 'Mitigeur thermostatique bain/douche Grohtherm 800', 220.00, 'distributor', 'plomberie', 'thermostatique', 'high', 75),

-- Jacob Delafon
('Jacob Delafon', 'E71922', 'Mitigeur lavabo Kumin', 75.00, 'distributor', 'plomberie', 'mitigeur', 'high', 75),
('Jacob Delafon', 'E71958', 'Mitigeur douche Kumin', 85.00, 'distributor', 'plomberie', 'mitigeur', 'high', 75),

-- Gamme economique
('Ideal Standard', 'BC586AA', 'Mitigeur lavabo CeraPlan', 55.00, 'distributor', 'plomberie', 'mitigeur', 'mid', 70),
('Ideal Standard', 'BC587AA', 'Mitigeur douche CeraPlan', 65.00, 'distributor', 'plomberie', 'mitigeur', 'mid', 70)
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- ============================================
-- VMC
-- ============================================
INSERT INTO product_prices (marque, reference, designation, prix_vente_ht, source, categorie, sous_categorie, gamme, fiabilite) VALUES
('Atlantic', 'DUOLIX', 'VMC Double flux Duolix Max', 1200.00, 'distributor', 'electricite', 'vmc', 'high', 75),
('Atlantic', 'AUTOCOSY', 'VMC Simple flux Autocosy IH Flex', 180.00, 'distributor', 'electricite', 'vmc', 'high', 75),
('Aldes', 'DEE FLY CUBE', 'VMC Double flux Dee Fly Cube 300', 1500.00, 'distributor', 'electricite', 'vmc', 'high', 75),
('Aldes', 'BAHIA OPTIMA', 'VMC Simple flux hygro B Bahia Optima', 220.00, 'distributor', 'electricite', 'vmc', 'high', 75),
('Unelvent', 'PULSIVE', 'VMC Simple flux Pulsive Ventil', 150.00, 'distributor', 'electricite', 'vmc', 'mid', 75)
ON DUPLICATE KEY UPDATE updated_at = NOW();

