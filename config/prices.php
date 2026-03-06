<?php

/**
 * Grille des prix unitaires MVP
 * Structure: 3 niveaux de gamme pour chaque produit
 * - price_low: Entrée de gamme / économique
 * - price_mid: Milieu de gamme (utilisé par défaut)
 * - price_high: Haut de gamme / premium
 */

return [
    // === MAIN D'OEUVRE ===
    'MO_H' => [
        'label' => 'Main d\'oeuvre (heure)',
        'unit' => 'h',
        'price_low' => 35.00,
        'price_mid' => 45.00,
        'price_high' => 55.00,
        'category' => 'main_oeuvre'
    ],
    'MO_DEPLACEMENT' => [
        'label' => 'Forfait déplacement',
        'unit' => 'forfait',
        'price_low' => 25.00,
        'price_mid' => 35.00,
        'price_high' => 50.00,
        'category' => 'main_oeuvre'
    ],
    'MO_MISE_EN_SERVICE' => [
        'label' => 'Mise en service et tests',
        'unit' => 'forfait',
        'price_low' => 60.00,
        'price_mid' => 80.00,
        'price_high' => 120.00,
        'category' => 'main_oeuvre'
    ],

    // === CÂBLAGE ===
    'CABLE_3G15' => [
        'label' => 'Câble R2V 3G1.5mm²',
        'unit' => 'm',
        'price_low' => 0.80,
        'price_mid' => 1.20,
        'price_high' => 1.60,
        'category' => 'materiel'
    ],
    'CABLE_3G25' => [
        'label' => 'Câble R2V 3G2.5mm²',
        'unit' => 'm',
        'price_low' => 1.20,
        'price_mid' => 1.80,
        'price_high' => 2.50,
        'category' => 'materiel'
    ],
    'CABLE_5G25' => [
        'label' => 'Câble R2V 5G2.5mm²',
        'unit' => 'm',
        'price_low' => 2.20,
        'price_mid' => 3.20,
        'price_high' => 4.50,
        'category' => 'materiel'
    ],
    'CABLE_3G6' => [
        'label' => 'Câble R2V 3G6mm²',
        'unit' => 'm',
        'price_low' => 2.50,
        'price_mid' => 3.50,
        'price_high' => 5.00,
        'category' => 'materiel'
    ],
    'CABLE_PTT' => [
        'label' => 'Câble téléphonique PTT 298',
        'unit' => 'm',
        'price_low' => 0.30,
        'price_mid' => 0.45,
        'price_high' => 0.65,
        'category' => 'materiel'
    ],
    'CABLE_SYT' => [
        'label' => 'Câble alarme SYT 4 paires',
        'unit' => 'm',
        'price_low' => 0.60,
        'price_mid' => 0.85,
        'price_high' => 1.20,
        'category' => 'materiel'
    ],
    'FOURREAU_TPC' => [
        'label' => 'Fourreau TPC rouge Ø40',
        'unit' => 'm',
        'price_low' => 1.80,
        'price_mid' => 2.50,
        'price_high' => 3.50,
        'category' => 'materiel'
    ],
    'GAINE_ICTA_20' => [
        'label' => 'Gaine ICTA Ø20',
        'unit' => 'm',
        'price_low' => 0.70,
        'price_mid' => 1.10,
        'price_high' => 1.50,
        'category' => 'materiel'
    ],

    // === APPAREILLAGE ===
    'PRISE_2PT' => [
        'label' => 'Prise 2P+T 16A',
        'unit' => 'u',
        'price_low' => 4.00,
        'price_mid' => 8.50,
        'price_high' => 18.00,
        'category' => 'materiel'
    ],
    'PRISE_DOUBLE' => [
        'label' => 'Double prise 2P+T 16A',
        'unit' => 'u',
        'price_low' => 8.00,
        'price_mid' => 15.00,
        'price_high' => 30.00,
        'category' => 'materiel'
    ],
    'INTER_SA' => [
        'label' => 'Interrupteur simple allumage',
        'unit' => 'u',
        'price_low' => 3.50,
        'price_mid' => 7.50,
        'price_high' => 15.00,
        'category' => 'materiel'
    ],
    'INTER_VV' => [
        'label' => 'Interrupteur va-et-vient',
        'unit' => 'u',
        'price_low' => 5.00,
        'price_mid' => 12.00,
        'price_high' => 22.00,
        'category' => 'materiel'
    ],
    'INTER_VR' => [
        'label' => 'Interrupteur volet roulant',
        'unit' => 'u',
        'price_low' => 10.00,
        'price_mid' => 18.00,
        'price_high' => 35.00,
        'category' => 'materiel'
    ],
    'BOITE_DERIV' => [
        'label' => 'Boîte de dérivation IP55',
        'unit' => 'u',
        'price_low' => 2.50,
        'price_mid' => 4.50,
        'price_high' => 8.00,
        'category' => 'materiel'
    ],
    'BOITE_ENCAST' => [
        'label' => 'Boîte d\'encastrement',
        'unit' => 'u',
        'price_low' => 0.60,
        'price_mid' => 1.20,
        'price_high' => 2.00,
        'category' => 'materiel'
    ],

    // === ÉCLAIRAGE ===
    'SPOT_LED' => [
        'label' => 'Spot LED encastrable',
        'unit' => 'u',
        'price_low' => 12.00,
        'price_mid' => 25.00,
        'price_high' => 45.00,
        'category' => 'materiel'
    ],
    'PLAFONNIER_LED' => [
        'label' => 'Plafonnier LED',
        'unit' => 'u',
        'price_low' => 25.00,
        'price_mid' => 45.00,
        'price_high' => 90.00,
        'category' => 'materiel'
    ],
    'REGLETTE_LED' => [
        'label' => 'Réglette LED 120cm',
        'unit' => 'u',
        'price_low' => 18.00,
        'price_mid' => 35.00,
        'price_high' => 65.00,
        'category' => 'materiel'
    ],
    'DETECTEUR_MVT' => [
        'label' => 'Détecteur de mouvement',
        'unit' => 'u',
        'price_low' => 15.00,
        'price_mid' => 28.00,
        'price_high' => 55.00,
        'category' => 'materiel'
    ],
    'HUBLOT_LED' => [
        'label' => 'Hublot LED extérieur',
        'unit' => 'u',
        'price_low' => 20.00,
        'price_mid' => 38.00,
        'price_high' => 70.00,
        'category' => 'materiel'
    ],

    // === TABLEAU ÉLECTRIQUE ===
    'TABLEAU_13M' => [
        'label' => 'Coffret 1 rangée 13 modules',
        'unit' => 'u',
        'price_low' => 25.00,
        'price_mid' => 45.00,
        'price_high' => 80.00,
        'category' => 'materiel'
    ],
    'TABLEAU_26M' => [
        'label' => 'Coffret 2 rangées 26 modules',
        'unit' => 'u',
        'price_low' => 45.00,
        'price_mid' => 75.00,
        'price_high' => 130.00,
        'category' => 'materiel'
    ],
    'TABLEAU_39M' => [
        'label' => 'Coffret 3 rangées 39 modules',
        'unit' => 'u',
        'price_low' => 70.00,
        'price_mid' => 110.00,
        'price_high' => 180.00,
        'category' => 'materiel'
    ],
    'DJ_10A' => [
        'label' => 'Disjoncteur 10A',
        'unit' => 'u',
        'price_low' => 6.00,
        'price_mid' => 12.00,
        'price_high' => 20.00,
        'category' => 'materiel'
    ],
    'DJ_16A' => [
        'label' => 'Disjoncteur 16A',
        'unit' => 'u',
        'price_low' => 6.00,
        'price_mid' => 12.00,
        'price_high' => 20.00,
        'category' => 'materiel'
    ],
    'DJ_20A' => [
        'label' => 'Disjoncteur 20A',
        'unit' => 'u',
        'price_low' => 7.00,
        'price_mid' => 14.00,
        'price_high' => 24.00,
        'category' => 'materiel'
    ],
    'DJ_32A' => [
        'label' => 'Disjoncteur 32A',
        'unit' => 'u',
        'price_low' => 10.00,
        'price_mid' => 18.00,
        'price_high' => 30.00,
        'category' => 'materiel'
    ],
    'ID_40A_30MA' => [
        'label' => 'Interrupteur diff. 40A 30mA type A',
        'unit' => 'u',
        'price_low' => 50.00,
        'price_mid' => 85.00,
        'price_high' => 140.00,
        'category' => 'materiel'
    ],
    'ID_63A_30MA' => [
        'label' => 'Interrupteur diff. 63A 30mA type A',
        'unit' => 'u',
        'price_low' => 60.00,
        'price_mid' => 95.00,
        'price_high' => 160.00,
        'category' => 'materiel'
    ],
    'PEIGNE' => [
        'label' => 'Peigne d\'alimentation horizontal',
        'unit' => 'u',
        'price_low' => 10.00,
        'price_mid' => 18.00,
        'price_high' => 30.00,
        'category' => 'materiel'
    ],
    'BORNIER_TERRE' => [
        'label' => 'Bornier de terre',
        'unit' => 'u',
        'price_low' => 4.00,
        'price_mid' => 8.00,
        'price_high' => 15.00,
        'category' => 'materiel'
    ],

    // === CONTRÔLE D'ACCÈS ===
    'DIGICODE_FIL' => [
        'label' => 'Digicode filaire',
        'unit' => 'u',
        'price_low' => 45.00,
        'price_mid' => 80.00,
        'price_high' => 150.00,
        'category' => 'materiel'
    ],
    'DIGICODE_RADIO' => [
        'label' => 'Digicode radio',
        'unit' => 'u',
        'price_low' => 60.00,
        'price_mid' => 95.00,
        'price_high' => 180.00,
        'category' => 'materiel'
    ],
    'VISIOPHONE' => [
        'label' => 'Visiophone 2 fils',
        'unit' => 'u',
        'price_low' => 80.00,
        'price_mid' => 140.00,
        'price_high' => 280.00,
        'category' => 'materiel'
    ],
    'INTERPHONE' => [
        'label' => 'Interphone audio',
        'unit' => 'u',
        'price_low' => 45.00,
        'price_mid' => 75.00,
        'price_high' => 140.00,
        'category' => 'materiel'
    ],
    'GACHE_ELEC' => [
        'label' => 'Gâche électrique 12V',
        'unit' => 'u',
        'price_low' => 30.00,
        'price_mid' => 55.00,
        'price_high' => 95.00,
        'category' => 'materiel'
    ],
    'VENTOUSE' => [
        'label' => 'Ventouse électromagnétique',
        'unit' => 'u',
        'price_low' => 45.00,
        'price_mid' => 75.00,
        'price_high' => 130.00,
        'category' => 'materiel'
    ],
    'ALIM_12V' => [
        'label' => 'Alimentation 12V 2A',
        'unit' => 'u',
        'price_low' => 12.00,
        'price_mid' => 22.00,
        'price_high' => 40.00,
        'category' => 'materiel'
    ],
    'BP_SORTIE' => [
        'label' => 'Bouton poussoir de sortie',
        'unit' => 'u',
        'price_low' => 6.00,
        'price_mid' => 12.00,
        'price_high' => 25.00,
        'category' => 'materiel'
    ],
    'BADGE_RFID' => [
        'label' => 'Badge RFID',
        'unit' => 'u',
        'price_low' => 3.00,
        'price_mid' => 6.00,
        'price_high' => 12.00,
        'category' => 'materiel'
    ],

    // === CONSOMMABLES ===
    'CONSOMMABLES' => [
        'label' => 'Consommables divers (connecteurs, vis, chevilles...)',
        'unit' => 'forfait',
        'price_low' => 15.00,
        'price_mid' => 25.00,
        'price_high' => 40.00,
        'category' => 'materiel'
    ],
    'CONSOMMABLES_RENOV' => [
        'label' => 'Consommables rénovation complète',
        'unit' => 'forfait',
        'price_low' => 150.00,
        'price_mid' => 300.00,
        'price_high' => 500.00,
        'category' => 'materiel'
    ],
    'WAGO' => [
        'label' => 'Bornes Wago (lot de 10)',
        'unit' => 'lot',
        'price_low' => 4.00,
        'price_mid' => 8.00,
        'price_high' => 15.00,
        'category' => 'materiel'
    ],

    // === GROS ÉQUIPEMENTS ===
    'BALLON_ECS_100L' => [
        'label' => 'Ballon eau chaude électrique 100L',
        'unit' => 'u',
        'price_low' => 180.00,
        'price_mid' => 280.00,
        'price_high' => 450.00,
        'category' => 'materiel'
    ],
    'BALLON_ECS_150L' => [
        'label' => 'Ballon eau chaude électrique 150L',
        'unit' => 'u',
        'price_low' => 220.00,
        'price_mid' => 350.00,
        'price_high' => 550.00,
        'category' => 'materiel'
    ],
    'BALLON_ECS_200L' => [
        'label' => 'Ballon eau chaude électrique 200L',
        'unit' => 'u',
        'price_low' => 280.00,
        'price_mid' => 420.00,
        'price_high' => 650.00,
        'category' => 'materiel'
    ],
    'BALLON_THERMO_200L' => [
        'label' => 'Chauffe-eau thermodynamique 200L',
        'unit' => 'u',
        'price_low' => 1200.00,
        'price_mid' => 1800.00,
        'price_high' => 2800.00,
        'category' => 'materiel'
    ],
    'RADIATEUR_1000W' => [
        'label' => 'Radiateur électrique 1000W',
        'unit' => 'u',
        'price_low' => 80.00,
        'price_mid' => 180.00,
        'price_high' => 450.00,
        'category' => 'materiel'
    ],
    'RADIATEUR_1500W' => [
        'label' => 'Radiateur électrique 1500W',
        'unit' => 'u',
        'price_low' => 100.00,
        'price_mid' => 220.00,
        'price_high' => 550.00,
        'category' => 'materiel'
    ],
    'RADIATEUR_2000W' => [
        'label' => 'Radiateur électrique 2000W',
        'unit' => 'u',
        'price_low' => 130.00,
        'price_mid' => 280.00,
        'price_high' => 700.00,
        'category' => 'materiel'
    ],
    'SECHE_SERVIETTE' => [
        'label' => 'Sèche-serviettes électrique',
        'unit' => 'u',
        'price_low' => 120.00,
        'price_mid' => 250.00,
        'price_high' => 500.00,
        'category' => 'materiel'
    ],
    'VMC_SIMPLE' => [
        'label' => 'VMC simple flux',
        'unit' => 'u',
        'price_low' => 80.00,
        'price_mid' => 150.00,
        'price_high' => 280.00,
        'category' => 'materiel'
    ],
    'VMC_HYGRO' => [
        'label' => 'VMC hygroréglable',
        'unit' => 'u',
        'price_low' => 180.00,
        'price_mid' => 320.00,
        'price_high' => 550.00,
        'category' => 'materiel'
    ],

    // === BORNES DE RECHARGE VÉHICULE ÉLECTRIQUE ===
    'BORNE_RECHARGE_7KW' => [
        'label' => 'Borne de recharge 7kW (monophasé)',
        'unit' => 'u',
        'price_low' => 400.00,
        'price_mid' => 650.00,
        'price_high' => 1200.00,
        'category' => 'materiel'
    ],
    'BORNE_RECHARGE_11KW' => [
        'label' => 'Borne de recharge 11kW (triphasé)',
        'unit' => 'u',
        'price_low' => 600.00,
        'price_mid' => 900.00,
        'price_high' => 1500.00,
        'category' => 'materiel'
    ],
    'BORNE_RECHARGE_22KW' => [
        'label' => 'Borne de recharge 22kW (triphasé)',
        'unit' => 'u',
        'price_low' => 900.00,
        'price_mid' => 1400.00,
        'price_high' => 2500.00,
        'category' => 'materiel'
    ],
    'WALLBOX' => [
        'label' => 'Wallbox murale avec câble',
        'unit' => 'u',
        'price_low' => 500.00,
        'price_mid' => 800.00,
        'price_high' => 1500.00,
        'category' => 'materiel'
    ],
    'PRISE_RENFORCEE' => [
        'label' => 'Prise renforcée Green\'Up / Mode 2',
        'unit' => 'u',
        'price_low' => 80.00,
        'price_mid' => 150.00,
        'price_high' => 250.00,
        'category' => 'materiel'
    ],
    'FORFAIT_POSE_BORNE' => [
        'label' => 'Pose et raccordement borne de recharge',
        'unit' => 'forfait',
        'price_low' => 300.00,
        'price_mid' => 500.00,
        'price_high' => 900.00,
        'category' => 'forfait'
    ],
    'PROTECTION_BORNE' => [
        'label' => 'Protection différentielle dédiée borne (Type A ou F)',
        'unit' => 'u',
        'price_low' => 80.00,
        'price_mid' => 150.00,
        'price_high' => 280.00,
        'category' => 'materiel'
    ],

    // === TABLEAU COMPLET ===
    'TABLEAU_52M' => [
        'label' => 'Coffret 4 rangées 52 modules',
        'unit' => 'u',
        'price_low' => 100.00,
        'price_mid' => 160.00,
        'price_high' => 280.00,
        'category' => 'materiel'
    ],
    'DJ_40A' => [
        'label' => 'Disjoncteur 40A (chauffe-eau)',
        'unit' => 'u',
        'price_low' => 12.00,
        'price_mid' => 22.00,
        'price_high' => 38.00,
        'category' => 'materiel'
    ],
    'CONTACTEUR_HC' => [
        'label' => 'Contacteur heures creuses',
        'unit' => 'u',
        'price_low' => 25.00,
        'price_mid' => 45.00,
        'price_high' => 75.00,
        'category' => 'materiel'
    ],
    'PARAFOUDRE' => [
        'label' => 'Parafoudre modulaire',
        'unit' => 'u',
        'price_low' => 60.00,
        'price_mid' => 120.00,
        'price_high' => 200.00,
        'category' => 'materiel'
    ],
    'TELERUPTEUR' => [
        'label' => 'Télérupteur modulaire',
        'unit' => 'u',
        'price_low' => 15.00,
        'price_mid' => 28.00,
        'price_high' => 50.00,
        'category' => 'materiel'
    ],

    // === CÂBLES GROS SECTION ===
    'CABLE_3G10' => [
        'label' => 'Câble R2V 3G10mm² (chauffe-eau)',
        'unit' => 'm',
        'price_low' => 5.00,
        'price_mid' => 7.50,
        'price_high' => 11.00,
        'category' => 'materiel'
    ],
    'CABLE_5G6' => [
        'label' => 'Câble R2V 5G6mm² (plaque cuisson)',
        'unit' => 'm',
        'price_low' => 6.00,
        'price_mid' => 9.00,
        'price_high' => 14.00,
        'category' => 'materiel'
    ],

    // === GOULOTTES & CHEMINS ===
    'GOULOTTE_40x25' => [
        'label' => 'Goulotte 40x25mm avec couvercle',
        'unit' => 'm',
        'price_low' => 3.50,
        'price_mid' => 6.00,
        'price_high' => 12.00,
        'category' => 'materiel'
    ],
    'MOULURE_32x12' => [
        'label' => 'Moulure électrique 32x12mm',
        'unit' => 'm',
        'price_low' => 1.50,
        'price_mid' => 2.80,
        'price_high' => 5.00,
        'category' => 'materiel'
    ],

    // === TRAVAUX SPÉCIAUX ===
    'SAIGNEE_BRIQUE' => [
        'label' => 'Saignée dans brique/parpaing',
        'unit' => 'm',
        'price_low' => 8.00,
        'price_mid' => 12.00,
        'price_high' => 18.00,
        'category' => 'main_oeuvre'
    ],
    'SAIGNEE_BETON' => [
        'label' => 'Saignée dans béton',
        'unit' => 'm',
        'price_low' => 15.00,
        'price_mid' => 25.00,
        'price_high' => 40.00,
        'category' => 'main_oeuvre'
    ],
    'REBOUCHAGE_SAIGNEE' => [
        'label' => 'Rebouchage saignée au plâtre',
        'unit' => 'm',
        'price_low' => 5.00,
        'price_mid' => 8.00,
        'price_high' => 12.00,
        'category' => 'main_oeuvre'
    ],

    // === FORFAITS ===
    'FORFAIT_POINT_LUM' => [
        'label' => 'Création point lumineux complet',
        'unit' => 'u',
        'price_low' => 100.00,
        'price_mid' => 150.00,
        'price_high' => 220.00,
        'category' => 'forfait'
    ],
    'FORFAIT_PRISE' => [
        'label' => 'Création prise complète (câblage inclus)',
        'unit' => 'u',
        'price_low' => 80.00,
        'price_mid' => 120.00,
        'price_high' => 180.00,
        'category' => 'forfait'
    ],
    'FORFAIT_TABLEAU_RENOV' => [
        'label' => 'Rénovation tableau électrique complet',
        'unit' => 'forfait',
        'price_low' => 800.00,
        'price_mid' => 1200.00,
        'price_high' => 1800.00,
        'category' => 'forfait'
    ],
    'FORFAIT_TABLEAU_NEUF' => [
        'label' => 'Tableau électrique neuf 4 rangées équipé',
        'unit' => 'forfait',
        'price_low' => 1200.00,
        'price_mid' => 1800.00,
        'price_high' => 2800.00,
        'category' => 'forfait'
    ],
    'FORFAIT_CONSUEL' => [
        'label' => 'Préparation passage Consuel',
        'unit' => 'forfait',
        'price_low' => 150.00,
        'price_mid' => 250.00,
        'price_high' => 400.00,
        'category' => 'forfait'
    ],
    'FORFAIT_RENOV_M2' => [
        'label' => 'Rénovation électrique complète au m²',
        'unit' => 'm²',
        'price_low' => 90.00,
        'price_mid' => 130.00,
        'price_high' => 200.00,
        'category' => 'forfait'
    ],
    'FORFAIT_POSE_BALLON' => [
        'label' => 'Pose et raccordement ballon eau chaude',
        'unit' => 'forfait',
        'price_low' => 180.00,
        'price_mid' => 280.00,
        'price_high' => 450.00,
        'category' => 'forfait'
    ],
    'FORFAIT_POSE_RADIATEUR' => [
        'label' => 'Pose et raccordement radiateur électrique',
        'unit' => 'u',
        'price_low' => 80.00,
        'price_mid' => 120.00,
        'price_high' => 180.00,
        'category' => 'forfait'
    ],
    'FORFAIT_POSE_VMC' => [
        'label' => 'Pose et raccordement VMC complète',
        'unit' => 'forfait',
        'price_low' => 250.00,
        'price_mid' => 400.00,
        'price_high' => 600.00,
        'category' => 'forfait'
    ],

    // === VMC DOUBLE FLUX (TARIFS 2026) ===
    'VMC_DOUBLE_FLUX' => [
        'label' => 'VMC double flux haut rendement',
        'unit' => 'u',
        'price_low' => 1800.00,
        'price_mid' => 2800.00,
        'price_high' => 4500.00,
        'category' => 'materiel'
    ],
    'FORFAIT_POSE_VMC_DF' => [
        'label' => 'Pose VMC double flux complète',
        'unit' => 'forfait',
        'price_low' => 1500.00,
        'price_mid' => 2200.00,
        'price_high' => 3500.00,
        'category' => 'forfait'
    ],
    'BOUCHE_EXTRACTION' => [
        'label' => 'Bouche d\'extraction VMC',
        'unit' => 'u',
        'price_low' => 12.00,
        'price_mid' => 25.00,
        'price_high' => 45.00,
        'category' => 'materiel'
    ],
    'BOUCHE_INSUFFLATION' => [
        'label' => 'Bouche d\'insufflation VMC DF',
        'unit' => 'u',
        'price_low' => 18.00,
        'price_mid' => 35.00,
        'price_high' => 60.00,
        'category' => 'materiel'
    ],
    'GAINE_VMC' => [
        'label' => 'Gaine souple VMC Ø125',
        'unit' => 'm',
        'price_low' => 3.50,
        'price_mid' => 6.00,
        'price_high' => 10.00,
        'category' => 'materiel'
    ],

    // === CHAUFFAGE ÉLECTRIQUE PERFORMANT (TARIFS 2026) ===
    'RADIATEUR_INERTIE_1000W' => [
        'label' => 'Radiateur à inertie 1000W',
        'unit' => 'u',
        'price_low' => 250.00,
        'price_mid' => 450.00,
        'price_high' => 800.00,
        'category' => 'materiel'
    ],
    'RADIATEUR_INERTIE_1500W' => [
        'label' => 'Radiateur à inertie 1500W',
        'unit' => 'u',
        'price_low' => 300.00,
        'price_mid' => 550.00,
        'price_high' => 950.00,
        'category' => 'materiel'
    ],
    'RADIATEUR_INERTIE_2000W' => [
        'label' => 'Radiateur à inertie 2000W',
        'unit' => 'u',
        'price_low' => 400.00,
        'price_mid' => 700.00,
        'price_high' => 1200.00,
        'category' => 'materiel'
    ],
    'RADIATEUR_CONNECTE' => [
        'label' => 'Radiateur connecté intelligent',
        'unit' => 'u',
        'price_low' => 500.00,
        'price_mid' => 850.00,
        'price_high' => 1400.00,
        'category' => 'materiel'
    ],
    'PLANCHER_CHAUFFANT_ELEC' => [
        'label' => 'Plancher chauffant électrique',
        'unit' => 'm²',
        'price_low' => 60.00,
        'price_mid' => 95.00,
        'price_high' => 150.00,
        'category' => 'materiel'
    ],
    'THERMOSTAT_PROGRAMMABLE' => [
        'label' => 'Thermostat programmable',
        'unit' => 'u',
        'price_low' => 45.00,
        'price_mid' => 85.00,
        'price_high' => 150.00,
        'category' => 'materiel'
    ],
    'THERMOSTAT_CONNECTE' => [
        'label' => 'Thermostat connecté intelligent',
        'unit' => 'u',
        'price_low' => 120.00,
        'price_mid' => 200.00,
        'price_high' => 350.00,
        'category' => 'materiel'
    ],
    'GESTIONNAIRE_ENERGIE' => [
        'label' => 'Gestionnaire d\'énergie centralisé',
        'unit' => 'u',
        'price_low' => 180.00,
        'price_mid' => 320.00,
        'price_high' => 550.00,
        'category' => 'materiel'
    ],
    'DELESTEUR' => [
        'label' => 'Délesteur modulaire',
        'unit' => 'u',
        'price_low' => 80.00,
        'price_mid' => 150.00,
        'price_high' => 280.00,
        'category' => 'materiel'
    ],

    // === VOLETS ROULANTS & MOTORISATION (TARIFS 2026) ===
    'VOLET_ROULANT_ELEC' => [
        'label' => 'Volet roulant électrique PVC',
        'unit' => 'u',
        'price_low' => 350.00,
        'price_mid' => 550.00,
        'price_high' => 850.00,
        'category' => 'materiel'
    ],
    'VOLET_ROULANT_ALU' => [
        'label' => 'Volet roulant électrique aluminium',
        'unit' => 'u',
        'price_low' => 450.00,
        'price_mid' => 700.00,
        'price_high' => 1100.00,
        'category' => 'materiel'
    ],
    'MOTEUR_VOLET' => [
        'label' => 'Moteur tubulaire volet roulant',
        'unit' => 'u',
        'price_low' => 120.00,
        'price_mid' => 200.00,
        'price_high' => 350.00,
        'category' => 'materiel'
    ],
    'MOTORISATION_VOLET' => [
        'label' => 'Motorisation volet manuel existant',
        'unit' => 'u',
        'price_low' => 250.00,
        'price_mid' => 400.00,
        'price_high' => 650.00,
        'category' => 'forfait'
    ],
    'INTER_VOLET_RADIO' => [
        'label' => 'Interrupteur volet roulant radio',
        'unit' => 'u',
        'price_low' => 25.00,
        'price_mid' => 45.00,
        'price_high' => 80.00,
        'category' => 'materiel'
    ],
    'STORE_BANNE_ELEC' => [
        'label' => 'Store banne électrique 4m',
        'unit' => 'u',
        'price_low' => 800.00,
        'price_mid' => 1400.00,
        'price_high' => 2500.00,
        'category' => 'materiel'
    ],
    'FORFAIT_POSE_STORE' => [
        'label' => 'Pose store banne électrique',
        'unit' => 'forfait',
        'price_low' => 200.00,
        'price_mid' => 350.00,
        'price_high' => 550.00,
        'category' => 'forfait'
    ],

    // === PORTAIL ÉLECTRIQUE (TARIFS 2026) ===
    'MOTEUR_PORTAIL_BATTANT' => [
        'label' => 'Motorisation portail battant (kit)',
        'unit' => 'u',
        'price_low' => 400.00,
        'price_mid' => 700.00,
        'price_high' => 1200.00,
        'category' => 'materiel'
    ],
    'MOTEUR_PORTAIL_COULISSANT' => [
        'label' => 'Motorisation portail coulissant (kit)',
        'unit' => 'u',
        'price_low' => 500.00,
        'price_mid' => 850.00,
        'price_high' => 1500.00,
        'category' => 'materiel'
    ],
    'FORFAIT_POSE_MOTEUR_PORTAIL' => [
        'label' => 'Pose motorisation portail',
        'unit' => 'forfait',
        'price_low' => 350.00,
        'price_mid' => 550.00,
        'price_high' => 900.00,
        'category' => 'forfait'
    ],
    'PHOTOCELLULE' => [
        'label' => 'Paire de photocellules sécurité',
        'unit' => 'u',
        'price_low' => 35.00,
        'price_mid' => 65.00,
        'price_high' => 120.00,
        'category' => 'materiel'
    ],
    'GYROPHARE' => [
        'label' => 'Gyrophare clignotant portail',
        'unit' => 'u',
        'price_low' => 20.00,
        'price_mid' => 40.00,
        'price_high' => 75.00,
        'category' => 'materiel'
    ],
    'TELECOMMANDE_PORTAIL' => [
        'label' => 'Télécommande portail 4 canaux',
        'unit' => 'u',
        'price_low' => 25.00,
        'price_mid' => 45.00,
        'price_high' => 85.00,
        'category' => 'materiel'
    ],

    // === ÉCLAIRAGE EXTÉRIEUR (TARIFS 2026) ===
    'PROJECTEUR_LED_20W' => [
        'label' => 'Projecteur LED extérieur 20W',
        'unit' => 'u',
        'price_low' => 25.00,
        'price_mid' => 45.00,
        'price_high' => 85.00,
        'category' => 'materiel'
    ],
    'PROJECTEUR_LED_50W' => [
        'label' => 'Projecteur LED extérieur 50W',
        'unit' => 'u',
        'price_low' => 40.00,
        'price_mid' => 75.00,
        'price_high' => 140.00,
        'category' => 'materiel'
    ],
    'PROJECTEUR_DETECTEUR' => [
        'label' => 'Projecteur LED avec détecteur',
        'unit' => 'u',
        'price_low' => 35.00,
        'price_mid' => 65.00,
        'price_high' => 120.00,
        'category' => 'materiel'
    ],
    'APPLIQUE_EXTERIEURE' => [
        'label' => 'Applique murale extérieure LED',
        'unit' => 'u',
        'price_low' => 25.00,
        'price_mid' => 55.00,
        'price_high' => 120.00,
        'category' => 'materiel'
    ],
    'BORNE_JARDIN' => [
        'label' => 'Borne lumineuse jardin LED',
        'unit' => 'u',
        'price_low' => 35.00,
        'price_mid' => 70.00,
        'price_high' => 150.00,
        'category' => 'materiel'
    ],
    'LAMPADAIRE_JARDIN' => [
        'label' => 'Lampadaire de jardin LED',
        'unit' => 'u',
        'price_low' => 80.00,
        'price_mid' => 150.00,
        'price_high' => 300.00,
        'category' => 'materiel'
    ],
    'SPOT_ENCASTRE_SOL' => [
        'label' => 'Spot LED encastré au sol IP67',
        'unit' => 'u',
        'price_low' => 25.00,
        'price_mid' => 50.00,
        'price_high' => 100.00,
        'category' => 'materiel'
    ],
    'FORFAIT_ECLAIRAGE_EXT' => [
        'label' => 'Installation point lumineux extérieur',
        'unit' => 'u',
        'price_low' => 120.00,
        'price_mid' => 180.00,
        'price_high' => 280.00,
        'category' => 'forfait'
    ],

    // === ANTENNE TV & PARABOLE (TARIFS 2026) ===
    'ANTENNE_TNT_EXT' => [
        'label' => 'Antenne TNT extérieure',
        'unit' => 'u',
        'price_low' => 50.00,
        'price_mid' => 100.00,
        'price_high' => 180.00,
        'category' => 'materiel'
    ],
    'PARABOLE_FIXE' => [
        'label' => 'Parabole satellite fixe',
        'unit' => 'u',
        'price_low' => 80.00,
        'price_mid' => 150.00,
        'price_high' => 280.00,
        'category' => 'materiel'
    ],
    'PARABOLE_MOTORISEE' => [
        'label' => 'Parabole satellite motorisée',
        'unit' => 'u',
        'price_low' => 180.00,
        'price_mid' => 320.00,
        'price_high' => 550.00,
        'category' => 'materiel'
    ],
    'REPARTITEUR_TV' => [
        'label' => 'Répartiteur TV 4 sorties',
        'unit' => 'u',
        'price_low' => 8.00,
        'price_mid' => 18.00,
        'price_high' => 35.00,
        'category' => 'materiel'
    ],
    'FORFAIT_POSE_ANTENNE' => [
        'label' => 'Pose antenne TNT/Parabole complète',
        'unit' => 'forfait',
        'price_low' => 120.00,
        'price_mid' => 200.00,
        'price_high' => 350.00,
        'category' => 'forfait'
    ],
    'PRISE_TV_COAX' => [
        'label' => 'Prise TV coaxiale',
        'unit' => 'u',
        'price_low' => 8.00,
        'price_mid' => 15.00,
        'price_high' => 28.00,
        'category' => 'materiel'
    ],
    'CABLE_COAX' => [
        'label' => 'Câble coaxial TV 17VATC',
        'unit' => 'm',
        'price_low' => 0.80,
        'price_mid' => 1.50,
        'price_high' => 2.80,
        'category' => 'materiel'
    ],

    // === RÉSEAU INFORMATIQUE RJ45 (TARIFS 2026) ===
    'PRISE_RJ45' => [
        'label' => 'Prise RJ45 Cat6',
        'unit' => 'u',
        'price_low' => 12.00,
        'price_mid' => 22.00,
        'price_high' => 40.00,
        'category' => 'materiel'
    ],
    'CABLE_RJ45_CAT6' => [
        'label' => 'Câble réseau Cat6 FTP',
        'unit' => 'm',
        'price_low' => 0.80,
        'price_mid' => 1.50,
        'price_high' => 2.80,
        'category' => 'materiel'
    ],
    'COFFRET_COMM' => [
        'label' => 'Coffret de communication Grade 2',
        'unit' => 'u',
        'price_low' => 120.00,
        'price_mid' => 220.00,
        'price_high' => 400.00,
        'category' => 'materiel'
    ],
    'SWITCH_8P' => [
        'label' => 'Switch réseau 8 ports Gigabit',
        'unit' => 'u',
        'price_low' => 25.00,
        'price_mid' => 50.00,
        'price_high' => 100.00,
        'category' => 'materiel'
    ],
    'FORFAIT_PRISE_RJ45' => [
        'label' => 'Création prise RJ45 complète',
        'unit' => 'u',
        'price_low' => 80.00,
        'price_mid' => 130.00,
        'price_high' => 200.00,
        'category' => 'forfait'
    ],

    // === ALARME & SÉCURITÉ (TARIFS 2026) ===
    'CENTRALE_ALARME' => [
        'label' => 'Centrale alarme sans fil',
        'unit' => 'u',
        'price_low' => 200.00,
        'price_mid' => 400.00,
        'price_high' => 800.00,
        'category' => 'materiel'
    ],
    'DETECTEUR_INTRUSION' => [
        'label' => 'Détecteur de mouvement infrarouge',
        'unit' => 'u',
        'price_low' => 25.00,
        'price_mid' => 50.00,
        'price_high' => 100.00,
        'category' => 'materiel'
    ],
    'DETECTEUR_OUVERTURE' => [
        'label' => 'Détecteur d\'ouverture porte/fenêtre',
        'unit' => 'u',
        'price_low' => 15.00,
        'price_mid' => 30.00,
        'price_high' => 60.00,
        'category' => 'materiel'
    ],
    'SIRENE_INTERIEURE' => [
        'label' => 'Sirène intérieure',
        'unit' => 'u',
        'price_low' => 25.00,
        'price_mid' => 50.00,
        'price_high' => 95.00,
        'category' => 'materiel'
    ],
    'SIRENE_EXTERIEURE' => [
        'label' => 'Sirène extérieure avec flash',
        'unit' => 'u',
        'price_low' => 60.00,
        'price_mid' => 120.00,
        'price_high' => 220.00,
        'category' => 'materiel'
    ],
    'CLAVIER_ALARME' => [
        'label' => 'Clavier de commande alarme',
        'unit' => 'u',
        'price_low' => 45.00,
        'price_mid' => 90.00,
        'price_high' => 180.00,
        'category' => 'materiel'
    ],
    'DETECTEUR_FUMEE' => [
        'label' => 'Détecteur de fumée connecté',
        'unit' => 'u',
        'price_low' => 20.00,
        'price_mid' => 45.00,
        'price_high' => 90.00,
        'category' => 'materiel'
    ],
    'FORFAIT_ALARME_MAISON' => [
        'label' => 'Installation alarme maison complète',
        'unit' => 'forfait',
        'price_low' => 600.00,
        'price_mid' => 1000.00,
        'price_high' => 1800.00,
        'category' => 'forfait'
    ],

    // === DOMOTIQUE (TARIFS 2026) ===
    'BOX_DOMOTIQUE' => [
        'label' => 'Box domotique centrale',
        'unit' => 'u',
        'price_low' => 150.00,
        'price_mid' => 300.00,
        'price_high' => 600.00,
        'category' => 'materiel'
    ],
    'MODULE_ECLAIRAGE' => [
        'label' => 'Module domotique éclairage',
        'unit' => 'u',
        'price_low' => 35.00,
        'price_mid' => 65.00,
        'price_high' => 120.00,
        'category' => 'materiel'
    ],
    'MODULE_VOLET' => [
        'label' => 'Module domotique volet roulant',
        'unit' => 'u',
        'price_low' => 45.00,
        'price_mid' => 85.00,
        'price_high' => 150.00,
        'category' => 'materiel'
    ],
    'PRISE_CONNECTEE' => [
        'label' => 'Prise connectée intelligente',
        'unit' => 'u',
        'price_low' => 20.00,
        'price_mid' => 40.00,
        'price_high' => 80.00,
        'category' => 'materiel'
    ],
    'INTER_CONNECTE' => [
        'label' => 'Interrupteur connecté',
        'unit' => 'u',
        'price_low' => 40.00,
        'price_mid' => 75.00,
        'price_high' => 140.00,
        'category' => 'materiel'
    ],
    'FORFAIT_DOMOTIQUE_BASE' => [
        'label' => 'Installation domotique de base',
        'unit' => 'forfait',
        'price_low' => 500.00,
        'price_mid' => 900.00,
        'price_high' => 1600.00,
        'category' => 'forfait'
    ],

    // === DÉPANNAGE & URGENCES (TARIFS 2026) ===
    'MO_DEPANNAGE' => [
        'label' => 'Dépannage électrique (heure)',
        'unit' => 'h',
        'price_low' => 50.00,
        'price_mid' => 70.00,
        'price_high' => 100.00,
        'category' => 'main_oeuvre'
    ],
    'MO_URGENCE_SOIR' => [
        'label' => 'Intervention urgente soir/WE',
        'unit' => 'h',
        'price_low' => 80.00,
        'price_mid' => 120.00,
        'price_high' => 180.00,
        'category' => 'main_oeuvre'
    ],
    'MO_URGENCE_NUIT' => [
        'label' => 'Intervention urgente nuit/férié',
        'unit' => 'h',
        'price_low' => 120.00,
        'price_mid' => 180.00,
        'price_high' => 280.00,
        'category' => 'main_oeuvre'
    ],
    'FORFAIT_RECHERCHE_PANNE' => [
        'label' => 'Recherche de panne électrique',
        'unit' => 'forfait',
        'price_low' => 80.00,
        'price_mid' => 130.00,
        'price_high' => 220.00,
        'category' => 'forfait'
    ],
    'FORFAIT_DEPANNAGE_MIN' => [
        'label' => 'Forfait dépannage minimum',
        'unit' => 'forfait',
        'price_low' => 60.00,
        'price_mid' => 90.00,
        'price_high' => 140.00,
        'category' => 'forfait'
    ],

    // === CIRCUITS SPÉCIALISÉS (TARIFS 2026) ===
    'CIRCUIT_PLAQUE' => [
        'label' => 'Création circuit plaque cuisson 32A',
        'unit' => 'forfait',
        'price_low' => 180.00,
        'price_mid' => 280.00,
        'price_high' => 450.00,
        'category' => 'forfait'
    ],
    'CIRCUIT_FOUR' => [
        'label' => 'Création circuit four 20A',
        'unit' => 'forfait',
        'price_low' => 120.00,
        'price_mid' => 200.00,
        'price_high' => 320.00,
        'category' => 'forfait'
    ],
    'CIRCUIT_LAVE_LINGE' => [
        'label' => 'Création circuit lave-linge 20A',
        'unit' => 'forfait',
        'price_low' => 120.00,
        'price_mid' => 200.00,
        'price_high' => 320.00,
        'category' => 'forfait'
    ],
    'CIRCUIT_SECHE_LINGE' => [
        'label' => 'Création circuit sèche-linge 20A',
        'unit' => 'forfait',
        'price_low' => 120.00,
        'price_mid' => 200.00,
        'price_high' => 320.00,
        'category' => 'forfait'
    ],
    'CIRCUIT_LAVE_VAISSELLE' => [
        'label' => 'Création circuit lave-vaisselle 20A',
        'unit' => 'forfait',
        'price_low' => 120.00,
        'price_mid' => 200.00,
        'price_high' => 320.00,
        'category' => 'forfait'
    ],
    'CIRCUIT_CONGELATEUR' => [
        'label' => 'Création circuit congélateur dédié',
        'unit' => 'forfait',
        'price_low' => 100.00,
        'price_mid' => 160.00,
        'price_high' => 260.00,
        'category' => 'forfait'
    ],
    'CIRCUIT_CLIM' => [
        'label' => 'Création circuit climatisation',
        'unit' => 'forfait',
        'price_low' => 180.00,
        'price_mid' => 300.00,
        'price_high' => 480.00,
        'category' => 'forfait'
    ],

    // === PRISES SPÉCIALES (TARIFS 2026) ===
    'PRISE_USB' => [
        'label' => 'Prise 2P+T avec USB intégré',
        'unit' => 'u',
        'price_low' => 18.00,
        'price_mid' => 32.00,
        'price_high' => 55.00,
        'category' => 'materiel'
    ],
    'PRISE_32A' => [
        'label' => 'Prise 32A plaque cuisson',
        'unit' => 'u',
        'price_low' => 15.00,
        'price_mid' => 28.00,
        'price_high' => 50.00,
        'category' => 'materiel'
    ],
    'PRISE_20A' => [
        'label' => 'Prise 20A spécialisée',
        'unit' => 'u',
        'price_low' => 10.00,
        'price_mid' => 18.00,
        'price_high' => 32.00,
        'category' => 'materiel'
    ],
    'PRISE_RASOIR' => [
        'label' => 'Prise rasoir salle de bain',
        'unit' => 'u',
        'price_low' => 35.00,
        'price_mid' => 65.00,
        'price_high' => 120.00,
        'category' => 'materiel'
    ],
    'PRISE_EXTERIEURE' => [
        'label' => 'Prise étanche extérieure IP55',
        'unit' => 'u',
        'price_low' => 18.00,
        'price_mid' => 35.00,
        'price_high' => 65.00,
        'category' => 'materiel'
    ],

    // === ÉCLAIRAGE INTÉRIEUR COMPLÉMENTAIRE ===
    'VARIATEUR' => [
        'label' => 'Variateur d\'intensité lumineuse',
        'unit' => 'u',
        'price_low' => 25.00,
        'price_mid' => 50.00,
        'price_high' => 100.00,
        'category' => 'materiel'
    ],
    'MINUTERIE' => [
        'label' => 'Minuterie escalier modulaire',
        'unit' => 'u',
        'price_low' => 20.00,
        'price_mid' => 40.00,
        'price_high' => 75.00,
        'category' => 'materiel'
    ],
    'BANDEAU_LED' => [
        'label' => 'Bandeau LED 5m avec alimentation',
        'unit' => 'kit',
        'price_low' => 35.00,
        'price_mid' => 70.00,
        'price_high' => 140.00,
        'category' => 'materiel'
    ],
    'SPOT_ORIENTABLE' => [
        'label' => 'Spot LED orientable sur rail',
        'unit' => 'u',
        'price_low' => 25.00,
        'price_mid' => 50.00,
        'price_high' => 100.00,
        'category' => 'materiel'
    ],
    'RAIL_SPOTS' => [
        'label' => 'Rail pour spots 1m',
        'unit' => 'u',
        'price_low' => 30.00,
        'price_mid' => 60.00,
        'price_high' => 120.00,
        'category' => 'materiel'
    ],
    'SUSPENSION_LED' => [
        'label' => 'Suspension LED design',
        'unit' => 'u',
        'price_low' => 50.00,
        'price_mid' => 120.00,
        'price_high' => 280.00,
        'category' => 'materiel'
    ],

    // === DIAGNOSTIC & CONFORMITÉ (TARIFS 2026) ===
    'DIAGNOSTIC_ELEC' => [
        'label' => 'Diagnostic électrique complet',
        'unit' => 'forfait',
        'price_low' => 100.00,
        'price_mid' => 160.00,
        'price_high' => 250.00,
        'category' => 'forfait'
    ],
    'MISE_SECURITE' => [
        'label' => 'Mise en sécurité installation',
        'unit' => 'm²',
        'price_low' => 55.00,
        'price_mid' => 75.00,
        'price_high' => 100.00,
        'category' => 'forfait'
    ],
    'ATTESTATION_CONSUEL' => [
        'label' => 'Frais attestation Consuel',
        'unit' => 'forfait',
        'price_low' => 130.00,
        'price_mid' => 155.00,
        'price_high' => 180.00,
        'category' => 'forfait'
    ],
];
