-- Migration: Additional product prices for chauffage, domotique, plomberie
-- Date: 2026-03-07
-- Sources: sanitaire.fr, 123elec.com, idealo.fr, sites fabricants (Prix HT)

-- ============================================
-- CHAUFFAGE - Chauffe-eau
-- ============================================
INSERT INTO product_prices (marque, reference, designation, prix_vente_ht, source, categorie, sous_categorie, gamme, fiabilite) VALUES
-- Atlantic Zeneo
('Atlantic', '153110', 'Chauffe-eau electrique Zeneo 100L vertical mural', 380.00, 'distributor', 'chauffage', 'chauffe-eau', 'high', 80),
('Atlantic', '153115', 'Chauffe-eau electrique Zeneo 150L vertical mural', 450.00, 'distributor', 'chauffage', 'chauffe-eau', 'high', 80),
('Atlantic', '153120', 'Chauffe-eau electrique Zeneo 200L vertical mural', 520.00, 'distributor', 'chauffage', 'chauffe-eau', 'high', 80),
('Atlantic', '154125', 'Chauffe-eau electrique Zeneo 250L vertical socle', 650.00, 'distributor', 'chauffage', 'chauffe-eau', 'high', 80),
('Atlantic', '154130', 'Chauffe-eau electrique Zeneo 300L vertical socle', 750.00, 'distributor', 'chauffage', 'chauffe-eau', 'high', 80),
('Atlantic', '153150C', 'Chauffe-eau electrique Zeneo 150L compact', 550.00, 'distributor', 'chauffage', 'chauffe-eau', 'high', 80),
('Atlantic', '153200C', 'Chauffe-eau electrique Zeneo 200L compact', 620.00, 'distributor', 'chauffage', 'chauffe-eau', 'high', 80),

-- Atlantic Linéo
('Atlantic', '156310', 'Chauffe-eau thermodynamique Lineo 100L', 1450.00, 'distributor', 'chauffage', 'chauffe-eau-thermo', 'high', 80),
('Atlantic', '156315', 'Chauffe-eau thermodynamique Lineo 150L', 1650.00, 'distributor', 'chauffage', 'chauffe-eau-thermo', 'high', 80),
('Atlantic', '156320', 'Chauffe-eau thermodynamique Lineo 200L', 1850.00, 'distributor', 'chauffage', 'chauffe-eau-thermo', 'high', 80),

-- Thermor Duralis
('Thermor', 'DURALIS100', 'Chauffe-eau electrique Duralis 100L', 320.00, 'distributor', 'chauffage', 'chauffe-eau', 'high', 75),
('Thermor', 'DURALIS150', 'Chauffe-eau electrique Duralis 150L', 380.00, 'distributor', 'chauffage', 'chauffe-eau', 'high', 75),
('Thermor', 'DURALIS200', 'Chauffe-eau electrique Duralis 200L', 420.00, 'distributor', 'chauffage', 'chauffe-eau', 'high', 75),
('Thermor', 'DURALIS300', 'Chauffe-eau electrique Duralis 300L', 550.00, 'distributor', 'chauffage', 'chauffe-eau', 'high', 75),

-- Gamme economique
('Ariston', 'VELIS100', 'Chauffe-eau electrique Velis 100L plat', 450.00, 'distributor', 'chauffage', 'chauffe-eau', 'mid', 75),
('Ariston', 'PRO1-100V', 'Chauffe-eau electrique Pro1 100L', 220.00, 'distributor', 'chauffage', 'chauffe-eau', 'low', 75),
('Ariston', 'PRO1-150V', 'Chauffe-eau electrique Pro1 150L', 280.00, 'distributor', 'chauffage', 'chauffe-eau', 'low', 75),
('Ariston', 'PRO1-200V', 'Chauffe-eau electrique Pro1 200L', 320.00, 'distributor', 'chauffage', 'chauffe-eau', 'low', 75)
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- ============================================
-- CHAUFFAGE - Pompes à chaleur (prix installé indicatif)
-- ============================================
INSERT INTO product_prices (marque, reference, designation, prix_vente_ht, source, categorie, sous_categorie, gamme, fiabilite) VALUES
-- Atlantic Alfea Extensa (prix matériel seul)
('Atlantic', 'ALFEA-EXT-5', 'PAC Air/Eau Alfea Extensa AI 5kW R32', 5500.00, 'distributor', 'chauffage', 'pac-air-eau', 'high', 70),
('Atlantic', 'ALFEA-EXT-8', 'PAC Air/Eau Alfea Extensa AI 8kW R32', 6500.00, 'distributor', 'chauffage', 'pac-air-eau', 'high', 70),
('Atlantic', 'ALFEA-DUO-6', 'PAC Air/Eau Alfea Extensa Duo AI 6kW + ECS', 7500.00, 'distributor', 'chauffage', 'pac-air-eau', 'high', 70),
('Atlantic', 'ALFEA-DUO-10', 'PAC Air/Eau Alfea Extensa Duo AI 10kW + ECS', 9500.00, 'distributor', 'chauffage', 'pac-air-eau', 'high', 70),

-- Daikin Altherma
('Daikin', 'EHBH04E6V', 'PAC Air/Eau Altherma 3 4kW monobloc', 6800.00, 'distributor', 'chauffage', 'pac-air-eau', 'high', 70),
('Daikin', 'EHBH08E6V', 'PAC Air/Eau Altherma 3 8kW monobloc', 8500.00, 'distributor', 'chauffage', 'pac-air-eau', 'high', 70),
('Daikin', 'EHBH11E6V', 'PAC Air/Eau Altherma 3 11kW monobloc', 10500.00, 'distributor', 'chauffage', 'pac-air-eau', 'high', 70)
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- ============================================
-- DOMOTIQUE - Yokis Micromodules
-- ============================================
INSERT INTO product_prices (marque, reference, designation, prix_vente_ht, source, categorie, sous_categorie, gamme, fiabilite) VALUES
-- Yokis micromodules
('Yokis', 'MTV500E', 'Micromodule televariateur encastrable 500W', 32.92, 'distributor', 'domotique', 'micromodule', 'high', 85),
('Yokis', 'MTR2000E', 'Micromodule telerupteur encastrable 2000W', 28.50, 'distributor', 'domotique', 'micromodule', 'high', 85),
('Yokis', 'MTR500E', 'Micromodule telerupteur encastrable 500W', 26.00, 'distributor', 'domotique', 'micromodule', 'high', 85),
('Yokis', 'MVR500E', 'Micromodule volet roulant encastrable', 45.00, 'distributor', 'domotique', 'micromodule', 'high', 85),
('Yokis', 'E2BPP', 'Emetteur 2 canaux mural radio', 35.00, 'distributor', 'domotique', 'telecommande', 'high', 85),
('Yokis', 'TLC4CP', 'Telecommande 4 canaux porte-cles', 42.00, 'distributor', 'domotique', 'telecommande', 'high', 85),
('Yokis', 'YNO', 'Hub Yokis centralisation domotique', 180.00, 'distributor', 'domotique', 'box', 'high', 85),

-- Yokis modulaires
('Yokis', 'MTV500M', 'Televariateur modulaire 500W', 55.00, 'distributor', 'domotique', 'modulaire', 'high', 85),
('Yokis', 'MTR2000M', 'Telerupteur modulaire 2000W', 45.00, 'distributor', 'domotique', 'modulaire', 'high', 85),
('Yokis', 'MVR500M', 'Commande volet roulant modulaire', 65.00, 'distributor', 'domotique', 'modulaire', 'high', 85)
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- ============================================
-- DOMOTIQUE - Schneider Wiser
-- ============================================
INSERT INTO product_prices (marque, reference, designation, prix_vente_ht, source, categorie, sous_categorie, gamme, fiabilite) VALUES
('Schneider', 'CCTFR6400', 'Thermostat ambiance connecte Wiser Zigbee', 41.58, 'distributor', 'domotique', 'thermostat', 'high', 85),
('Schneider', 'CCTFR6700', 'Actionneur chauffage electrique Wiser 16A', 56.66, 'distributor', 'domotique', 'actionneur', 'high', 85),
('Schneider', 'CCTFR6905', 'Kit thermostat connecte Wiser radiateurs', 215.89, 'distributor', 'domotique', 'kit', 'high', 85),
('Schneider', 'CCT501801', 'Hub Wiser 2 gen Zigbee/WiFi', 95.00, 'distributor', 'domotique', 'box', 'high', 85),
('Schneider', 'CCT5010-0001', 'Tete thermostatique connectee Wiser', 55.00, 'distributor', 'domotique', 'thermostat', 'high', 85),
('Schneider', 'CCT5102-0002', 'Vanne connectee Wiser pour radiateur', 65.00, 'distributor', 'domotique', 'vanne', 'high', 85)
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- ============================================
-- DOMOTIQUE - Legrand Celiane Netatmo
-- ============================================
INSERT INTO product_prices (marque, reference, designation, prix_vente_ht, source, categorie, sous_categorie, gamme, fiabilite) VALUES
('Legrand', '067721', 'Interrupteur connecte Celiane Netatmo', 55.00, 'distributor', 'domotique', 'interrupteur', 'high', 80),
('Legrand', '067722', 'Interrupteur double connecte Celiane Netatmo', 75.00, 'distributor', 'domotique', 'interrupteur', 'high', 80),
('Legrand', '067725', 'Prise connectee Celiane Netatmo 16A', 65.00, 'distributor', 'domotique', 'prise', 'high', 80),
('Legrand', '067772', 'Commande sans fil Celiane Netatmo', 45.00, 'distributor', 'domotique', 'telecommande', 'high', 80),
('Legrand', '067740', 'Interrupteur volet roulant connecte Celiane', 85.00, 'distributor', 'domotique', 'volet', 'high', 80),
('Legrand', '067777', 'Gateway starter pack Celiane Netatmo', 180.00, 'distributor', 'domotique', 'box', 'high', 80),
('Legrand', '067781', 'Micromodule eclairage connecte Netatmo', 50.00, 'distributor', 'domotique', 'micromodule', 'high', 80),
('Legrand', '067782', 'Micromodule volet roulant connecte Netatmo', 60.00, 'distributor', 'domotique', 'micromodule', 'high', 80)
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- ============================================
-- DOMOTIQUE - Delta Dore Tydom
-- ============================================
INSERT INTO product_prices (marque, reference, designation, prix_vente_ht, source, categorie, sous_categorie, gamme, fiabilite) VALUES
('Delta Dore', '6700103', 'Tydom Home box domotique', 150.00, 'distributor', 'domotique', 'box', 'high', 80),
('Delta Dore', '6351180', 'Micromodule eclairage X3D', 48.00, 'distributor', 'domotique', 'micromodule', 'high', 80),
('Delta Dore', '6351181', 'Micromodule volet roulant X3D', 55.00, 'distributor', 'domotique', 'micromodule', 'high', 80),
('Delta Dore', '6050638', 'Telecommande murale 4 canaux X3D', 40.00, 'distributor', 'domotique', 'telecommande', 'high', 80),
('Delta Dore', '6351128', 'Detecteur ouverture X3D', 35.00, 'distributor', 'domotique', 'detecteur', 'high', 80)
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- ============================================
-- PLOMBERIE - Geberit
-- ============================================
INSERT INTO product_prices (marque, reference, designation, prix_vente_ht, source, categorie, sous_categorie, gamme, fiabilite) VALUES
-- Bâti-supports
('Geberit', '111.300.00.5', 'Bati-support Duofix UP100 82cm', 180.00, 'distributor', 'plomberie', 'bati-support', 'high', 85),
('Geberit', '111.333.00.6', 'Bati-support Duofix UP320 autoportant Sigma12', 290.83, 'distributor', 'plomberie', 'bati-support', 'high', 85),
('Geberit', '111.796.00.1', 'Bati-support Duofix UP720 extra-plat 114cm', 380.00, 'distributor', 'plomberie', 'bati-support', 'high', 85),
('Geberit', '111.902.00.5', 'Bati-support Duofix UP320 112cm', 250.00, 'distributor', 'plomberie', 'bati-support', 'high', 85),
('Geberit', '111.009.00.1', 'Bati-support Duofix 82cm faible hauteur', 320.00, 'distributor', 'plomberie', 'bati-support', 'high', 85),

-- Plaques de commande
('Geberit', '115.770.21.5', 'Plaque commande Sigma20 blanc/chrome', 65.00, 'distributor', 'plomberie', 'plaque-commande', 'high', 85),
('Geberit', '115.882.KJ.1', 'Plaque commande Sigma01 blanc', 45.00, 'distributor', 'plomberie', 'plaque-commande', 'high', 85),
('Geberit', '115.883.KJ.1', 'Plaque commande Sigma01 chrome', 55.00, 'distributor', 'plomberie', 'plaque-commande', 'high', 85),
('Geberit', '115.788.11.5', 'Plaque commande Sigma30 chrome mat', 95.00, 'distributor', 'plomberie', 'plaque-commande', 'high', 85),
('Geberit', '115.620.KJ.1', 'Plaque commande Delta51 blanc', 35.00, 'distributor', 'plomberie', 'plaque-commande', 'high', 85),

-- Accessoires
('Geberit', '152.438.46.1', 'Siphon de douche CleanLine 90mm', 85.00, 'distributor', 'plomberie', 'siphon', 'high', 85),
('Geberit', '154.050.00.1', 'Caniveau de douche CleanLine20 70cm', 280.00, 'distributor', 'plomberie', 'caniveau', 'high', 85),
('Geberit', '154.051.00.1', 'Caniveau de douche CleanLine20 90cm', 320.00, 'distributor', 'plomberie', 'caniveau', 'high', 85)
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- ============================================
-- PLOMBERIE - Hansgrohe
-- ============================================
INSERT INTO product_prices (marque, reference, designation, prix_vente_ht, source, categorie, sous_categorie, gamme, fiabilite) VALUES
-- Mitigeurs lavabo
('Hansgrohe', '71700000', 'Mitigeur lavabo Talis E 110', 120.00, 'distributor', 'plomberie', 'mitigeur', 'high', 80),
('Hansgrohe', '31607000', 'Mitigeur lavabo Focus 100', 95.00, 'distributor', 'plomberie', 'mitigeur', 'high', 80),
('Hansgrohe', '71020000', 'Mitigeur lavabo Logis 70', 65.00, 'distributor', 'plomberie', 'mitigeur', 'high', 80),
('Hansgrohe', '32310000', 'Mitigeur lavabo Metropol 110', 280.00, 'distributor', 'plomberie', 'mitigeur', 'high', 80),

-- Mitigeurs douche
('Hansgrohe', '31960000', 'Mitigeur douche Focus', 85.00, 'distributor', 'plomberie', 'mitigeur', 'high', 80),
('Hansgrohe', '71600000', 'Mitigeur douche Talis E', 110.00, 'distributor', 'plomberie', 'mitigeur', 'high', 80),
('Hansgrohe', '71760000', 'Mitigeur bain/douche Talis E', 145.00, 'distributor', 'plomberie', 'mitigeur', 'high', 80),

-- Thermostatiques
('Hansgrohe', '13114000', 'Mitigeur thermostatique douche Ecostat 1001 CL', 220.00, 'distributor', 'plomberie', 'thermostatique', 'high', 80),
('Hansgrohe', '13145000', 'Mitigeur thermostatique bain/douche Ecostat', 280.00, 'distributor', 'plomberie', 'thermostatique', 'high', 80),
('Hansgrohe', '15757000', 'Mitigeur thermostatique encastre ShowerSelect', 350.00, 'distributor', 'plomberie', 'thermostatique', 'high', 80),

-- Colonnes de douche
('Hansgrohe', '26721000', 'Colonne de douche Crometta S 240', 320.00, 'distributor', 'plomberie', 'colonne', 'high', 80),
('Hansgrohe', '27296000', 'Colonne de douche Raindance Select S', 650.00, 'distributor', 'plomberie', 'colonne', 'high', 80),
('Hansgrohe', '27633000', 'Colonne de douche Croma Select E 180', 420.00, 'distributor', 'plomberie', 'colonne', 'high', 80)
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- ============================================
-- PLOMBERIE - Grohe (complément)
-- ============================================
INSERT INTO product_prices (marque, reference, designation, prix_vente_ht, source, categorie, sous_categorie, gamme, fiabilite) VALUES
-- Colonnes de douche
('Grohe', '27296001', 'Colonne de douche Euphoria XXL 210', 380.00, 'distributor', 'plomberie', 'colonne', 'high', 80),
('Grohe', '26509000', 'Colonne de douche Tempesta 210', 280.00, 'distributor', 'plomberie', 'colonne', 'high', 80),
('Grohe', '27932001', 'Colonne de douche Rainshower System 310', 850.00, 'distributor', 'plomberie', 'colonne', 'high', 80),

-- Robinetterie cuisine
('Grohe', '30306000', 'Mitigeur evier Minta', 180.00, 'distributor', 'plomberie', 'cuisine', 'high', 80),
('Grohe', '31367000', 'Mitigeur evier Eurosmart avec douchette', 150.00, 'distributor', 'plomberie', 'cuisine', 'high', 80),
('Grohe', '32168000', 'Mitigeur evier K7 semi-pro', 450.00, 'distributor', 'plomberie', 'cuisine', 'high', 80),

-- WC
('Grohe', '39462000', 'Pack WC suspendu Euro Ceramic sans bride', 280.00, 'distributor', 'plomberie', 'wc', 'high', 80),
('Grohe', '39554000', 'Cuvette WC suspendue Essence sans bride', 380.00, 'distributor', 'plomberie', 'wc', 'high', 80)
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- ============================================
-- PLOMBERIE - Gamme économique
-- ============================================
INSERT INTO product_prices (marque, reference, designation, prix_vente_ht, source, categorie, sous_categorie, gamme, fiabilite) VALUES
-- Wirquin
('Wirquin', 'D750126', 'Siphon lavabo chrome', 12.00, 'distributor', 'plomberie', 'siphon', 'mid', 75),
('Wirquin', 'D752139', 'Siphon evier PVC', 8.00, 'distributor', 'plomberie', 'siphon', 'mid', 75),
('Wirquin', 'D751236', 'Bonde lavabo clic-clac', 18.00, 'distributor', 'plomberie', 'bonde', 'mid', 75),
('Wirquin', 'D755890', 'Mecanisme WC double chasse 3/6L', 25.00, 'distributor', 'plomberie', 'mecanisme', 'mid', 75),

-- Nicoll
('Nicoll', '71102', 'Siphon douche extra-plat 90mm', 35.00, 'distributor', 'plomberie', 'siphon', 'mid', 75),
('Nicoll', 'YTON', 'Pipe WC rigide D100', 8.50, 'distributor', 'plomberie', 'pipe', 'mid', 75),
('Nicoll', 'YTCE', 'Pipe WC souple extensible', 12.00, 'distributor', 'plomberie', 'pipe', 'mid', 75),

-- Alimentation
('Somatherm', 'FLEXALIM12', 'Flexible alimentation 12x17 50cm inox', 5.50, 'distributor', 'plomberie', 'flexible', 'mid', 75),
('Somatherm', 'FLEXALIM34', 'Flexible alimentation 20x27 80cm inox', 8.50, 'distributor', 'plomberie', 'flexible', 'mid', 75),
('Comap', 'ROBINET12', 'Robinet arret equerre 1/2 chrome', 8.00, 'distributor', 'plomberie', 'robinet', 'mid', 75),
('Comap', 'ROBINET34', 'Robinet arret equerre 3/4 chrome', 12.00, 'distributor', 'plomberie', 'robinet', 'mid', 75)
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- ============================================
-- INTERPHONIE - Compléments
-- ============================================
INSERT INTO product_prices (marque, reference, designation, prix_vente_ht, source, categorie, sous_categorie, gamme, fiabilite) VALUES
-- Aiphone
('Aiphone', 'JOS-1A', 'Kit visiophone couleur 7 pouces', 350.00, 'distributor', 'interphonie', 'visiophone', 'high', 80),
('Aiphone', 'JO-1MD', 'Moniteur supplementaire JO 7 pouces', 180.00, 'distributor', 'interphonie', 'moniteur', 'high', 80),
('Aiphone', 'DB-1MD', 'Platine video de rue saillie', 120.00, 'distributor', 'interphonie', 'platine', 'high', 80),
('Aiphone', 'JF-2MED', 'Kit audio 2 fils', 150.00, 'distributor', 'interphonie', 'interphone', 'high', 80),

-- Urmet
('Urmet', '1750/16', 'Kit video couleur Miro 7 pouces', 280.00, 'distributor', 'interphonie', 'visiophone', 'high', 80),
('Urmet', '1750/41', 'Platine video Miro antivandale', 95.00, 'distributor', 'interphonie', 'platine', 'high', 80),
('Urmet', '1783/3', 'Combine audio Atlantico', 45.00, 'distributor', 'interphonie', 'interphone', 'mid', 80),

-- Extel (entrée de gamme)
('Extel', 'LEVO', 'Visiophone couleur Levo 7 pouces', 95.00, 'distributor', 'interphonie', 'visiophone', 'low', 75),
('Extel', 'LESLI', 'Interphone audio Lesli 2 fils', 45.00, 'distributor', 'interphonie', 'interphone', 'low', 75),
('Extel', 'CONNECT', 'Visiophone connecte WiFi 7 pouces', 180.00, 'distributor', 'interphonie', 'visiophone', 'mid', 75)
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- ============================================
-- ALARME - Delta Dore Tyxal+
-- ============================================
INSERT INTO product_prices (marque, reference, designation, prix_vente_ht, source, categorie, sous_categorie, gamme, fiabilite) VALUES
('Delta Dore', '6410176', 'Pack alarme Tyxal+ Access', 350.00, 'distributor', 'domotique', 'alarme', 'high', 80),
('Delta Dore', '6410177', 'Pack alarme Tyxal+ Promo', 550.00, 'distributor', 'domotique', 'alarme', 'high', 80),
('Delta Dore', '6412301', 'Sirene interieure Tyxal+', 85.00, 'distributor', 'domotique', 'alarme', 'high', 80),
('Delta Dore', '6412302', 'Sirene exterieure Tyxal+', 150.00, 'distributor', 'domotique', 'alarme', 'high', 80),
('Delta Dore', '6412312', 'Detecteur mouvement Tyxal+ DMB', 65.00, 'distributor', 'domotique', 'detecteur', 'high', 80),
('Delta Dore', '6412308', 'Detecteur ouverture Tyxal+ DO', 45.00, 'distributor', 'domotique', 'detecteur', 'high', 80),
('Delta Dore', '6413252', 'Telecommande Tyxal+ TL2000', 40.00, 'distributor', 'domotique', 'telecommande', 'high', 80),
('Delta Dore', '6413261', 'Clavier exterieur Tyxal+', 120.00, 'distributor', 'domotique', 'clavier', 'high', 80)
ON DUPLICATE KEY UPDATE updated_at = NOW();

