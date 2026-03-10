<?php

/**
 * Script de validation de cohérence entre travaux_definitions.php et technical_needs_details.php
 *
 * Usage :
 *   php scripts/validate_technical_needs_details.php
 *   php scripts/validate_technical_needs_details.php --strict
 *
 * Options :
 *   --strict : Codes manquants traités comme erreurs (pas warnings)
 *
 * Vérifie :
 * - Structure _meta valide (categories_devis, modes_facturation, defaults)
 * - Codes manquants (dans travaux_definitions mais pas dans technical_needs_details)
 * - Codes orphelins (dans technical_needs_details mais pas dans travaux_definitions)
 * - Catégories invalides (non définies dans _meta.categories_devis)
 * - Modes de facturation invalides (non définis dans _meta.modes_facturation)
 * - Champs inconnus (fautes de frappe potentielles)
 * - Regroupements incohérents (même regroupement mais catégories différentes)
 * - Risques de fusion incorrecte (même code+type mais specifications différentes)
 * - Types de champs invalides (visible_devis, mode_facturation, etc.)
 * - Doublons de présentation (même label_devis + categorie_devis + mode_facturation)
 * - Résumé final : erreurs / warnings / stats
 */

declare(strict_types=1);

// =========================================================================
// Configuration et options
// =========================================================================

$strictMode = in_array('--strict', $argv ?? [], true);

const COLOR_RED = "\033[31m";
const COLOR_GREEN = "\033[32m";
const COLOR_YELLOW = "\033[33m";
const COLOR_BLUE = "\033[34m";
const COLOR_RESET = "\033[0m";
const COLOR_BOLD = "\033[1m";

// Champs autorisés dans une entrée de technical_needs_details.php
const ALLOWED_FIELDS = [
    'visible_devis',
    'mode_facturation',
    'categorie_devis',
    'ordre_affichage',
    'label_devis',
    'regroupement',
];

// Types attendus pour chaque champ
const FIELD_TYPES = [
    'visible_devis' => 'bool',
    'mode_facturation' => 'string',
    'categorie_devis' => 'string',
    'ordre_affichage' => 'int',
    'label_devis' => 'string',
    'regroupement' => 'string|null',
];

// =========================================================================
// Fonctions utilitaires
// =========================================================================

/**
 * Normalisation récursive d'un tableau pour comparaison fiable.
 * - Trie les clés des tableaux associatifs
 * - Conserve l'ordre naturel des tableaux indexés
 */
function normalizeArray(mixed $value): string
{
    if (!is_array($value)) {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_null($value)) {
            return 'null';
        }
        return (string) $value;
    }

    if (empty($value)) {
        return '[]';
    }

    $keys = array_keys($value);
    $isAssoc = $keys !== range(0, count($value) - 1);

    if ($isAssoc) {
        ksort($value, SORT_STRING);
        $parts = [];
        foreach ($value as $k => $v) {
            $parts[] = $k . ':' . normalizeArray($v);
        }
        return '{' . implode(',', $parts) . '}';
    } else {
        $normalized = array_map('normalizeArray', $value);
        return '[' . implode(',', $normalized) . ']';
    }
}

/**
 * Valide le type d'une valeur selon la spécification.
 */
function validateType(mixed $value, string $expectedType): bool
{
    $types = explode('|', $expectedType);

    foreach ($types as $type) {
        switch ($type) {
            case 'bool':
                if (is_bool($value)) return true;
                break;
            case 'string':
                if (is_string($value)) return true;
                break;
            case 'int':
                if (is_int($value)) return true;
                break;
            case 'null':
                if (is_null($value)) return true;
                break;
        }
    }

    return false;
}

/**
 * Retourne le type PHP d'une valeur sous forme lisible.
 */
function getReadableType(mixed $value): string
{
    if (is_null($value)) return 'null';
    if (is_bool($value)) return 'bool';
    if (is_int($value)) return 'int';
    if (is_float($value)) return 'float';
    if (is_string($value)) return 'string';
    if (is_array($value)) return 'array';
    return gettype($value);
}

/**
 * Génère une clé de présentation unique et sûre (sans risque de collision).
 */
function makePresentationKey(string $label, string $categorie, string $mode): string
{
    return hash('sha256', json_encode([
        'label' => $label,
        'categorie' => $categorie,
        'mode' => $mode,
    ], JSON_THROW_ON_ERROR));
}

/**
 * Trouve les champs similaires pour suggestion en cas de typo.
 */
function findSimilarFields(string $field, array $allowedFields): array
{
    $similar = [];
    foreach ($allowedFields as $allowed) {
        $distance = levenshtein($field, $allowed);
        if ($distance <= 3 && $distance > 0) {
            $similar[$allowed] = $distance;
        }
    }
    asort($similar);
    return array_keys($similar);
}

// =========================================================================
// Chargement des fichiers
// =========================================================================

$basePath = __DIR__ . '/../config/pipeline_v2/';

if (!file_exists($basePath . 'travaux_definitions.php')) {
    echo COLOR_RED . "ERREUR: travaux_definitions.php introuvable\n" . COLOR_RESET;
    exit(1);
}

if (!file_exists($basePath . 'technical_needs_details.php')) {
    echo COLOR_RED . "ERREUR: technical_needs_details.php introuvable\n" . COLOR_RESET;
    exit(1);
}

$travauxDefinitions = require $basePath . 'travaux_definitions.php';
$technicalNeedsDetails = require $basePath . 'technical_needs_details.php';

// =========================================================================
// Validation structurelle de _meta (erreurs bloquantes)
// =========================================================================

$erreurs = [];
$warnings = [];

$meta = $technicalNeedsDetails['_meta'] ?? null;

if ($meta === null) {
    echo COLOR_RED . COLOR_BOLD . "\n✗ ERREUR FATALE: _meta absent\n" . COLOR_RESET;
    exit(1);
}

if (!isset($meta['categories_devis']) || !is_array($meta['categories_devis']) || empty($meta['categories_devis'])) {
    $erreurs[] = [
        'type' => 'structure_invalide',
        'code' => '_meta',
        'message' => "_meta.categories_devis est absent, invalide ou vide",
    ];
}

if (!isset($meta['modes_facturation']) || !is_array($meta['modes_facturation']) || empty($meta['modes_facturation'])) {
    $erreurs[] = [
        'type' => 'structure_invalide',
        'code' => '_meta',
        'message' => "_meta.modes_facturation est absent, invalide ou vide",
    ];
}

if (!isset($meta['defaults']) || !is_array($meta['defaults'])) {
    $erreurs[] = [
        'type' => 'structure_invalide',
        'code' => '_meta',
        'message' => "_meta.defaults est absent ou invalide",
    ];
}

$structureErrors = array_filter($erreurs, fn($e) => $e['type'] === 'structure_invalide');
if (count($structureErrors) > 0) {
    echo COLOR_RED . COLOR_BOLD . "\n✗ ERREURS STRUCTURELLES CRITIQUES:\n" . COLOR_RESET;
    foreach ($structureErrors as $err) {
        echo COLOR_RED . "  - " . $err['message'] . "\n" . COLOR_RESET;
    }
    echo "\nCorrection requise avant de continuer.\n\n";
    exit(1);
}

$categoriesAutorisees = array_keys($meta['categories_devis']);
$modesAutorises = array_keys($meta['modes_facturation']);
$defaults = $meta['defaults'];

// =========================================================================
// Fonctions d'extraction
// =========================================================================

function extractCodesFromTravauxDefinitions(array $definitions): array
{
    $codes = [];

    foreach ($definitions as $travailId => $travail) {
        if ($travailId === '_meta' || !is_array($travail)) {
            continue;
        }

        $sousTravaux = $travail['sous_travaux'] ?? [];
        foreach ($sousTravaux as $sousTravailId => $sousTravail) {
            $besoins = $sousTravail['besoins_techniques'] ?? [];
            foreach ($besoins as $besoin) {
                if (!isset($besoin['code'])) {
                    continue;
                }

                $code = $besoin['code'];
                $type = $besoin['type'] ?? 'unknown';

                $specs = $besoin['specifications'] ?? $besoin['selection'] ?? [];
                $specsNormalized = normalizeArray($specs);

                if (!isset($codes[$code])) {
                    $codes[$code] = [
                        'type' => $type,
                        'sources' => [],
                        'specifications_variants' => [],
                    ];
                }

                $codes[$code]['sources'][] = [
                    'travail' => $travailId,
                    'sous_travail' => $sousTravailId,
                    'specs' => $specsNormalized,
                ];

                if ($specsNormalized !== '[]' && !in_array($specsNormalized, $codes[$code]['specifications_variants'], true)) {
                    $codes[$code]['specifications_variants'][] = $specsNormalized;
                }
            }
        }
    }

    return $codes;
}

function extractCodesFromDetails(array $details): array
{
    $codes = [];
    foreach ($details as $code => $config) {
        if ($code === '_meta') {
            continue;
        }
        $codes[$code] = $config;
    }
    return $codes;
}

// =========================================================================
// Extraction et analyse
// =========================================================================

$codesTravauxDef = extractCodesFromTravauxDefinitions($travauxDefinitions);
$codesDetails = extractCodesFromDetails($technicalNeedsDetails);

$codesTravauxDefSet = array_keys($codesTravauxDef);
$codesDetailsSet = array_keys($codesDetails);

$codesCouverts = array_intersect($codesTravauxDefSet, $codesDetailsSet);
$codesManquants = array_diff($codesTravauxDefSet, $codesDetailsSet);
$codesOrphelins = array_diff($codesDetailsSet, $codesTravauxDefSet);

// =========================================================================
// Validation des règles
// =========================================================================

// 0. Validation des defaults
if (isset($defaults['categorie_devis'])) {
    if (!in_array($defaults['categorie_devis'], $categoriesAutorisees, true)) {
        $erreurs[] = [
            'type' => 'meta_invalide',
            'code' => '_meta',
            'message' => "defaults.categorie_devis '{$defaults['categorie_devis']}' n'existe pas dans categories_devis",
        ];
    }
}

if (isset($defaults['mode_facturation'])) {
    if (!in_array($defaults['mode_facturation'], $modesAutorises, true)) {
        $erreurs[] = [
            'type' => 'meta_invalide',
            'code' => '_meta',
            'message' => "defaults.mode_facturation '{$defaults['mode_facturation']}' n'existe pas dans modes_facturation",
        ];
    }
}

// 1. Validation des champs inconnus (fautes de frappe)
foreach ($codesDetails as $code => $config) {
    foreach (array_keys($config) as $field) {
        if (!in_array($field, ALLOWED_FIELDS, true)) {
            $suggestion = '';
            $similar = findSimilarFields($field, ALLOWED_FIELDS);
            if (count($similar) > 0) {
                $suggestion = sprintf(" (vouliez-vous dire '%s' ?)", $similar[0]);
            }

            $erreurs[] = [
                'type' => 'champ_inconnu',
                'code' => $code,
                'message' => "Champ '$field' non reconnu$suggestion",
            ];
        }
    }
}

// 2. Validation des types de champs
foreach ($codesDetails as $code => $config) {
    foreach (FIELD_TYPES as $field => $expectedType) {
        if (!array_key_exists($field, $config)) {
            continue;
        }

        $value = $config[$field];
        if (!validateType($value, $expectedType)) {
            $erreurs[] = [
                'type' => 'type_invalide',
                'code' => $code,
                'message' => sprintf(
                    "Champ '%s' : attendu %s, reçu %s",
                    $field,
                    $expectedType,
                    getReadableType($value)
                ),
            ];
        }
    }
}

// 3. Catégories invalides
foreach ($codesDetails as $code => $config) {
    if (isset($config['categorie_devis'])) {
        $cat = $config['categorie_devis'];
        if (!in_array($cat, $categoriesAutorisees, true)) {
            $erreurs[] = [
                'type' => 'categorie_invalide',
                'code' => $code,
                'message' => "Catégorie '$cat' non définie dans _meta.categories_devis",
            ];
        }
    }
}

// 4. Modes de facturation invalides
foreach ($codesDetails as $code => $config) {
    if (isset($config['mode_facturation'])) {
        $mode = $config['mode_facturation'];
        if (!in_array($mode, $modesAutorises, true)) {
            $erreurs[] = [
                'type' => 'mode_invalide',
                'code' => $code,
                'message' => "Mode '$mode' non défini dans _meta.modes_facturation",
            ];
        }
    }
}

// 5. Regroupements incohérents
$regroupements = [];
foreach ($codesDetails as $code => $config) {
    $regroupement = $config['regroupement'] ?? null;
    if ($regroupement === null) {
        continue;
    }

    $categorie = $config['categorie_devis'] ?? $defaults['categorie_devis'] ?? 'divers';
    $mode = $config['mode_facturation'] ?? $defaults['mode_facturation'] ?? 'ligne';

    if (!isset($regroupements[$regroupement])) {
        $regroupements[$regroupement] = [
            'categorie' => $categorie,
            'mode' => $mode,
            'codes' => [$code],
        ];
    } else {
        $regroupements[$regroupement]['codes'][] = $code;

        if ($regroupements[$regroupement]['categorie'] !== $categorie) {
            $erreurs[] = [
                'type' => 'regroupement_incoherent',
                'code' => $code,
                'message' => "Regroupement '$regroupement' : catégorie '$categorie' != '{$regroupements[$regroupement]['categorie']}'",
            ];
        }

        if ($regroupements[$regroupement]['mode'] !== $mode) {
            $warnings[] = [
                'type' => 'regroupement_mode_different',
                'code' => $code,
                'message' => "Regroupement '$regroupement' : mode '$mode' != '{$regroupements[$regroupement]['mode']}'",
            ];
        }
    }
}

// 6. Risques de fusion incorrecte
foreach ($codesTravauxDef as $code => $info) {
    $nbVariants = count($info['specifications_variants']);

    if ($nbVariants > 1) {
        $variantsPreview = array_map(
            fn($v) => strlen($v) > 50 ? substr($v, 0, 47) . '...' : $v,
            array_slice($info['specifications_variants'], 0, 3)
        );

        $warnings[] = [
            'type' => 'fusion_risque',
            'code' => $code,
            'message' => sprintf(
                "Code '%s' a %d variantes de specifications.\n" .
                "      L'agrégation finale DOIT intégrer les specifications normalisées dans sa clé de fusion.\n" .
                "      Variantes: %s%s",
                $code,
                $nbVariants,
                implode(', ', $variantsPreview),
                $nbVariants > 3 ? ', ...' : ''
            ),
        ];
    }
}

// 7. Doublons de présentation potentiels (clé sécurisée avec hash)
$presentations = [];
$presentationDetails = [];

foreach ($codesDetails as $code => $config) {
    $label = $config['label_devis'] ?? null;
    if ($label === null) {
        continue;
    }

    $categorie = $config['categorie_devis'] ?? $defaults['categorie_devis'] ?? 'divers';
    $mode = $config['mode_facturation'] ?? $defaults['mode_facturation'] ?? 'ligne';

    $key = makePresentationKey($label, $categorie, $mode);

    if (!isset($presentations[$key])) {
        $presentations[$key] = [];
        $presentationDetails[$key] = [
            'label' => $label,
            'categorie' => $categorie,
            'mode' => $mode,
        ];
    }
    $presentations[$key][] = $code;
}

foreach ($presentations as $key => $codes) {
    if (count($codes) > 1) {
        $details = $presentationDetails[$key];
        $warnings[] = [
            'type' => 'doublon_presentation',
            'code' => implode(', ', $codes),
            'message' => sprintf(
                "Doublons de présentation détectés.\n" .
                "      Label: '%s', Catégorie: '%s', Mode: '%s'\n" .
                "      Codes concernés: %s\n" .
                "      Risque d'ambiguïté dans le rendu du devis.",
                $details['label'],
                $details['categorie'],
                $details['mode'],
                implode(', ', $codes)
            ),
        ];
    }
}

// 8. Codes manquants
foreach ($codesManquants as $code) {
    $info = $codesTravauxDef[$code];
    $entry = [
        'type' => 'code_manquant',
        'code' => $code,
        'message' => sprintf(
            "Code '%s' [%s] absent de technical_needs_details.php%s",
            $code,
            $info['type'],
            $strictMode ? '' : ' (defaults appliqués)'
        ),
    ];

    if ($strictMode) {
        $erreurs[] = $entry;
    } else {
        $warnings[] = $entry;
    }
}

// 9. Codes orphelins
foreach ($codesOrphelins as $code) {
    $erreurs[] = [
        'type' => 'code_orphelin',
        'code' => $code,
        'message' => "Code '$code' défini mais jamais utilisé dans travaux_definitions.php",
    ];
}

// =========================================================================
// Statistiques
// =========================================================================

$statsByCategorie = [];
$statsByType = [];
$statsByMode = [];

foreach ($codesCouverts as $code) {
    $detail = $codesDetails[$code];
    $info = $codesTravauxDef[$code];

    $cat = $detail['categorie_devis'] ?? $defaults['categorie_devis'] ?? 'divers';
    $mode = $detail['mode_facturation'] ?? $defaults['mode_facturation'] ?? 'ligne';
    $type = $info['type'];

    $statsByCategorie[$cat] = ($statsByCategorie[$cat] ?? 0) + 1;
    $statsByType[$type] = ($statsByType[$type] ?? 0) + 1;
    $statsByMode[$mode] = ($statsByMode[$mode] ?? 0) + 1;
}

// =========================================================================
// Affichage
// =========================================================================

echo "\n";
echo COLOR_BOLD . "═══════════════════════════════════════════════════════════════════════════\n";
echo "  VALIDATION TECHNICAL_NEEDS_DETAILS.PHP";
if ($strictMode) {
    echo " [MODE STRICT]";
}
echo "\n═══════════════════════════════════════════════════════════════════════════\n" . COLOR_RESET;
echo "\n";

echo COLOR_BOLD . "STATISTIQUES GÉNÉRALES\n" . COLOR_RESET;
echo "───────────────────────────────────────────────────────────────────────────\n";
echo sprintf("  Codes dans travaux_definitions.php    : %d\n", count($codesTravauxDefSet));
echo sprintf("  Codes dans technical_needs_details.php: %d\n", count($codesDetailsSet));
echo sprintf("  Codes couverts                        : %d (%.1f%%)\n",
    count($codesCouverts),
    count($codesTravauxDefSet) > 0 ? count($codesCouverts) / count($codesTravauxDefSet) * 100 : 0
);
echo sprintf("  Codes manquants                       : %d\n", count($codesManquants));
echo sprintf("  Codes orphelins                       : %d\n", count($codesOrphelins));
echo "\n";

echo COLOR_BOLD . "RÉPARTITION PAR TYPE DE BESOIN\n" . COLOR_RESET;
echo "───────────────────────────────────────────────────────────────────────────\n";
arsort($statsByType);
foreach ($statsByType as $type => $count) {
    echo sprintf("  %-20s : %3d codes\n", $type, $count);
}
echo "\n";

echo COLOR_BOLD . "RÉPARTITION PAR CATÉGORIE DEVIS\n" . COLOR_RESET;
echo "───────────────────────────────────────────────────────────────────────────\n";
arsort($statsByCategorie);
foreach ($statsByCategorie as $cat => $count) {
    $status = in_array($cat, $categoriesAutorisees, true) ? COLOR_GREEN . '✓' : COLOR_RED . '✗';
    echo sprintf("  %s" . COLOR_RESET . " %-20s : %3d codes\n", $status, $cat, $count);
}
echo "\n";

echo COLOR_BOLD . "RÉPARTITION PAR MODE DE FACTURATION\n" . COLOR_RESET;
echo "───────────────────────────────────────────────────────────────────────────\n";
foreach ($statsByMode as $mode => $count) {
    $status = in_array($mode, $modesAutorises, true) ? COLOR_GREEN . '✓' : COLOR_RED . '✗';
    echo sprintf("  %s" . COLOR_RESET . " %-20s : %3d codes\n", $status, $mode, $count);
}
echo "\n";

if (count($regroupements) > 0) {
    echo COLOR_BOLD . "REGROUPEMENTS DÉFINIS (" . count($regroupements) . ")\n" . COLOR_RESET;
    echo "───────────────────────────────────────────────────────────────────────────\n";
    ksort($regroupements);
    foreach ($regroupements as $regroupement => $info) {
        echo sprintf("  %-25s : %d codes [%s, %s]\n",
            $regroupement,
            count($info['codes']),
            $info['categorie'],
            $info['mode']
        );
    }
    echo "\n";
}

if (count($erreurs) > 0) {
    echo COLOR_RED . COLOR_BOLD . "ERREURS (" . count($erreurs) . ")\n" . COLOR_RESET;
    echo "───────────────────────────────────────────────────────────────────────────\n";
    $erreursByType = [];
    foreach ($erreurs as $erreur) {
        $erreursByType[$erreur['type']][] = $erreur;
    }
    foreach ($erreursByType as $type => $errs) {
        echo COLOR_RED . "  [$type] (" . count($errs) . ")\n" . COLOR_RESET;
        $displayed = 0;
        foreach ($errs as $err) {
            if ($displayed >= 15 && count($errs) > 20) {
                echo "    ... et " . (count($errs) - 15) . " autres\n";
                break;
            }
            $lines = explode("\n", $err['message']);
            echo "    ✗ " . $lines[0] . "\n";
            foreach (array_slice($lines, 1) as $line) {
                echo "      " . $line . "\n";
            }
            $displayed++;
        }
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo COLOR_YELLOW . COLOR_BOLD . "WARNINGS (" . count($warnings) . ")\n" . COLOR_RESET;
    echo "───────────────────────────────────────────────────────────────────────────\n";
    $warningsByType = [];
    foreach ($warnings as $warning) {
        $warningsByType[$warning['type']][] = $warning;
    }
    foreach ($warningsByType as $type => $warns) {
        echo COLOR_YELLOW . "  [$type] (" . count($warns) . ")\n" . COLOR_RESET;
        $displayed = 0;
        foreach ($warns as $warn) {
            if ($displayed >= 10 && count($warns) > 15) {
                echo "    ... et " . (count($warns) - 10) . " autres\n";
                break;
            }
            $lines = explode("\n", $warn['message']);
            echo "    ⚠ " . $lines[0] . "\n";
            foreach (array_slice($lines, 1) as $line) {
                echo "      " . $line . "\n";
            }
            $displayed++;
        }
    }
    echo "\n";
}

if (!$strictMode && count($codesManquants) > 0 && count($codesManquants) <= 25) {
    echo COLOR_BLUE . COLOR_BOLD . "CODES MANQUANTS DÉTAILLÉS (utiliseront defaults)\n" . COLOR_RESET;
    echo "───────────────────────────────────────────────────────────────────────────\n";
    sort($codesManquants);
    foreach ($codesManquants as $code) {
        $info = $codesTravauxDef[$code];
        $sources = array_map(fn($s) => $s['travail'], $info['sources']);
        $sources = array_unique($sources);
        echo sprintf("  %-35s [%-12s] %s\n",
            $code,
            $info['type'],
            implode(', ', array_slice($sources, 0, 2))
        );
    }
    echo "\n";
}

echo COLOR_BOLD . "═══════════════════════════════════════════════════════════════════════════\n";
echo "  RÉSUMÉ FINAL\n";
echo "═══════════════════════════════════════════════════════════════════════════\n" . COLOR_RESET;

$totalErreurs = count($erreurs);
$totalWarnings = count($warnings);

echo sprintf("  Erreurs   : %s%d%s\n",
    $totalErreurs > 0 ? COLOR_RED : COLOR_GREEN,
    $totalErreurs,
    COLOR_RESET
);
echo sprintf("  Warnings  : %s%d%s\n",
    $totalWarnings > 0 ? COLOR_YELLOW : COLOR_GREEN,
    $totalWarnings,
    COLOR_RESET
);
echo sprintf("  Couverture: %.1f%% (%d/%d codes)\n",
    count($codesTravauxDefSet) > 0 ? count($codesCouverts) / count($codesTravauxDefSet) * 100 : 0,
    count($codesCouverts),
    count($codesTravauxDefSet)
);
echo "\n";

if ($totalErreurs === 0 && $totalWarnings === 0) {
    echo COLOR_GREEN . COLOR_BOLD . "  ✓ VALIDATION RÉUSSIE - Aucun problème détecté\n" . COLOR_RESET;
    $exitCode = 0;
} elseif ($totalErreurs === 0) {
    echo COLOR_YELLOW . COLOR_BOLD . "  ⚠ VALIDATION OK AVEC WARNINGS\n" . COLOR_RESET;
    if (!$strictMode) {
        echo "    Les codes manquants utiliseront les valeurs par défaut.\n";
        echo "    Utiliser --strict pour traiter les codes manquants comme erreurs.\n";
    }
    $exitCode = 0;
} else {
    echo COLOR_RED . COLOR_BOLD . "  ✗ VALIDATION ÉCHOUÉE - Corriger les erreurs\n" . COLOR_RESET;
    $exitCode = 1;
}

echo "\n";

exit($exitCode);
