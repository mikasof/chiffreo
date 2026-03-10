<?php

/**
 * Script de démonstration du WorkTreeBuilder.
 *
 * Exécution : php demo_worktree_builder.php
 *
 * Produit une sortie lisible pour chaque macro-chantier.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use App\PipelineV2\WorkTreeBuilder\WorkTreeBuilder;
use App\PipelineV2\WorkTreeBuilder\DTO\WorkTreeBuilderInput;
use App\PipelineV2\WorkTreeBuilder\DTO\WorkTree;

// ============================================================================
// Configuration
// ============================================================================

$configDir = __DIR__ . '/../../../config/pipeline_v2';
$builder = new WorkTreeBuilder(
    $configDir . '/chantier_types.php',
    $configDir . '/travaux_definitions.php'
);

// ============================================================================
// Scénarios de test pour chaque macro-chantier
// ============================================================================

$scenarios = [
    // -------------------------------------------------------------------------
    // 1. Rénovation complète maison
    // -------------------------------------------------------------------------
    [
        'nom' => 'Rénovation complète maison (contexte complet)',
        'input' => new WorkTreeBuilderInput(
            typeChantier: 'renovation_complete_maison',
            contexte: [
                'surface' => 120,
                'nb_pieces' => 7,
                'nb_pieces_humides' => 3,
                'nb_etages' => 2,
                'annee_construction' => 1975,
                'puissance_abonnement' => '12kVA',
                'type_chauffage' => 'electrique',
                'cuisine' => true,
            ]
        ),
    ],
    [
        'nom' => 'Rénovation complète maison (contexte minimal)',
        'input' => new WorkTreeBuilderInput(
            typeChantier: 'renovation_complete_maison',
            contexte: [
                'surface' => 60,
                'nb_pieces' => 3,
                'annee_construction' => 2000,
            ]
        ),
    ],
    [
        'nom' => 'Rénovation complète maison (contexte incomplet)',
        'input' => new WorkTreeBuilderInput(
            typeChantier: 'renovation_complete_maison',
            contexte: []
        ),
    ],

    // -------------------------------------------------------------------------
    // 2. Remplacement tableau
    // -------------------------------------------------------------------------
    [
        'nom' => 'Remplacement tableau (standard)',
        'input' => new WorkTreeBuilderInput(
            typeChantier: 'remplacement_tableau',
            contexte: [
                'nb_circuits_existants' => 14,
                'puissance_abonnement' => '9kVA',
            ]
        ),
    ],
    [
        'nom' => 'Remplacement tableau (avec chauffe-eau HP/HC)',
        'input' => new WorkTreeBuilderInput(
            typeChantier: 'remplacement_tableau',
            contexte: [
                'nb_circuits_existants' => 16,
                'puissance_abonnement' => '12kVA',
                'chauffe_eau_electrique' => true,
                'abonnement_hphc' => true,
            ]
        ),
    ],

    // -------------------------------------------------------------------------
    // 3. Borne IRVE
    // -------------------------------------------------------------------------
    [
        'nom' => 'Borne IRVE 7.4kW simple',
        'input' => new WorkTreeBuilderInput(
            typeChantier: 'borne_irve',
            contexte: [
                'puissance_borne' => '7.4kW (mono)',
                'distance_tableau' => 8,
                'installation_existante' => 'monophase',
            ]
        ),
    ],
    [
        'nom' => 'Borne IRVE 22kW avec coffret secondaire',
        'input' => new WorkTreeBuilderInput(
            typeChantier: 'borne_irve',
            contexte: [
                'puissance_borne' => '22kW (tri)',
                'puissance_borne_kw' => 22,
                'distance_tableau' => 35,
                'installation_existante' => 'triphase',
                'passage_exterieur' => true,
            ]
        ),
    ],

    // -------------------------------------------------------------------------
    // 4. Cuisine
    // -------------------------------------------------------------------------
    [
        'nom' => 'Cuisine standard',
        'input' => new WorkTreeBuilderInput(
            typeChantier: 'cuisine',
            contexte: []
        ),
    ],
    [
        'nom' => 'Cuisine équipée avec îlot',
        'input' => new WorkTreeBuilderInput(
            typeChantier: 'cuisine',
            contexte: [
                'hotte' => true,
                'ilot_central' => true,
                'lave_linge_cuisine' => true,
            ]
        ),
    ],

    // -------------------------------------------------------------------------
    // 5. Salle de bain
    // -------------------------------------------------------------------------
    [
        'nom' => 'Salle de bain standard',
        'input' => new WorkTreeBuilderInput(
            typeChantier: 'salle_de_bain',
            contexte: [
                'surface_sdb' => 6,
            ]
        ),
    ],

    // -------------------------------------------------------------------------
    // 6. VMC
    // -------------------------------------------------------------------------
    [
        'nom' => 'VMC simple flux',
        'input' => new WorkTreeBuilderInput(
            typeChantier: 'vmc',
            contexte: [
                'type_vmc' => 'simple_flux',
                'nb_bouches_extraction' => 4,
            ]
        ),
    ],
];

// ============================================================================
// Exécution et affichage
// ============================================================================

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                    DÉMONSTRATION DU WORKTREEBUILDER                          ║\n";
echo "╚══════════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

foreach ($scenarios as $index => $scenario) {
    $numero = $index + 1;

    echo "┌──────────────────────────────────────────────────────────────────────────────┐\n";
    echo "│ Scénario $numero: {$scenario['nom']}\n";
    echo "└──────────────────────────────────────────────────────────────────────────────┘\n";

    try {
        $workTree = $builder->build($scenario['input']);
        printWorkTreeSummary($workTree);
    } catch (\Exception $e) {
        echo "  ❌ ERREUR: {$e->getMessage()}\n";
    }

    echo "\n";
}

// ============================================================================
// Fonctions d'affichage
// ============================================================================

function printWorkTreeSummary(WorkTree $workTree): void
{
    echo "\n";
    echo "  📋 TYPE: {$workTree->typeChantier}\n";
    echo "  📝 LABEL: {$workTree->labelChantier}\n";
    echo "  ✅ CONTEXTE COMPLET: " . ($workTree->isContexteComplet() ? 'Oui' : 'Non') . "\n";
    echo "\n";

    // Contexte
    echo "  📊 CONTEXTE:\n";
    foreach ($workTree->contexteComplet as $key => $value) {
        $valueStr = is_bool($value) ? ($value ? 'true' : 'false') : (string)$value;
        if (is_array($value)) {
            $valueStr = json_encode($value);
        }
        echo "     • $key: $valueStr\n";
    }
    echo "\n";

    // Travaux
    echo "  🔧 TRAVAUX ACTIVÉS (" . count($workTree->travaux) . "):\n";
    foreach ($workTree->travaux as $travail) {
        $activation = $travail->activation->raison;
        $origine = $travail->origine;
        echo "     ├─ [{$travail->ordre}] {$travail->id}\n";
        echo "     │     Label: {$travail->label}\n";
        echo "     │     Activation: $activation | Origine: $origine\n";
        echo "     │     Instance: {$travail->instanceId}\n";

        if (!empty($travail->sousTravaux)) {
            echo "     │     Sous-travaux (" . count($travail->sousTravaux) . "):\n";
            foreach ($travail->sousTravaux as $sousTravail) {
                $qte = $sousTravail->quantiteFinale ?? '?';
                $delegue = $sousTravail->delegueNormes ? ' [DÉLÉGUÉ NORMES]' : '';
                echo "     │       • [{$sousTravail->ordre}] {$sousTravail->id} (qté: $qte)$delegue\n";
            }
        }
        echo "     │\n";
    }

    // Questions manquantes
    if (!empty($workTree->questionsManquantes)) {
        echo "  ❓ QUESTIONS MANQUANTES (" . count($workTree->questionsManquantes) . "):\n";
        foreach ($workTree->questionsManquantes as $question) {
            $obligatoire = $question->obligatoirePourChiffrage ? '⚠️ OBLIGATOIRE' : 'optionnel';
            echo "     • [{$question->priorite}] {$question->id}: {$question->question} ($obligatoire)\n";
        }
        echo "\n";
    }

    // Délégations normes
    if (!empty($workTree->delegationsNormes)) {
        echo "  📐 DÉLÉGATIONS NORMES (" . count($workTree->delegationsNormes) . "):\n";
        foreach ($workTree->delegationsNormes as $delegation) {
            echo "     • {$delegation->type} → {$delegation->travailInstanceId}\n";
        }
        echo "\n";
    }

    // Domaines non couverts
    if (!empty($workTree->domainesNonCouverts)) {
        echo "  ⚠️ DOMAINES NON COUVERTS (" . count($workTree->domainesNonCouverts) . "):\n";
        foreach ($workTree->domainesNonCouverts as $domaine) {
            $bloquant = $domaine->bloquant ? '🚫 BLOQUANT' : '⚠️ Alerte';
            echo "     • {$domaine->domaine}: {$domaine->actionRequise} ($bloquant)\n";
        }
        echo "\n";
    }

    // Métadonnées
    echo "  ⏱️ META:\n";
    echo "     • Version: {$workTree->meta->versionConfig}\n";
    echo "     • Étape: {$workTree->meta->etapeCourante}\n";
    echo "     • Timestamp: {$workTree->meta->timestamp->format('Y-m-d H:i:s')}\n";
}
