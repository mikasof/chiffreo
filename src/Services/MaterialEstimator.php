<?php

namespace App\Services;

/**
 * Estimateur de mat�riel �lectrique
 *
 * Pr�-calcule les mat�riaux et main d'oeuvre n�cessaires bas� sur :
 * - La surface en m�
 * - Le nombre de pi�ces
 * - Le type de chantier (r�novation compl�te, partielle, neuf)
 *
 * Utilise les ratios NFC 15-100 et les prix de config/prices.php
 */
class MaterialEstimator
{
    private array $priceGrid;

    // Ratios NFC 15-100 par piece pour un logement standard (norme 2024+)
    private array $ratiosPiece = [
        'sejour' => [
            'prises' => 7,           // 1 par 4m2 min + reserve multimedia
            'prises_doubles' => 2,   // prises doubles TV/multimedia
            'points_lumineux' => 3,  // plafonnier + 2 appliques ou spots
            'interrupteurs' => 3,
            'va_et_vient' => 1,
            'rj45' => 2,
            'prise_tv' => 1,
        ],
        'chambre' => [
            'prises' => 4,           // 2 cote lit + 2 bureau/dressing
            'prises_doubles' => 1,   // double prise cote lit
            'points_lumineux' => 2,  // plafonnier + applique tete de lit
            'interrupteurs' => 1,
            'va_et_vient' => 1,
        ],
        'cuisine' => [
            'prises' => 8,           // plan de travail + electromenager
            'prises_specialisees' => 5, // four, plaque, lave-vaisselle, frigo, micro-ondes
            'points_lumineux' => 3,  // plafonnier + spots plan travail + hotte
            'interrupteurs' => 2,
        ],
        'sdb' => [
            'prises' => 2,           // hors volumes + seche-cheveux
            'points_lumineux' => 3,  // plafonnier + miroir + baignoire/douche
            'interrupteurs' => 2,
            'seche_serviettes' => 1,
        ],
        'wc' => [
            'prises' => 1,
            'points_lumineux' => 1,
            'interrupteurs' => 1,
        ],
        'couloir' => [
            'prises' => 2,
            'points_lumineux' => 2,
            'interrupteurs' => 2,   // va-et-vient
        ],
        'entree' => [
            'prises' => 2,
            'points_lumineux' => 2,  // plafonnier + applique
            'interrupteurs' => 2,
        ],
    ];

    // Temps unitaires main d'oeuvre (en heures)
    private array $tempsUnitaires = [
        'depose_tableau' => 2.0,
        'pose_tableau' => 4.0,
        'pose_differentiel' => 0.25,
        'pose_disjoncteur' => 0.15,
        'tirage_cable_ml' => 0.05,   // par m�tre lin�aire
        'pose_boite' => 0.3,
        'pose_prise' => 0.5,
        'pose_interrupteur' => 0.4,
        'pose_point_lumineux' => 0.75,
        'saignee_ml' => 0.15,        // par m�tre lin�aire
        'rebouchage_ml' => 0.1,
        'reperage_test' => 2.0,
        'mise_en_service' => 1.5,
    ];

    public function __construct()
    {
        $this->priceGrid = require __DIR__ . '/../../config/prices.php';
    }

    /**
     * Analyse une description pour extraire les param�tres cl�s
     *
     * @param string $description Description du chantier
     * @return array Param�tres extraits (surface, pieces, type, etc.)
     */
    public function parseDescription(string $description): array
    {
        $desc = mb_strtolower($description);

        $params = [
            'surface' => 0,
            'pieces' => [],
            'type' => 'renovation_complete', // renovation_complete, renovation_partielle, neuf
            'gamme' => 'mid',
            'options' => [],
        ];

        // Extraction surface
        if (preg_match('/(\d+)\s*m[�2]|(\d+)\s*metres?\s*carr/', $desc, $matches)) {
            $params['surface'] = (int)($matches[1] ?: $matches[2]);
        }

        // Extraction nombre de pi�ces sp�cifiques
        if (preg_match('/(\d+)\s*chambres?/', $desc, $m)) {
            for ($i = 0; $i < (int)$m[1]; $i++) {
                $params['pieces'][] = 'chambre';
            }
        }

        if (preg_match('/(\d+)\s*salle[s]?\s*d[e\']?\s*bain|(\d+)\s*sdb/', $desc, $m)) {
            $nb = (int)($m[1] ?: $m[2]);
            for ($i = 0; $i < $nb; $i++) {
                $params['pieces'][] = 'sdb';
            }
        }

        // Pi�ces typiques mentionn�es
        if (preg_match('/salon|s�jour|living/', $desc)) {
            $params['pieces'][] = 'sejour';
        }
        if (preg_match('/cuisine/', $desc)) {
            $params['pieces'][] = 'cuisine';
        }
        if (preg_match('/wc|toilettes?/', $desc)) {
            $params['pieces'][] = 'wc';
        }
        if (preg_match('/entr�e|hall/', $desc)) {
            $params['pieces'][] = 'entree';
        }
        if (preg_match('/couloir|d�gagement/', $desc)) {
            $params['pieces'][] = 'couloir';
        }

        // Si pas de pi�ces d�tect�es, estimer selon surface
        if (empty($params['pieces']) && $params['surface'] > 0) {
            $params['pieces'] = $this->estimerPiecesParSurface($params['surface']);
        }

        // Type de chantier
        if (preg_match('/r�novation\s*compl�te|refaire\s*(toute|l[\'a]?\s*)�lectricit�|mise\s*aux?\s*normes?\s*compl�te/', $desc)) {
            $params['type'] = 'renovation_complete';
        } elseif (preg_match('/r�novation\s*partielle|quelques|modifier|ajouter/', $desc)) {
            $params['type'] = 'renovation_partielle';
        } elseif (preg_match('/construction\s*neuve?|neuf|nouveau/', $desc)) {
            $params['type'] = 'neuf';
        }

        // Gamme
        if (preg_match('/legrand|schneider|hager|premium|haut\s*de\s*gamme/', $desc)) {
            $params['gamme'] = 'high';
        } elseif (preg_match('/�conomique|premier\s*prix|pas\s*cher|entr�e\s*de\s*gamme/', $desc)) {
            $params['gamme'] = 'low';
        }

        // Options sp�ciales
        if (preg_match('/vmc|ventilation/', $desc)) {
            $params['options'][] = 'vmc';
        }
        if (preg_match('/chauffage|radiateur/', $desc)) {
            $params['options'][] = 'chauffage';
        }
        if (preg_match('/borne|recharge|v�hicule\s*�lectrique|voiture/', $desc)) {
            $params['options'][] = 'borne_recharge';
        }
        if (preg_match('/domotique|connect/', $desc)) {
            $params['options'][] = 'domotique';
        }
        if (preg_match('/consuel/', $desc)) {
            $params['options'][] = 'consuel';
        }

        return $params;
    }

    /**
     * Estime les pi�ces d'un logement selon sa surface
     */
    private function estimerPiecesParSurface(int $surface): array
    {
        $pieces = [];

        // Estimation standard
        if ($surface <= 30) {
            // Studio
            $pieces = ['sejour', 'cuisine', 'sdb', 'entree'];
        } elseif ($surface <= 50) {
            // 2 pi�ces
            $pieces = ['sejour', 'chambre', 'cuisine', 'sdb', 'wc', 'entree'];
        } elseif ($surface <= 70) {
            // 3 pi�ces
            $pieces = ['sejour', 'chambre', 'chambre', 'cuisine', 'sdb', 'wc', 'entree', 'couloir'];
        } elseif ($surface <= 100) {
            // 4 pi�ces
            $pieces = ['sejour', 'chambre', 'chambre', 'chambre', 'cuisine', 'sdb', 'wc', 'entree', 'couloir'];
        } else {
            // 5+ pi�ces
            $nbChambres = min(5, max(3, (int)($surface / 25)));
            $pieces = ['sejour', 'cuisine', 'sdb', 'sdb', 'wc', 'entree', 'couloir'];
            for ($i = 0; $i < $nbChambres; $i++) {
                $pieces[] = 'chambre';
            }
        }

        return $pieces;
    }

    /**
     * Calcule un devis complet bas� sur les param�tres extraits
     *
     * @param array $params Param�tres extraits de parseDescription()
     * @return array Structure de devis (fournitures, main_oeuvre, taches, totaux)
     */
    public function calculerDevis(array $params): array
    {
        $fournitures = [];
        $mainOeuvre = [];
        $taches = [];

        $gamme = $params['gamme'] ?? 'mid';
        $surface = $params['surface'] ?? 100;
        $type = $params['type'] ?? 'renovation_complete';

        // Compteurs pour le tableau
        $totalPrises = 0;
        $totalLumieres = 0;
        $totalInterrupteurs = 0;
        $circuitsSpecialises = 0;

        // ====== �TAPE 1 : CALCUL PAR PI�CE ======
        foreach ($params['pieces'] as $piece) {
            $ratios = $this->ratiosPiece[$piece] ?? $this->ratiosPiece['chambre'];

            $totalPrises += $ratios['prises'] ?? 0;
            $totalLumieres += $ratios['points_lumineux'] ?? 0;
            $totalInterrupteurs += ($ratios['interrupteurs'] ?? 0) + ($ratios['va_et_vient'] ?? 0);
            $circuitsSpecialises += $ratios['prises_specialisees'] ?? 0;
        }

        // ====== �TAPE 2 : TABLEAU �LECTRIQUE ======

        // Nombre de modules n�cessaires (estimation)
        $nbDisjoncteurs10A = (int)ceil($totalLumieres / 8); // 1 DJ 10A par 8 points
        $nbDisjoncteurs16A = (int)ceil($totalPrises / 8);    // 1 DJ 16A par 8 prises
        $nbDisjoncteurs20A = $circuitsSpecialises;           // 1 par circuit sp�cialis�
        $nbDisjoncteurs32A = in_array('cuisine', $params['pieces']) ? 1 : 0; // plaque

        // Interrupteurs diff�rentiels (1 type A obligatoire, 1 type AC par tranche de 8 DJ)
        $totalDJ = $nbDisjoncteurs10A + $nbDisjoncteurs16A + $nbDisjoncteurs20A + $nbDisjoncteurs32A;
        $nbDiffA = 1; // Minimum obligatoire
        $nbDiffAC = max(1, (int)ceil(($totalDJ - 8) / 8));

        // Taille du tableau
        $modulesNecessaires = $totalDJ + ($nbDiffA + $nbDiffAC) * 2 + 5; // +5 pour peigne, r�serve
        if ($modulesNecessaires <= 13) {
            $tableauCode = 'TABLEAU_13M';
            $tableauLabel = 'Coffret 1 rang�e 13 modules';
        } elseif ($modulesNecessaires <= 26) {
            $tableauCode = 'TABLEAU_26M';
            $tableauLabel = 'Coffret 2 rang�es 26 modules';
        } elseif ($modulesNecessaires <= 39) {
            $tableauCode = 'TABLEAU_39M';
            $tableauLabel = 'Coffret 3 rang�es 39 modules';
        } else {
            $tableauCode = 'TABLEAU_52M';
            $tableauLabel = 'Coffret 4 rang�es 52 modules';
        }

        // Ajout du tableau
        $fournitures[] = $this->createLine($tableauCode, $tableauLabel, 1, 'u', $gamme);

        // Interrupteurs diff�rentiels
        $fournitures[] = $this->createLine(
            'ID_40A_30MA',
            'Interrupteur diff�rentiel 40A 30mA type A',
            $nbDiffA, 'u', $gamme
        );

        if ($nbDiffAC > 0) {
            $fournitures[] = $this->createLine(
                'ID_63A_30MA',
                'Interrupteur diff�rentiel 63A 30mA type AC',
                $nbDiffAC, 'u', $gamme
            );
        }

        // Disjoncteurs
        if ($nbDisjoncteurs10A > 0) {
            $fournitures[] = $this->createLine(
                'DJ_10A',
                'Disjoncteur 10A courbe C (�clairage)',
                $nbDisjoncteurs10A, 'u', $gamme
            );
        }

        $fournitures[] = $this->createLine(
            'DJ_16A',
            'Disjoncteur 16A courbe C (prises)',
            $nbDisjoncteurs16A, 'u', $gamme
        );

        if ($nbDisjoncteurs20A > 0) {
            $fournitures[] = $this->createLine(
                'DJ_20A',
                'Disjoncteur 20A courbe C (circuits sp�cialis�s)',
                $nbDisjoncteurs20A, 'u', $gamme
            );
        }

        if ($nbDisjoncteurs32A > 0) {
            $fournitures[] = $this->createLine(
                'DJ_32A',
                'Disjoncteur 32A courbe C (plaque cuisson)',
                $nbDisjoncteurs32A, 'u', $gamme
            );
        }

        // Peignes et borniers
        $fournitures[] = $this->createLine(
            'PEIGNE',
            'Peigne d\'alimentation horizontal',
            max(1, (int)ceil($modulesNecessaires / 13)), 'u', $gamme
        );

        $fournitures[] = $this->createLine(
            'BORNIER_TERRE',
            'Bornier de terre',
            1, 'u', $gamme
        );

        // Parafoudre (recommand�)
        $fournitures[] = $this->createLine(
            'PARAFOUDRE',
            'Parafoudre modulaire',
            1, 'u', $gamme
        );

        // ====== �TAPE 3 : C�BLAGE ======

        // Estimation m�trage c�bles (ratio par m� + par point)
        $cableEclairage = (int)($totalLumieres * 15 * 1.2); // 15m moyen par point + 20% marge
        $cablePrises = (int)($totalPrises * 12 * 1.2);       // 12m moyen par prise
        $cableSpecialise = $circuitsSpecialises * 15;         // 15m par circuit sp�
        $cable32A = $nbDisjoncteurs32A > 0 ? 15 : 0;          // cuisine

        $fournitures[] = $this->createLine(
            'CABLE_3G15',
            'C�ble R2V 3G1.5mm� (�clairage)',
            $cableEclairage, 'm', $gamme
        );

        $fournitures[] = $this->createLine(
            'CABLE_3G25',
            'C�ble R2V 3G2.5mm� (prises)',
            $cablePrises, 'm', $gamme
        );

        if ($cableSpecialise > 0) {
            $fournitures[] = $this->createLine(
                'CABLE_3G25',
                'C�ble R2V 3G2.5mm� (circuits sp�cialis�s)',
                $cableSpecialise, 'm', $gamme
            );
        }

        if ($cable32A > 0) {
            $fournitures[] = $this->createLine(
                'CABLE_3G6',
                'C�ble R2V 3G6mm� (plaque cuisson)',
                $cable32A, 'm', $gamme
            );
        }

        // Fil de terre
        $fournitures[] = $this->createLine(
            'CABLE_3G25',
            'Fil de terre vert/jaune 6mm�',
            (int)($surface * 0.5), 'm', $gamme
        );

        // Gaines ICTA
        $gaineTotal = (int)(($cableEclairage + $cablePrises + $cableSpecialise) * 1.1);
        $fournitures[] = $this->createLine(
            'GAINE_ICTA_20',
            'Gaine ICTA �20',
            $gaineTotal, 'm', $gamme
        );

        // ====== �TAPE 4 : APPAREILLAGE ======

        // Prises standard
        $fournitures[] = $this->createLine(
            'PRISE_2PT',
            'Prise 2P+T 16A encastr�e',
            $totalPrises, 'u', $gamme
        );

        // Double prises (1/4 des prises)
        $fournitures[] = $this->createLine(
            'PRISE_DOUBLE',
            'Double prise 2P+T 16A',
            max(2, (int)($totalPrises / 4)), 'u', $gamme
        );

        // Interrupteurs simples et va-et-vient
        $nbVV = count(array_filter($params['pieces'], fn($p) => in_array($p, ['sejour', 'chambre', 'couloir'])));
        $nbSimples = max(0, $totalInterrupteurs - $nbVV * 2);

        if ($nbSimples > 0) {
            $fournitures[] = $this->createLine(
                'INTER_SA',
                'Interrupteur simple allumage',
                $nbSimples, 'u', $gamme
            );
        }

        if ($nbVV > 0) {
            $fournitures[] = $this->createLine(
                'INTER_VV',
                'Interrupteur va-et-vient',
                $nbVV * 2, 'u', $gamme
            );
        }

        // Boutons poussoirs (pour t�l�rupteurs)
        $nbTelerupteurs = max(1, (int)($totalLumieres / 6));
        $fournitures[] = $this->createLine(
            'TELERUPTEUR',
            'T�l�rupteur modulaire',
            $nbTelerupteurs, 'u', $gamme
        );

        // Bo�tes d'encastrement
        $totalBoites = $totalPrises + $totalInterrupteurs + $totalLumieres;
        $fournitures[] = $this->createLine(
            'BOITE_ENCAST',
            'Bo�te d\'encastrement �67mm',
            $totalBoites, 'u', $gamme
        );

        // Bo�tes de d�rivation
        $fournitures[] = $this->createLine(
            'BOITE_DERIV',
            'Bo�te de d�rivation IP55 100x100',
            max(4, count($params['pieces'])), 'u', $gamme
        );

        // Points lumineux (DCL + douilles)
        $fournitures[] = $this->createLine(
            'BOITE_ENCAST',
            'Bo�te DCL pour point lumineux',
            $totalLumieres, 'u', $gamme
        );

        // Spots LED encastr�s
        $nbSpots = (int)($totalLumieres * 0.5); // 50% spots
        if ($nbSpots > 0) {
            $fournitures[] = $this->createLine(
                'SPOT_LED',
                'Spot LED encastrable',
                $nbSpots, 'u', $gamme
            );
        }

        // Plafonniers
        $nbPlafonniers = $totalLumieres - $nbSpots;
        if ($nbPlafonniers > 0) {
            $fournitures[] = $this->createLine(
                'PLAFONNIER_LED',
                'Plafonnier LED',
                $nbPlafonniers, 'u', $gamme
            );
        }

        // ====== �TAPE 5 : CIRCUITS SP�CIALIS�S ======

        if (in_array('cuisine', $params['pieces'])) {
            // Prise 32A plaque
            $fournitures[] = $this->createLine(
                'PRISE_32A',
                'Prise 32A pour plaque de cuisson',
                1, 'u', $gamme
            );

            // Prises sp�cialis�es 20A
            $fournitures[] = $this->createLine(
                'PRISE_20A',
                'Prise 20A four',
                1, 'u', $gamme
            );
            $fournitures[] = $this->createLine(
                'PRISE_20A',
                'Prise 20A lave-vaisselle',
                1, 'u', $gamme
            );
            $fournitures[] = $this->createLine(
                'PRISE_20A',
                'Prise 20A lave-linge',
                1, 'u', $gamme
            );
            $fournitures[] = $this->createLine(
                'PRISE_20A',
                'Prise 20A cong�lateur',
                1, 'u', $gamme
            );
        }

        // Salle de bain
        $nbSdb = count(array_filter($params['pieces'], fn($p) => $p === 'sdb'));
        if ($nbSdb > 0) {
            $fournitures[] = $this->createLine(
                'PRISE_RASOIR',
                'Prise rasoir salle de bain (transformateur)',
                $nbSdb, 'u', $gamme
            );
        }

        // ====== �TAPE 6 : CONSOMMABLES ======

        $fournitures[] = $this->createLine(
            'WAGO',
            'Bornes de connexion Wago (lot de 10)',
            max(5, (int)($totalBoites / 3)), 'lot', $gamme
        );

        $fournitures[] = $this->createLine(
            'CONSOMMABLES_RENOV',
            'Consommables (chevilles, vis, colliers, attaches, dominos)',
            1, 'forfait', $gamme
        );

        // ====== ETAPE 7 : VMC (obligatoire pour renovation) ======

        // VMC obligatoire pour renovation complete, ou si demande explicite
        if ($type === 'renovation_complete' || in_array('vmc', $params['options'])) {
            $fournitures[] = $this->createLine(
                'VMC_HYGRO',
                'VMC hygroreglable type B',
                1, 'u', $gamme
            );
            $nbBouches = count(array_filter($params['pieces'], fn($p) => in_array($p, ['sdb', 'wc', 'cuisine'])));
            $fournitures[] = $this->createLine(
                'BOUCHE_EXTRACTION',
                'Bouche d\'extraction VMC',
                max(3, $nbBouches), 'u', $gamme
            );
            $fournitures[] = $this->createLine(
                'GAINE_VMC',
                'Gaine souple VMC �125',
                max(15, $nbBouches * 8), 'm', $gamme
            );
        }

        if (in_array('chauffage', $params['options'])) {
            $nbRadiateurs = count(array_filter($params['pieces'], fn($p) => in_array($p, ['sejour', 'chambre'])));
            $fournitures[] = $this->createLine(
                'RADIATEUR_INERTIE_1500W',
                'Radiateur �lectrique � inertie 1500W',
                max(1, $nbRadiateurs), 'u', $gamme
            );
        }

        if (in_array('borne_recharge', $params['options'])) {
            $fournitures[] = $this->createLine(
                'WALLBOX',
                'Wallbox murale avec cable',
                1, 'u', $gamme
            );
            $fournitures[] = $this->createLine(
                'PROTECTION_BORNE',
                'Protection differentielle dediee borne (Type A)',
                1, 'u', $gamme
            );
        }

        // ====== ETAPE 7b : EQUIPEMENTS COMPLEMENTAIRES RENOVATION ======

        if ($type === 'renovation_complete') {
            // Contacteur heures creuses pour chauffe-eau
            $fournitures[] = $this->createLine(
                'CONTACTEUR_HC',
                'Contacteur heures creuses',
                1, 'u', $gamme
            );

            // Seche-serviettes pour chaque SDB
            if ($nbSdb > 0) {
                $fournitures[] = $this->createLine(
                    'SECHE_SERVIETTE',
                    'Seche-serviettes electrique',
                    $nbSdb, 'u', $gamme
                );
            }

            // Thermostat programmable
            $fournitures[] = $this->createLine(
                'THERMOSTAT_PROGRAMMABLE',
                'Thermostat programmable fil pilote',
                1, 'u', $gamme
            );

            // Detecteurs de fumee connectes (obligatoire)
            $nbDetecteurs = max(1, count($params['pieces']) / 3);
            $fournitures[] = $this->createLine(
                'DETECTEUR_FUMEE',
                'Detecteur de fumee connecte',
                (int)ceil($nbDetecteurs), 'u', $gamme
            );

            // Goulottes pour passages apparents (estimation 10% du metrage)
            $goulottesLongueur = (int)($gaineTotal * 0.15);
            if ($goulottesLongueur > 0) {
                $fournitures[] = $this->createLine(
                    'GOULOTTE_40x25',
                    'Goulotte 40x25mm avec couvercle',
                    $goulottesLongueur, 'm', $gamme
                );
            }

            // Mise en service / Consuel
            $fournitures[] = $this->createLine(
                'ATTESTATION_CONSUEL',
                'Frais attestation Consuel',
                1, 'forfait', $gamme
            );
        }

        // ====== ETAPE 8 : MAIN D'OEUVRE ======

        $heuresTotal = 0;
        $tauxHoraire = 70;

        // D�pose si r�novation
        if ($type === 'renovation_complete') {
            $heuresDepose = $this->tempsUnitaires['depose_tableau'] + ($surface * 0.1);
            $mainOeuvre[] = [
                'designation' => 'D�pose installation existante',
                'heures' => round($heuresDepose, 1),
                'taux_horaire' => $tauxHoraire,
                'total_ht' => round($heuresDepose * $tauxHoraire, 2),
            ];
            $heuresTotal += $heuresDepose;

            $taches[] = [
                'ordre' => 1,
                'titre' => 'D�pose de l\'installation existante',
                'details' => 'D�montage du tableau, des c�bles et de l\'appareillage',
                'duree_estimee_h' => round($heuresDepose, 1),
                'points_attention' => ['Couper le courant', 'V�rifier absence de tension', 'Tri des d�chets'],
            ];
        }

        // Pose tableau
        $heuresPoseTableau = $this->tempsUnitaires['pose_tableau'] +
                            ($nbDiffA + $nbDiffAC) * $this->tempsUnitaires['pose_differentiel'] +
                            $totalDJ * $this->tempsUnitaires['pose_disjoncteur'];

        $mainOeuvre[] = [
            'designation' => 'Pose et c�blage tableau �lectrique',
            'heures' => round($heuresPoseTableau, 1),
            'taux_horaire' => $tauxHoraire,
            'total_ht' => round($heuresPoseTableau * $tauxHoraire, 2),
        ];
        $heuresTotal += $heuresPoseTableau;

        $taches[] = [
            'ordre' => count($taches) + 1,
            'titre' => 'Installation du tableau �lectrique',
            'details' => 'Pose coffret, diff�rentiels, disjoncteurs, peignes et raccordements',
            'duree_estimee_h' => round($heuresPoseTableau, 1),
            'points_attention' => ['Respect des normes NFC 15-100', '�tiquetage des circuits'],
        ];

        // Tirage de c�bles
        $metrageTotal = $cableEclairage + $cablePrises + $cableSpecialise + $cable32A;
        $heuresTirage = $metrageTotal * $this->tempsUnitaires['tirage_cable_ml'];

        $mainOeuvre[] = [
            'designation' => 'Tirage de c�bles',
            'heures' => round($heuresTirage, 1),
            'taux_horaire' => $tauxHoraire,
            'total_ht' => round($heuresTirage * $tauxHoraire, 2),
        ];
        $heuresTotal += $heuresTirage;

        $taches[] = [
            'ordre' => count($taches) + 1,
            'titre' => 'Tirage des c�bles',
            'details' => "Passage de {$metrageTotal}m de c�bles dans les gaines",
            'duree_estimee_h' => round($heuresTirage, 1),
            'points_attention' => ['Respect des rayons de courbure', 'Rep�rage des circuits'],
        ];

        // Pose appareillage
        $heuresPoseAppareillage = $totalPrises * $this->tempsUnitaires['pose_prise'] +
                                  $totalInterrupteurs * $this->tempsUnitaires['pose_interrupteur'] +
                                  $totalLumieres * $this->tempsUnitaires['pose_point_lumineux'];

        $mainOeuvre[] = [
            'designation' => 'Pose appareillage (prises, interrupteurs, points lumineux)',
            'heures' => round($heuresPoseAppareillage, 1),
            'taux_horaire' => $tauxHoraire,
            'total_ht' => round($heuresPoseAppareillage * $tauxHoraire, 2),
        ];
        $heuresTotal += $heuresPoseAppareillage;

        $taches[] = [
            'ordre' => count($taches) + 1,
            'titre' => 'Pose de l\'appareillage',
            'details' => "Installation de {$totalPrises} prises, {$totalInterrupteurs} interrupteurs, {$totalLumieres} points lumineux",
            'duree_estimee_h' => round($heuresPoseAppareillage, 1),
            'points_attention' => ['Hauteurs r�glementaires', 'Alignement'],
        ];

        // Saign�es si r�novation (estimation 30% du m�trage)
        if ($type === 'renovation_complete') {
            $metreSaignee = (int)($metrageTotal * 0.3);
            $heuresSaignee = $metreSaignee * $this->tempsUnitaires['saignee_ml'];
            $heuresRebouchage = $metreSaignee * $this->tempsUnitaires['rebouchage_ml'];

            $mainOeuvre[] = [
                'designation' => 'Saign�es murales',
                'heures' => round($heuresSaignee, 1),
                'taux_horaire' => $tauxHoraire,
                'total_ht' => round($heuresSaignee * $tauxHoraire, 2),
            ];
            $heuresTotal += $heuresSaignee;

            $mainOeuvre[] = [
                'designation' => 'Rebouchage des saign�es',
                'heures' => round($heuresRebouchage, 1),
                'taux_horaire' => $tauxHoraire,
                'total_ht' => round($heuresRebouchage * $tauxHoraire, 2),
            ];
            $heuresTotal += $heuresRebouchage;

            $taches[] = [
                'ordre' => count($taches) + 1,
                'titre' => 'R�alisation des saign�es',
                'details' => "Environ {$metreSaignee}m de saign�es murales",
                'duree_estimee_h' => round($heuresSaignee, 1),
                'points_attention' => ['Protection des sols', 'Profondeur adapt�e'],
            ];

            $taches[] = [
                'ordre' => count($taches) + 1,
                'titre' => 'Rebouchage des saign�es',
                'details' => 'Rebouchage au pl�tre et finition',
                'duree_estimee_h' => round($heuresRebouchage, 1),
                'points_attention' => ['S�chage avant peinture'],
            ];
        }

        // Raccordements
        $heuresRaccordements = ($totalPrises + $totalInterrupteurs + $totalLumieres) * 0.15;
        $mainOeuvre[] = [
            'designation' => 'Raccordements �lectriques',
            'heures' => round($heuresRaccordements, 1),
            'taux_horaire' => $tauxHoraire,
            'total_ht' => round($heuresRaccordements * $tauxHoraire, 2),
        ];
        $heuresTotal += $heuresRaccordements;

        // Tests et mise en service
        $heuresTests = $this->tempsUnitaires['reperage_test'] + $this->tempsUnitaires['mise_en_service'];

        $mainOeuvre[] = [
            'designation' => 'Tests, rep�rage et mise en service',
            'heures' => round($heuresTests, 1),
            'taux_horaire' => $tauxHoraire,
            'total_ht' => round($heuresTests * $tauxHoraire, 2),
        ];
        $heuresTotal += $heuresTests;

        $taches[] = [
            'ordre' => count($taches) + 1,
            'titre' => 'Tests et mise en service',
            'details' => 'V�rification de tous les circuits, tests diff�rentiels, �tiquetage',
            'duree_estimee_h' => round($heuresTests, 1),
            'points_attention' => ['Test de chaque circuit', 'V�rification isolement'],
        ];

        // Nettoyage chantier
        $heuresNettoyage = max(1, $surface * 0.02);
        $mainOeuvre[] = [
            'designation' => 'Nettoyage et �vacuation des d�chets',
            'heures' => round($heuresNettoyage, 1),
            'taux_horaire' => $tauxHoraire,
            'total_ht' => round($heuresNettoyage * $tauxHoraire, 2),
        ];
        $heuresTotal += $heuresNettoyage;

        $taches[] = [
            'ordre' => count($taches) + 1,
            'titre' => 'Nettoyage de chantier',
            'details' => '�vacuation des d�chets, nettoyage des zones de travail',
            'duree_estimee_h' => round($heuresNettoyage, 1),
            'points_attention' => ['Tri s�lectif', 'D�chetterie'],
        ];

        // D�placement
        $prixDeplacement = $this->priceGrid['MO_DEPLACEMENT']['price_' . $gamme] ?? 35;
        $mainOeuvre[] = [
            'designation' => 'D�placement',
            'heures' => 0,
            'taux_horaire' => 0,
            'total_ht' => $prixDeplacement,
        ];

        // ====== CALCUL DES TOTAUX ======

        $totalFournitures = array_sum(array_column($fournitures, 'total_ht'));
        $totalMO = array_sum(array_column($mainOeuvre, 'total_ht'));
        $totalHT = $totalFournitures + $totalMO;
        $tauxTva = 10; // TVA 10% r�novation
        $montantTva = round($totalHT * ($tauxTva / 100), 2);
        $totalTTC = round($totalHT + $montantTva, 2);

        return [
            'fournitures' => $fournitures,
            'main_oeuvre' => $mainOeuvre,
            'taches' => $taches,
            'totaux' => [
                'total_fournitures_ht' => round($totalFournitures, 2),
                'total_main_oeuvre_ht' => round($totalMO, 2),
                'total_ht' => round($totalHT, 2),
                'tva_applicable' => $tauxTva,
                'montant_tva' => $montantTva,
                'total_ttc' => $totalTTC,
            ],
            'meta' => [
                'surface' => $surface,
                'type' => $type,
                'gamme' => $gamme,
                'nb_prises' => $totalPrises,
                'nb_lumieres' => $totalLumieres,
                'nb_interrupteurs' => $totalInterrupteurs,
                'heures_estimees' => round($heuresTotal, 1),
                'calculateur_version' => '1.0',
            ],
            'questions_a_poser' => [],
            'avertissements' => [],
        ];
    }

    /**
     * G�n�re un devis complet � partir d'une description textuelle
     *
     * @param string $description Description du chantier
     * @return array Structure de devis compl�te
     */
    public function estimerDepuisDescription(string $description): array
    {
        $params = $this->parseDescription($description);
        return $this->calculerDevis($params);
    }

    /**
     * Catalogue des produits avec marques, references, prix reels et sources
     * Prix HT professionnels - Sources: Rexel, Sonepar, Legrand Pro 2024
     */
    private array $productCatalog = [
        // === TABLEAU ELECTRIQUE ===
        'TABLEAU_13M' => [
            'low'  => ['marque' => 'Eur\'ohm', 'reference' => '20113', 'prix' => 18.50, 'catalogue' => 'Rexel 2024'],
            'mid'  => ['marque' => 'Legrand', 'reference' => '401211', 'prix' => 42.80, 'catalogue' => 'Legrand Pro 2024'],
            'high' => ['marque' => 'Hager', 'reference' => 'GD113A', 'prix' => 78.50, 'catalogue' => 'Sonepar 2024'],
        ],
        'TABLEAU_26M' => [
            'low'  => ['marque' => 'Eur\'ohm', 'reference' => '20126', 'prix' => 32.00, 'catalogue' => 'Rexel 2024'],
            'mid'  => ['marque' => 'Legrand', 'reference' => '401212', 'prix' => 68.50, 'catalogue' => 'Legrand Pro 2024'],
            'high' => ['marque' => 'Hager', 'reference' => 'GD213A', 'prix' => 125.00, 'catalogue' => 'Sonepar 2024'],
        ],
        'TABLEAU_39M' => [
            'low'  => ['marque' => 'Eur\'ohm', 'reference' => '20139', 'prix' => 52.00, 'catalogue' => 'Rexel 2024'],
            'mid'  => ['marque' => 'Legrand', 'reference' => '401213', 'prix' => 98.00, 'catalogue' => 'Legrand Pro 2024'],
            'high' => ['marque' => 'Hager', 'reference' => 'GD313A', 'prix' => 168.00, 'catalogue' => 'Sonepar 2024'],
        ],
        'TABLEAU_52M' => [
            'low'  => ['marque' => 'Eur\'ohm', 'reference' => '20152', 'prix' => 85.00, 'catalogue' => 'Rexel 2024'],
            'mid'  => ['marque' => 'Legrand', 'reference' => '401214', 'prix' => 145.00, 'catalogue' => 'Legrand Pro 2024'],
            'high' => ['marque' => 'Hager', 'reference' => 'GD413A', 'prix' => 265.00, 'catalogue' => 'Sonepar 2024'],
        ],

        // === PROTECTION DIFFERENTIELLE ===
        'ID_40A_30MA' => [
            'low'  => ['marque' => 'Eur\'ohm', 'reference' => '20240', 'prix' => 42.00, 'catalogue' => 'Rexel 2024'],
            'mid'  => ['marque' => 'Legrand', 'reference' => '411617', 'prix' => 78.90, 'catalogue' => 'Legrand Pro 2024'],
            'high' => ['marque' => 'Schneider', 'reference' => 'A9R61240', 'prix' => 135.00, 'catalogue' => 'Schneider Pro 2024'],
        ],
        'ID_63A_30MA' => [
            'low'  => ['marque' => 'Eur\'ohm', 'reference' => '20263', 'prix' => 52.00, 'catalogue' => 'Rexel 2024'],
            'mid'  => ['marque' => 'Legrand', 'reference' => '411651', 'prix' => 89.50, 'catalogue' => 'Legrand Pro 2024'],
            'high' => ['marque' => 'Schneider', 'reference' => 'A9R61263', 'prix' => 155.00, 'catalogue' => 'Schneider Pro 2024'],
        ],

        // === DISJONCTEURS ===
        'DJ_10A' => [
            'low'  => ['marque' => 'Eur\'ohm', 'reference' => '20010', 'prix' => 4.85, 'catalogue' => 'Rexel 2024'],
            'mid'  => ['marque' => 'Legrand', 'reference' => '406773', 'prix' => 11.20, 'catalogue' => 'Legrand Pro 2024'],
            'high' => ['marque' => 'Schneider', 'reference' => 'A9F74110', 'prix' => 18.50, 'catalogue' => 'Schneider Pro 2024'],
        ],
        'DJ_16A' => [
            'low'  => ['marque' => 'Eur\'ohm', 'reference' => '20016', 'prix' => 4.85, 'catalogue' => 'Rexel 2024'],
            'mid'  => ['marque' => 'Legrand', 'reference' => '406775', 'prix' => 11.20, 'catalogue' => 'Legrand Pro 2024'],
            'high' => ['marque' => 'Schneider', 'reference' => 'A9F74116', 'prix' => 18.50, 'catalogue' => 'Schneider Pro 2024'],
        ],
        'DJ_20A' => [
            'low'  => ['marque' => 'Eur\'ohm', 'reference' => '20020', 'prix' => 5.20, 'catalogue' => 'Rexel 2024'],
            'mid'  => ['marque' => 'Legrand', 'reference' => '406777', 'prix' => 12.80, 'catalogue' => 'Legrand Pro 2024'],
            'high' => ['marque' => 'Schneider', 'reference' => 'A9F74120', 'prix' => 21.50, 'catalogue' => 'Schneider Pro 2024'],
        ],
        'DJ_32A' => [
            'low'  => ['marque' => 'Eur\'ohm', 'reference' => '20032', 'prix' => 7.80, 'catalogue' => 'Rexel 2024'],
            'mid'  => ['marque' => 'Legrand', 'reference' => '406779', 'prix' => 16.50, 'catalogue' => 'Legrand Pro 2024'],
            'high' => ['marque' => 'Schneider', 'reference' => 'A9F74132', 'prix' => 28.00, 'catalogue' => 'Schneider Pro 2024'],
        ],

        // === MODULAIRE ===
        'PEIGNE' => [
            'low'  => ['marque' => 'Eur\'ohm', 'reference' => '20801', 'prix' => 8.50, 'catalogue' => 'Rexel 2024'],
            'mid'  => ['marque' => 'Legrand', 'reference' => '404926', 'prix' => 16.80, 'catalogue' => 'Legrand Pro 2024'],
            'high' => ['marque' => 'Hager', 'reference' => 'KDN363A', 'prix' => 28.50, 'catalogue' => 'Sonepar 2024'],
        ],
        'BORNIER_TERRE' => [
            'low'  => ['marque' => 'Eur\'ohm', 'reference' => '20830', 'prix' => 3.50, 'catalogue' => 'Rexel 2024'],
            'mid'  => ['marque' => 'Legrand', 'reference' => '004832', 'prix' => 7.20, 'catalogue' => 'Legrand Pro 2024'],
            'high' => ['marque' => 'Hager', 'reference' => 'KJ02B', 'prix' => 14.00, 'catalogue' => 'Sonepar 2024'],
        ],
        'PARAFOUDRE' => [
            'low'  => ['marque' => 'Eur\'ohm', 'reference' => '30275', 'prix' => 55.00, 'catalogue' => 'Rexel 2024'],
            'mid'  => ['marque' => 'Legrand', 'reference' => '412220', 'prix' => 115.00, 'catalogue' => 'Legrand Pro 2024'],
            'high' => ['marque' => 'Schneider', 'reference' => 'A9L16293', 'prix' => 195.00, 'catalogue' => 'Schneider Pro 2024'],
        ],
        'TELERUPTEUR' => [
            'low'  => ['marque' => 'Eur\'ohm', 'reference' => '30116', 'prix' => 12.50, 'catalogue' => 'Rexel 2024'],
            'mid'  => ['marque' => 'Legrand', 'reference' => '412408', 'prix' => 26.80, 'catalogue' => 'Legrand Pro 2024'],
            'high' => ['marque' => 'Hager', 'reference' => 'EPN510', 'prix' => 48.00, 'catalogue' => 'Sonepar 2024'],
        ],
        'CONTACTEUR_HC' => [
            'low'  => ['marque' => 'Eur\'ohm', 'reference' => '30125', 'prix' => 22.00, 'catalogue' => 'Rexel 2024'],
            'mid'  => ['marque' => 'Legrand', 'reference' => '412501', 'prix' => 42.50, 'catalogue' => 'Legrand Pro 2024'],
            'high' => ['marque' => 'Hager', 'reference' => 'ESC225', 'prix' => 72.00, 'catalogue' => 'Sonepar 2024'],
        ],

        // === CABLAGE (prix au metre) ===
        'CABLE_3G15' => [
            'low'  => ['marque' => 'Nexans', 'reference' => 'H07VU-3G1.5', 'prix' => 0.75, 'catalogue' => 'Rexel Cable 2024'],
            'mid'  => ['marque' => 'Nexans', 'reference' => 'R2V-3G1.5', 'prix' => 1.15, 'catalogue' => 'Rexel Cable 2024'],
            'high' => ['marque' => 'Nexans', 'reference' => 'R2V-3G1.5-LSOH', 'prix' => 1.55, 'catalogue' => 'Rexel Cable 2024'],
        ],
        'CABLE_3G25' => [
            'low'  => ['marque' => 'Nexans', 'reference' => 'H07VU-3G2.5', 'prix' => 1.10, 'catalogue' => 'Rexel Cable 2024'],
            'mid'  => ['marque' => 'Nexans', 'reference' => 'R2V-3G2.5', 'prix' => 1.75, 'catalogue' => 'Rexel Cable 2024'],
            'high' => ['marque' => 'Nexans', 'reference' => 'R2V-3G2.5-LSOH', 'prix' => 2.40, 'catalogue' => 'Rexel Cable 2024'],
        ],
        'CABLE_3G6' => [
            'low'  => ['marque' => 'Nexans', 'reference' => 'U1000R2V-3G6', 'prix' => 2.35, 'catalogue' => 'Rexel Cable 2024'],
            'mid'  => ['marque' => 'Nexans', 'reference' => 'R2V-3G6', 'prix' => 3.40, 'catalogue' => 'Rexel Cable 2024'],
            'high' => ['marque' => 'Nexans', 'reference' => 'R2V-3G6-LSOH', 'prix' => 4.80, 'catalogue' => 'Rexel Cable 2024'],
        ],
        'GAINE_ICTA_20' => [
            'low'  => ['marque' => 'Preflex', 'reference' => 'ICTA3422-20', 'prix' => 0.65, 'catalogue' => 'Rexel 2024'],
            'mid'  => ['marque' => 'Legrand', 'reference' => '610003', 'prix' => 1.05, 'catalogue' => 'Legrand Pro 2024'],
            'high' => ['marque' => 'Arnould', 'reference' => 'ICTA-20-LSOH', 'prix' => 1.45, 'catalogue' => 'Sonepar 2024'],
        ],

        // === APPAREILLAGE ===
        'PRISE_2PT' => [
            'low'  => ['marque' => 'Eur\'ohm', 'reference' => 'Square 51110', 'prix' => 3.85, 'catalogue' => 'Rexel 2024'],
            'mid'  => ['marque' => 'Legrand', 'reference' => 'Mosaic 077111', 'prix' => 8.20, 'catalogue' => 'Legrand Pro 2024'],
            'high' => ['marque' => 'Legrand', 'reference' => 'Celiane 067111', 'prix' => 16.50, 'catalogue' => 'Legrand Pro 2024'],
        ],
        'PRISE_DOUBLE' => [
            'low'  => ['marque' => 'Eur\'ohm', 'reference' => 'Square 51120', 'prix' => 7.20, 'catalogue' => 'Rexel 2024'],
            'mid'  => ['marque' => 'Legrand', 'reference' => 'Mosaic 077112', 'prix' => 14.50, 'catalogue' => 'Legrand Pro 2024'],
            'high' => ['marque' => 'Legrand', 'reference' => 'Celiane 067121', 'prix' => 28.00, 'catalogue' => 'Legrand Pro 2024'],
        ],
        'INTER_SA' => [
            'low'  => ['marque' => 'Eur\'ohm', 'reference' => 'Square 51001', 'prix' => 3.20, 'catalogue' => 'Rexel 2024'],
            'mid'  => ['marque' => 'Legrand', 'reference' => 'Mosaic 077010', 'prix' => 7.10, 'catalogue' => 'Legrand Pro 2024'],
            'high' => ['marque' => 'Legrand', 'reference' => 'Celiane 067001', 'prix' => 14.20, 'catalogue' => 'Legrand Pro 2024'],
        ],
        'INTER_VV' => [
            'low'  => ['marque' => 'Eur\'ohm', 'reference' => 'Square 51002', 'prix' => 4.50, 'catalogue' => 'Rexel 2024'],
            'mid'  => ['marque' => 'Legrand', 'reference' => 'Mosaic 077011', 'prix' => 11.20, 'catalogue' => 'Legrand Pro 2024'],
            'high' => ['marque' => 'Legrand', 'reference' => 'Celiane 067002', 'prix' => 21.00, 'catalogue' => 'Legrand Pro 2024'],
        ],
        'PRISE_32A' => [
            'low'  => ['marque' => 'Legrand', 'reference' => '055812', 'prix' => 14.50, 'catalogue' => 'Legrand Pro 2024'],
            'mid'  => ['marque' => 'Legrand', 'reference' => '055814', 'prix' => 26.80, 'catalogue' => 'Legrand Pro 2024'],
            'high' => ['marque' => 'Schneider', 'reference' => 'NU342330', 'prix' => 48.00, 'catalogue' => 'Schneider Pro 2024'],
        ],
        'PRISE_20A' => [
            'low'  => ['marque' => 'Legrand', 'reference' => '055800', 'prix' => 8.50, 'catalogue' => 'Legrand Pro 2024'],
            'mid'  => ['marque' => 'Legrand', 'reference' => '055801', 'prix' => 16.20, 'catalogue' => 'Legrand Pro 2024'],
            'high' => ['marque' => 'Schneider', 'reference' => 'NU342230', 'prix' => 30.00, 'catalogue' => 'Schneider Pro 2024'],
        ],
        'PRISE_RASOIR' => [
            'low'  => ['marque' => 'Legrand', 'reference' => '047135', 'prix' => 32.00, 'catalogue' => 'Legrand Pro 2024'],
            'mid'  => ['marque' => 'Legrand', 'reference' => 'Mosaic 078433', 'prix' => 62.00, 'catalogue' => 'Legrand Pro 2024'],
            'high' => ['marque' => 'Legrand', 'reference' => 'Celiane 067201', 'prix' => 115.00, 'catalogue' => 'Legrand Pro 2024'],
        ],

        // === BOITES ===
        'BOITE_ENCAST' => [
            'low'  => ['marque' => 'BLM', 'reference' => '685067', 'prix' => 0.55, 'catalogue' => 'Rexel 2024'],
            'mid'  => ['marque' => 'Legrand', 'reference' => 'Batibox 080001', 'prix' => 1.15, 'catalogue' => 'Legrand Pro 2024'],
            'high' => ['marque' => 'Schneider', 'reference' => 'ALB71300', 'prix' => 1.95, 'catalogue' => 'Schneider Pro 2024'],
        ],
        'BOITE_DERIV' => [
            'low'  => ['marque' => 'Gewiss', 'reference' => 'GW44004', 'prix' => 2.20, 'catalogue' => 'Rexel 2024'],
            'mid'  => ['marque' => 'Legrand', 'reference' => 'Plexo 092012', 'prix' => 4.30, 'catalogue' => 'Legrand Pro 2024'],
            'high' => ['marque' => 'Schneider', 'reference' => 'Mureva ENT61024', 'prix' => 7.80, 'catalogue' => 'Schneider Pro 2024'],
        ],

        // === ECLAIRAGE ===
        'SPOT_LED' => [
            'low'  => ['marque' => 'Xanlite', 'reference' => 'PACK3SPW7', 'prix' => 11.50, 'catalogue' => 'Rexel Eclairage 2024'],
            'mid'  => ['marque' => 'Philips', 'reference' => 'CoreLine 33360099', 'prix' => 23.80, 'catalogue' => 'Philips Pro 2024'],
            'high' => ['marque' => 'SLV', 'reference' => 'NEW TRIA 1001938', 'prix' => 42.00, 'catalogue' => 'SLV Pro 2024'],
        ],
        'PLAFONNIER_LED' => [
            'low'  => ['marque' => 'Xanlite', 'reference' => 'PLRF240W', 'prix' => 22.00, 'catalogue' => 'Rexel Eclairage 2024'],
            'mid'  => ['marque' => 'Philips', 'reference' => 'CoreLine CL550', 'prix' => 42.50, 'catalogue' => 'Philips Pro 2024'],
            'high' => ['marque' => 'Trilux', 'reference' => 'Arimo Fit', 'prix' => 85.00, 'catalogue' => 'Trilux Pro 2024'],
        ],

        // === VMC ===
        'VMC_HYGRO' => [
            'low'  => ['marque' => 'Atlantic', 'reference' => 'Autocosy IH Flex 422263', 'prix' => 165.00, 'catalogue' => 'Sonepar CVC 2024'],
            'mid'  => ['marque' => 'Aldes', 'reference' => 'EasyHOME Hygro Premium 11033017', 'prix' => 298.00, 'catalogue' => 'Aldes Pro 2024'],
            'high' => ['marque' => 'Aldes', 'reference' => 'InspirAIR Home SC240 11033034', 'prix' => 520.00, 'catalogue' => 'Aldes Pro 2024'],
        ],
        'BOUCHE_EXTRACTION' => [
            'low'  => ['marque' => 'Atlantic', 'reference' => 'BHB 422100', 'prix' => 11.50, 'catalogue' => 'Sonepar CVC 2024'],
            'mid'  => ['marque' => 'Aldes', 'reference' => 'BAW Color 11015062', 'prix' => 23.50, 'catalogue' => 'Aldes Pro 2024'],
            'high' => ['marque' => 'Aldes', 'reference' => 'BHB Line 11015064', 'prix' => 42.00, 'catalogue' => 'Aldes Pro 2024'],
        ],
        'GAINE_VMC' => [
            'low'  => ['marque' => 'Atlantic', 'reference' => 'Gaine PVC 125', 'prix' => 3.20, 'catalogue' => 'Sonepar CVC 2024'],
            'mid'  => ['marque' => 'Aldes', 'reference' => 'Algaine 11091001', 'prix' => 5.80, 'catalogue' => 'Aldes Pro 2024'],
            'high' => ['marque' => 'Aldes', 'reference' => 'Algaine isole 11091002', 'prix' => 9.50, 'catalogue' => 'Aldes Pro 2024'],
        ],

        // === CHAUFFAGE ===
        'SECHE_SERVIETTE' => [
            'low'  => ['marque' => 'Atlantic', 'reference' => 'Doris Digital 850505', 'prix' => 115.00, 'catalogue' => 'Sonepar CVC 2024'],
            'mid'  => ['marque' => 'Atlantic', 'reference' => 'Adelis Digital 850515', 'prix' => 235.00, 'catalogue' => 'Sonepar CVC 2024'],
            'high' => ['marque' => 'Acova', 'reference' => 'Cala +Air TLNO-050-050', 'prix' => 485.00, 'catalogue' => 'Acova Pro 2024'],
        ],
        'THERMOSTAT_PROGRAMMABLE' => [
            'low'  => ['marque' => 'Delta Dore', 'reference' => 'Minor 12 6050560', 'prix' => 42.00, 'catalogue' => 'Rexel Domotique 2024'],
            'mid'  => ['marque' => 'Delta Dore', 'reference' => 'Tybox 117 6053005', 'prix' => 82.00, 'catalogue' => 'Rexel Domotique 2024'],
            'high' => ['marque' => 'Hager', 'reference' => 'Kallysta EK520', 'prix' => 145.00, 'catalogue' => 'Sonepar 2024'],
        ],
        'RADIATEUR_INERTIE_1500W' => [
            'low'  => ['marque' => 'Cayenne', 'reference' => 'Indiana 49699', 'prix' => 285.00, 'catalogue' => 'Rexel CVC 2024'],
            'mid'  => ['marque' => 'Atlantic', 'reference' => 'Maradja Digital 503115', 'prix' => 520.00, 'catalogue' => 'Sonepar CVC 2024'],
            'high' => ['marque' => 'Thermor', 'reference' => 'Equateur 4 443251', 'prix' => 920.00, 'catalogue' => 'Thermor Pro 2024'],
        ],

        // === CONSOMMABLES ===
        'WAGO' => [
            'low'  => ['marque' => 'Wago', 'reference' => '221-413 (lot 50)', 'prix' => 0.42, 'catalogue' => 'Rexel 2024'],
            'mid'  => ['marque' => 'Wago', 'reference' => '221-415 (lot 50)', 'prix' => 0.78, 'catalogue' => 'Rexel 2024'],
            'high' => ['marque' => 'Wago', 'reference' => '221-615 (lot 50)', 'prix' => 1.25, 'catalogue' => 'Rexel 2024'],
        ],
        'CONSOMMABLES_RENOV' => [
            'low'  => ['marque' => 'Divers', 'reference' => 'LOT-CONSO-ECO', 'prix' => 145.00, 'catalogue' => 'Forfait Rexel'],
            'mid'  => ['marque' => 'Divers', 'reference' => 'LOT-CONSO-STD', 'prix' => 285.00, 'catalogue' => 'Forfait Rexel'],
            'high' => ['marque' => 'Divers', 'reference' => 'LOT-CONSO-PRO', 'prix' => 480.00, 'catalogue' => 'Forfait Rexel'],
        ],
        'GOULOTTE_40x25' => [
            'low'  => ['marque' => 'Iboco', 'reference' => 'T1E 08750', 'prix' => 3.20, 'catalogue' => 'Rexel 2024'],
            'mid'  => ['marque' => 'Legrand', 'reference' => 'DLP 030014', 'prix' => 5.80, 'catalogue' => 'Legrand Pro 2024'],
            'high' => ['marque' => 'Hager', 'reference' => 'Tehalit LF4004009010', 'prix' => 11.50, 'catalogue' => 'Sonepar 2024'],
        ],
        'DETECTEUR_FUMEE' => [
            'low'  => ['marque' => 'Kidde', 'reference' => '29HD-FR', 'prix' => 18.50, 'catalogue' => 'Rexel Securite 2024'],
            'mid'  => ['marque' => 'Honeywell', 'reference' => 'XS100', 'prix' => 42.00, 'catalogue' => 'Rexel Securite 2024'],
            'high' => ['marque' => 'Netatmo', 'reference' => 'NSA-EC', 'prix' => 85.00, 'catalogue' => 'Rexel Domotique 2024'],
        ],
        'ATTESTATION_CONSUEL' => [
            'low'  => ['marque' => 'Consuel', 'reference' => 'Attestation elec', 'prix' => 130.00, 'catalogue' => 'Consuel tarif 2024'],
            'mid'  => ['marque' => 'Consuel', 'reference' => 'Attestation elec', 'prix' => 155.00, 'catalogue' => 'Consuel tarif 2024'],
            'high' => ['marque' => 'Consuel', 'reference' => 'Attestation elec', 'prix' => 180.00, 'catalogue' => 'Consuel tarif 2024'],
        ],

        // === BORNE RECHARGE ===
        'WALLBOX' => [
            'low'  => ['marque' => 'Hager', 'reference' => 'Witty Start XEV101', 'prix' => 485.00, 'catalogue' => 'Sonepar VE 2024'],
            'mid'  => ['marque' => 'Schneider', 'reference' => 'EVlink Home EVH2S7P0CK', 'prix' => 780.00, 'catalogue' => 'Schneider Pro 2024'],
            'high' => ['marque' => 'Legrand', 'reference' => 'Green\'up Premium 059052', 'prix' => 1450.00, 'catalogue' => 'Legrand Pro 2024'],
        ],
        'PROTECTION_BORNE' => [
            'low'  => ['marque' => 'Legrand', 'reference' => '411631', 'prix' => 78.00, 'catalogue' => 'Legrand Pro 2024'],
            'mid'  => ['marque' => 'Schneider', 'reference' => 'Acti9 A9R60240', 'prix' => 145.00, 'catalogue' => 'Schneider Pro 2024'],
            'high' => ['marque' => 'Hager', 'reference' => 'CDS240E', 'prix' => 265.00, 'catalogue' => 'Sonepar 2024'],
        ],
    ];

    /**
     * Cree une ligne de fourniture avec prix depuis le catalogue produit
     * Priorite: catalogue produit > grille de prix > prix par defaut
     */
    private function createLine(string $code, string $designation, int $quantite, string $unite, string $gamme): array
    {
        // Recuperer marque, reference, prix et catalogue depuis le productCatalog
        $marque = null;
        $reference = null;
        $catalogue = null;
        $prixUnitaire = null;

        if (isset($this->productCatalog[$code][$gamme])) {
            $catalogEntry = $this->productCatalog[$code][$gamme];
            $marque = $catalogEntry['marque'];
            $reference = $catalogEntry['reference'];
            $prixUnitaire = $catalogEntry['prix'];
            $catalogue = $catalogEntry['catalogue'];
        }

        // Fallback sur la grille de prix si pas dans le catalogue
        if ($prixUnitaire === null) {
            $prixKey = 'price_' . $gamme;
            $prixUnitaire = $this->priceGrid[$code][$prixKey] ?? 10;
            $catalogue = 'Grille tarifaire interne';
        }

        // Construire designation complete avec marque/ref
        $designationComplete = $designation;
        if ($marque && $reference) {
            $designationComplete = "{$designation} - {$marque} ref. {$reference}";
        }

        return [
            'designation' => $designationComplete,
            'marque' => $marque,
            'reference' => $reference,
            'catalogue' => $catalogue,
            'quantite' => $quantite,
            'unite' => $unite,
            'prix_unitaire_ht' => $prixUnitaire,
            'total_ht' => round($prixUnitaire * $quantite, 2),
        ];
    }
}
