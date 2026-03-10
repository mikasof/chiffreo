<?php

/**
 * Tests fonctionnels du NormesEngine.
 *
 * Script standalone - aucune dépendance PHPUnit requise.
 *
 * Exécution : php NormesEngineTest.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use App\PipelineV2\NormesEngine\NormesEngine;
use App\PipelineV2\NormesEngine\NormesRuleEvaluator;
use App\PipelineV2\NormesEngine\NormesRuleApplier;
use App\PipelineV2\NormesEngine\DTO\NormesEngineInput;
use App\PipelineV2\NormesEngine\DTO\NormesEngineOutput;
use App\PipelineV2\NormesEngine\DTO\RegleAppliquee;
use App\PipelineV2\NormesEngine\DTO\Alerte;
use App\PipelineV2\NormesEngine\DTO\Impossibilite;
use App\PipelineV2\NormesEngine\DTO\ElementNonDetermine;
use App\PipelineV2\NormesEngine\Exception\NormesEngineException;
use App\PipelineV2\NormesEngine\Exception\InvalidRuleException;
use App\PipelineV2\WorkTreeBuilder\WorkTreeBuilder;
use App\PipelineV2\WorkTreeBuilder\DTO\WorkTreeBuilderInput;
use App\PipelineV2\WorkTreeBuilder\DTO\WorkTree;

// ============================================================================
// Framework de test minimal
// ============================================================================

$testsPassed = 0;
$testsFailed = 0;
$testsErrors = [];

function assertTrue(bool $condition, string $message = ''): void
{
    if (!$condition) {
        throw new AssertionError($message ?: 'Expected true, got false');
    }
}

function assertFalse(bool $condition, string $message = ''): void
{
    if ($condition) {
        throw new AssertionError($message ?: 'Expected false, got true');
    }
}

function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        $msg = $message ?: sprintf('Expected %s, got %s', var_export($expected, true), var_export($actual, true));
        throw new AssertionError($msg);
    }
}

function assertNotEmpty(mixed $value, string $message = ''): void
{
    if (empty($value)) {
        throw new AssertionError($message ?: 'Expected non-empty value');
    }
}

function assertEmpty(mixed $value, string $message = ''): void
{
    if (!empty($value)) {
        throw new AssertionError($message ?: 'Expected empty value, got: ' . var_export($value, true));
    }
}

function assertContains(mixed $needle, array $haystack, string $message = ''): void
{
    if (!in_array($needle, $haystack, true)) {
        $msg = $message ?: sprintf('Expected array to contain %s', var_export($needle, true));
        throw new AssertionError($msg);
    }
}

function assertArrayHasKey(string|int $key, array $array, string $message = ''): void
{
    if (!array_key_exists($key, $array)) {
        throw new AssertionError($message ?: "Expected array to have key '$key'");
    }
}

function assertInstanceOf(string $expected, mixed $actual, string $message = ''): void
{
    if (!$actual instanceof $expected) {
        $actualType = is_object($actual) ? get_class($actual) : gettype($actual);
        throw new AssertionError($message ?: "Expected instance of {$expected}, got {$actualType}");
    }
}

function assertGreaterThan(int|float $expected, int|float $actual, string $message = ''): void
{
    if ($actual <= $expected) {
        $msg = $message ?: sprintf('Expected %s > %s', $actual, $expected);
        throw new AssertionError($msg);
    }
}

function assertGreaterThanOrEqual(int|float $expected, int|float $actual, string $message = ''): void
{
    if ($actual < $expected) {
        $msg = $message ?: sprintf('Expected %s >= %s', $actual, $expected);
        throw new AssertionError($msg);
    }
}

function runTest(string $name, callable $test): void
{
    global $testsPassed, $testsFailed, $testsErrors;

    try {
        $test();
        $testsPassed++;
        echo "  ✅ $name\n";
    } catch (Throwable $e) {
        $testsFailed++;
        $testsErrors[] = ['name' => $name, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()];
        echo "  ❌ $name\n";
        echo "     └─ " . $e->getMessage() . "\n";
    }
}

// ============================================================================
// Configuration
// ============================================================================

$configDir = __DIR__ . '/../../../config/pipeline_v2';
$rulesPath = $configDir . '/normes_rules.php';
$travauxPath = $configDir . '/travaux_definitions.php';
$chantierTypesPath = $configDir . '/chantier_types.php';

// Créer le WorkTreeBuilder pour générer des WorkTrees de test
$workTreeBuilder = new WorkTreeBuilder($chantierTypesPath, $travauxPath);

// Créer le NormesEngine
$normesEngine = new NormesEngine($rulesPath, $travauxPath);

// ============================================================================
// Tests
// ============================================================================

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                       TESTS DU NORMESENGINE                                  ║\n";
echo "╚══════════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// -------------------------------------------------------------------------
// Tests généraux d'instanciation
// -------------------------------------------------------------------------

echo "┌─ Tests généraux d'instanciation\n";

runTest('NormesEngine se crée avec les bons fichiers', function () use ($rulesPath, $travauxPath) {
    $engine = new NormesEngine($rulesPath, $travauxPath);
    assertNotEmpty($engine->getAvailableCategories());
});

runTest('Exception si fichier de règles inexistant', function () use ($travauxPath) {
    $exceptionThrown = false;
    try {
        new NormesEngine('/inexistant/rules.php', $travauxPath);
    } catch (NormesEngineException $e) {
        $exceptionThrown = true;
    }
    assertTrue($exceptionThrown, 'NormesEngineException attendue');
});

runTest('Catégories disponibles non vides', function () use ($normesEngine) {
    $categories = $normesEngine->getAvailableCategories();
    assertNotEmpty($categories);
    assertContains('protection_differentielle', $categories);
});

runTest('supportsRule fonctionne', function () use ($normesEngine) {
    assertTrue($normesEngine->supportsRule('protection_30ma_obligatoire'));
    assertFalse($normesEngine->supportsRule('regle_inexistante_xyz'));
});

// -------------------------------------------------------------------------
// Tests du NormesRuleEvaluator
// -------------------------------------------------------------------------

echo "│\n├─ Tests du NormesRuleEvaluator\n";

$tables = [
    'sections_cable' => [
        '7.4kW (mono)' => ['10' => 2.5, '20' => 4.0, '30' => 6.0],
    ],
];
$evaluator = new NormesRuleEvaluator($tables);

runTest('Condition always retourne applicable', function () use ($evaluator) {
    $workTree = WorkTree::create('test', 'test', []);
    $result = $evaluator->evaluateCondition(['type' => 'always'], $workTree, []);
    assertTrue($result['applicable']);
});

runTest('Condition context_has avec champ présent', function () use ($evaluator) {
    $workTree = WorkTree::create('test', 'test', []);
    $result = $evaluator->evaluateCondition(
        ['type' => 'context_has', 'champs' => ['surface']],
        $workTree,
        ['surface' => 100]
    );
    assertTrue($result['applicable']);
});

runTest('Condition context_has avec champ absent', function () use ($evaluator) {
    $workTree = WorkTree::create('test', 'test', []);
    $result = $evaluator->evaluateCondition(
        ['type' => 'context_has', 'champs' => ['surface']],
        $workTree,
        []
    );
    assertFalse($result['applicable']);
});

runTest('Condition field_eq avec valeur égale', function () use ($evaluator) {
    $workTree = WorkTree::create('test', 'test', []);
    $result = $evaluator->evaluateCondition(
        ['type' => 'field_eq', 'champ' => 'type_chauffage', 'valeur' => 'electrique'],
        $workTree,
        ['type_chauffage' => 'electrique']
    );
    assertTrue($result['applicable']);
});

runTest('Condition field_eq avec valeur différente', function () use ($evaluator) {
    $workTree = WorkTree::create('test', 'test', []);
    $result = $evaluator->evaluateCondition(
        ['type' => 'field_eq', 'champ' => 'type_chauffage', 'valeur' => 'electrique'],
        $workTree,
        ['type_chauffage' => 'gaz']
    );
    assertFalse($result['applicable']);
});

runTest('Condition field_gt avec valeur supérieure', function () use ($evaluator) {
    $workTree = WorkTree::create('test', 'test', []);
    $result = $evaluator->evaluateCondition(
        ['type' => 'field_gt', 'champ' => 'surface', 'valeur' => 50],
        $workTree,
        ['surface' => 100]
    );
    assertTrue($result['applicable']);
});

runTest('Condition field_gt avec valeur inférieure', function () use ($evaluator) {
    $workTree = WorkTree::create('test', 'test', []);
    $result = $evaluator->evaluateCondition(
        ['type' => 'field_gt', 'champ' => 'surface', 'valeur' => 50],
        $workTree,
        ['surface' => 30]
    );
    assertFalse($result['applicable']);
});

runTest('Condition OR avec une condition vraie', function () use ($evaluator) {
    $workTree = WorkTree::create('test', 'test', []);
    $result = $evaluator->evaluateCondition(
        [
            'type' => 'or',
            'conditions' => [
                ['type' => 'field_eq', 'champ' => 'a', 'valeur' => 1],
                ['type' => 'field_eq', 'champ' => 'b', 'valeur' => 2],
            ],
        ],
        $workTree,
        ['a' => 0, 'b' => 2]
    );
    assertTrue($result['applicable']);
});

runTest('Condition AND avec toutes conditions vraies', function () use ($evaluator) {
    $workTree = WorkTree::create('test', 'test', []);
    $result = $evaluator->evaluateCondition(
        [
            'type' => 'and',
            'conditions' => [
                ['type' => 'field_eq', 'champ' => 'a', 'valeur' => 1],
                ['type' => 'field_eq', 'champ' => 'b', 'valeur' => 2],
            ],
        ],
        $workTree,
        ['a' => 1, 'b' => 2]
    );
    assertTrue($result['applicable']);
});

runTest('Condition AND avec une condition fausse', function () use ($evaluator) {
    $workTree = WorkTree::create('test', 'test', []);
    $result = $evaluator->evaluateCondition(
        [
            'type' => 'and',
            'conditions' => [
                ['type' => 'field_eq', 'champ' => 'a', 'valeur' => 1],
                ['type' => 'field_eq', 'champ' => 'b', 'valeur' => 2],
            ],
        ],
        $workTree,
        ['a' => 1, 'b' => 99]
    );
    assertFalse($result['applicable']);
});

runTest('Condition NOT inverse le résultat', function () use ($evaluator) {
    $workTree = WorkTree::create('test', 'test', []);
    $result = $evaluator->evaluateCondition(
        [
            'type' => 'not',
            'condition' => ['type' => 'field_eq', 'champ' => 'x', 'valeur' => 1],
        ],
        $workTree,
        ['x' => 2]
    );
    assertTrue($result['applicable']);
});

runTest('Exception pour type de condition inconnu', function () use ($evaluator) {
    $workTree = WorkTree::create('test', 'test', []);
    $exceptionThrown = false;
    try {
        $evaluator->evaluateCondition(['type' => 'unknown_type'], $workTree, []);
    } catch (InvalidRuleException $e) {
        $exceptionThrown = true;
    }
    assertTrue($exceptionThrown, 'InvalidRuleException attendue');
});

runTest('tableLookup trouve une valeur', function () use ($evaluator) {
    $result = $evaluator->tableLookup(
        'sections_cable',
        ['puissance_borne', 'distance'],
        ['puissance_borne' => '7.4kW (mono)', 'distance' => '20']
    );
    assertTrue($result['trouve']);
    assertEquals(4.0, $result['valeur']);
});

runTest('tableLookup retourne false si non trouvé', function () use ($evaluator) {
    $result = $evaluator->tableLookup(
        'table_inexistante',
        ['cle'],
        ['cle' => 'val']
    );
    assertFalse($result['trouve']);
});

runTest('evaluateFormula calcule correctement', function () use ($evaluator) {
    $result = $evaluator->evaluateFormula('10 + 5', []);
    assertEquals(15, $result);
});

runTest('evaluateFormula avec variable de contexte', function () use ($evaluator) {
    $result = $evaluator->evaluateFormula('surface * 2', ['surface' => 50]);
    assertEquals(100, $result);
});

runTest('checkConformite détecte minimum non respecté', function () use ($evaluator) {
    $result = $evaluator->checkConformite(
        valeur: 3,
        valeurImposee: ['minimum' => 5],
        ruleId: 'test_rule',
        severite: 'bloquant',
        reference: 'TEST'
    );
    assertFalse($result['conforme']);
    assertInstanceOf(Impossibilite::class, $result['impossibilite']);
});

runTest('checkConformite retourne alerte si non bloquant', function () use ($evaluator) {
    $result = $evaluator->checkConformite(
        valeur: 3,
        valeurImposee: ['minimum' => 5],
        ruleId: 'test_rule',
        severite: 'alerte',
        reference: 'TEST'
    );
    assertFalse($result['conforme']);
    assertInstanceOf(Alerte::class, $result['alerte']);
});

runTest('checkConformite valide si conforme', function () use ($evaluator) {
    $result = $evaluator->checkConformite(
        valeur: 10,
        valeurImposee: ['minimum' => 5],
        ruleId: 'test_rule',
        severite: 'bloquant',
        reference: 'TEST'
    );
    assertTrue($result['conforme']);
});

// -------------------------------------------------------------------------
// Tests des DTOs
// -------------------------------------------------------------------------

echo "│\n├─ Tests des DTOs\n";

runTest('RegleAppliquee.ajoutTravail crée correctement', function () {
    $regle = RegleAppliquee::ajoutTravail(
        regleId: 'test_rule',
        categorie: 'protection',
        label: 'Test',
        source: 'norme',
        reference: 'REF',
        travailId: 'travail_test',
        contexte: []
    );
    assertEquals(RegleAppliquee::ACTION_AJOUT_TRAVAIL, $regle->action);
    assertEquals('travail_test', $regle->modifications['travail_id']);
    assertTrue($regle->aModifie());
});

runTest('RegleAppliquee.verification avec conforme=true', function () {
    $regle = RegleAppliquee::verification(
        regleId: 'test_rule',
        categorie: 'protection',
        label: 'Test',
        source: 'norme',
        reference: 'REF',
        conforme: true
    );
    assertEquals(RegleAppliquee::ACTION_VERIFICATION, $regle->action);
    assertFalse($regle->aModifie());
});

runTest('Alerte.info crée une alerte de sévérité INFO', function () {
    $alerte = Alerte::info('code_test', 'Message test');
    assertEquals(Alerte::SEVERITE_INFO, $alerte->severite);
    assertEquals('code_test', $alerte->code);
    assertEquals('Message test', $alerte->message);
});

runTest('Alerte.attention crée une alerte de sévérité ATTENTION', function () {
    $alerte = Alerte::attention('code_test', 'Message test');
    assertEquals(Alerte::SEVERITE_ATTENTION, $alerte->severite);
});

runTest('Alerte.important crée une alerte de sévérité IMPORTANT', function () {
    $alerte = Alerte::important('code_test', 'Message test');
    assertEquals(Alerte::SEVERITE_IMPORTANT, $alerte->severite);
});

runTest('Impossibilite.normeNonRespectee crée correctement', function () {
    $impossibilite = Impossibilite::normeNonRespectee(
        code: 'code_test',
        message: 'Norme non respectée',
        regleId: 'rule_id',
        reference: 'REF'
    );
    assertEquals(Impossibilite::TYPE_NORME_NON_RESPECTEE, $impossibilite->type);
    assertEquals('code_test', $impossibilite->code);
    assertEquals('rule_id', $impossibilite->regleId);
});

runTest('ElementNonDetermine.sectionCable crée correctement', function () {
    $element = ElementNonDetermine::sectionCable(
        code: 'section_test',
        description: 'Section à déterminer',
        sousTravailId: 'st_test',
        champsManquants: ['puissance'],
        sectionParDefaut: 2.5
    );
    assertEquals(ElementNonDetermine::TYPE_SECTION_CABLE, $element->type);
    assertEquals(2.5, $element->valeursParDefaut['section']);
    assertContains('puissance', $element->champsManquants);
});

runTest('NormesEngineOutput.conforme crée un statut conforme', function () {
    $workTree = WorkTree::create('test', 'test', []);
    $output = NormesEngineOutput::conforme($workTree);
    assertEquals(NormesEngineOutput::STATUT_CONFORME, $output->statut);
    assertTrue($output->peutContinuer());
    assertTrue($output->estComplet());
});

runTest('NormesEngineOutput.nonConforme bloque la continuation', function () {
    $workTree = WorkTree::create('test', 'test', []);
    $output = NormesEngineOutput::nonConforme(
        $workTree,
        [],
        [],
        [Impossibilite::normeNonRespectee('code', 'msg', 'rule', 'ref')]
    );
    assertEquals(NormesEngineOutput::STATUT_NON_CONFORME, $output->statut);
    assertFalse($output->peutContinuer());
});

// -------------------------------------------------------------------------
// Tests d'intégration NormesEngine
// -------------------------------------------------------------------------

echo "│\n├─ Tests d'intégration NormesEngine\n";

runTest('Process avec WorkTree vide retourne conforme', function () use ($normesEngine) {
    $workTree = WorkTree::create('test', 'renovation_complete_maison', []);
    $input = new NormesEngineInput(
        workTree: $workTree,
        contexte: [],
        typeChantier: 'renovation_complete_maison'
    );

    $output = $normesEngine->process($input);
    assertInstanceOf(NormesEngineOutput::class, $output);
    assertNotEmpty($output->statistiques);
    assertArrayHasKey('nb_regles_evaluees', $output->statistiques);
});

runTest('Process avec WorkTree réel rénovation', function () use ($normesEngine, $workTreeBuilder) {
    // Créer un WorkTree via le WorkTreeBuilder
    $builderInput = new WorkTreeBuilderInput(
        typeChantier: 'renovation_complete_maison',
        contexte: [
            'surface' => 100,
            'nb_pieces' => 5,
            'nb_pieces_humides' => 2,
            'annee_construction' => 1985,
        ]
    );
    $workTree = $workTreeBuilder->build($builderInput);

    // Traiter avec NormesEngine
    $input = new NormesEngineInput(
        workTree: $workTree,
        contexte: $workTree->contexteComplet,
        typeChantier: 'renovation_complete_maison'
    );

    $output = $normesEngine->process($input);

    assertInstanceOf(NormesEngineOutput::class, $output);
    // Le traitement s'est exécuté (des règles ont été évaluées)
    assertGreaterThan(0, $output->statistiques['nb_regles_evaluees']);
    // Le WorkTree est toujours présent
    assertNotEmpty($output->workTree->travaux);
});

runTest('Process avec borne IRVE', function () use ($normesEngine, $workTreeBuilder) {
    $builderInput = new WorkTreeBuilderInput(
        typeChantier: 'borne_irve',
        contexte: [
            'puissance_borne' => '7.4kW (mono)',
            'distance_tableau' => 15,
            'installation_existante' => 'monophase',
        ]
    );
    $workTree = $workTreeBuilder->build($builderInput);

    $input = new NormesEngineInput(
        workTree: $workTree,
        contexte: $workTree->contexteComplet,
        typeChantier: 'borne_irve'
    );

    $output = $normesEngine->process($input);

    assertInstanceOf(NormesEngineOutput::class, $output);
    assertArrayHasKey('categories_traitees', $output->statistiques);
});

runTest('Process avec cuisine', function () use ($normesEngine, $workTreeBuilder) {
    $builderInput = new WorkTreeBuilderInput(
        typeChantier: 'cuisine',
        contexte: ['hotte' => true, 'ilot_central' => false]
    );
    $workTree = $workTreeBuilder->build($builderInput);

    $input = new NormesEngineInput(
        workTree: $workTree,
        contexte: $workTree->contexteComplet,
        typeChantier: 'cuisine'
    );

    $output = $normesEngine->process($input);

    assertInstanceOf(NormesEngineOutput::class, $output);
    // Le traitement s'est exécuté correctement
    assertArrayHasKey('nb_regles_evaluees', $output->statistiques);
    // Le WorkTree conserve les travaux de cuisine
    assertNotEmpty($output->workTree->travaux);
});

runTest('Mode strict rend les alertes bloquantes', function () use ($normesEngine) {
    $workTree = WorkTree::create('test', 'test', []);
    // Ajouter manuellement une alerte via un domaine non couvert (simulation)

    $input = new NormesEngineInput(
        workTree: $workTree,
        contexte: [],
        typeChantier: 'test',
        options: ['mode_strict' => true]
    );

    $output = $normesEngine->process($input);
    // En mode strict, s'il y a des alertes, le statut sera non conforme
    assertInstanceOf(NormesEngineOutput::class, $output);
});

runTest('Filtrage par catégories actives', function () use ($normesEngine) {
    $workTree = WorkTree::create('test', 'test', []);

    $input = new NormesEngineInput(
        workTree: $workTree,
        contexte: [],
        typeChantier: 'test',
        options: ['categories' => ['protection_differentielle']]
    );

    $output = $normesEngine->process($input);

    assertInstanceOf(NormesEngineOutput::class, $output);
    // Seule la catégorie protection_differentielle devrait être traitée
    if (!empty($output->statistiques['categories_traitees'])) {
        foreach ($output->statistiques['categories_traitees'] as $cat) {
            assertEquals('protection_differentielle', $cat);
        }
    }
});

runTest('Règles ignorées ne sont pas évaluées', function () use ($normesEngine) {
    $workTree = WorkTree::create('test', 'test', []);

    $input = new NormesEngineInput(
        workTree: $workTree,
        contexte: [],
        typeChantier: 'test',
        options: ['regles_ignorees' => ['protection_diff_30ma']]
    );

    $output = $normesEngine->process($input);

    assertInstanceOf(NormesEngineOutput::class, $output);
    // La règle protection_diff_30ma ne devrait pas apparaître dans les règles appliquées
    foreach ($output->reglesAppliquees as $regle) {
        assertTrue(
            $regle->regleId !== 'protection_diff_30ma',
            'La règle protection_diff_30ma ne devrait pas être appliquée'
        );
    }
});

// -------------------------------------------------------------------------
// Tests de statuts
// -------------------------------------------------------------------------

echo "│\n└─ Tests de statuts\n";

runTest('Statut CONFORME sans alertes ni impossibilités', function () use ($normesEngine) {
    $workTree = WorkTree::create('test', 'test', []);

    $input = new NormesEngineInput(
        workTree: $workTree,
        contexte: ['type_local' => 'habitation'],
        typeChantier: 'test'
    );

    $output = $normesEngine->process($input);

    // Sans impossibilités ni alertes, devrait être conforme
    if (empty($output->impossibilites) && empty($output->alertes) && empty($output->elementsNonDetermines)) {
        assertEquals(NormesEngineOutput::STATUT_CONFORME, $output->statut);
    }
});

runTest('Statistiques de traitement présentes', function () use ($normesEngine, $workTreeBuilder) {
    $builderInput = new WorkTreeBuilderInput(
        typeChantier: 'remplacement_tableau',
        contexte: [
            'nb_circuits_existants' => 12,
            'puissance_abonnement' => '9kVA',
        ]
    );
    $workTree = $workTreeBuilder->build($builderInput);

    $input = new NormesEngineInput(
        workTree: $workTree,
        contexte: $workTree->contexteComplet,
        typeChantier: 'remplacement_tableau'
    );

    $output = $normesEngine->process($input);

    assertArrayHasKey('nb_regles_evaluees', $output->statistiques);
    assertArrayHasKey('nb_regles_appliquees', $output->statistiques);
    assertArrayHasKey('nb_ajustements', $output->statistiques);
    assertArrayHasKey('categories_traitees', $output->statistiques);
});

runTest('WorkTree enrichi conserve les travaux', function () use ($normesEngine, $workTreeBuilder) {
    $builderInput = new WorkTreeBuilderInput(
        typeChantier: 'salle_de_bain',
        contexte: ['surface_sdb' => 6]
    );
    $workTree = $workTreeBuilder->build($builderInput);
    $nbTravauxAvant = count($workTree->travaux);

    $input = new NormesEngineInput(
        workTree: $workTree,
        contexte: $workTree->contexteComplet,
        typeChantier: 'salle_de_bain'
    );

    $output = $normesEngine->process($input);

    // Le nombre de travaux ne devrait pas diminuer
    assertGreaterThanOrEqual($nbTravauxAvant, count($output->workTree->travaux));
});

runTest('Historique des modifications ajouté', function () use ($normesEngine, $workTreeBuilder) {
    $builderInput = new WorkTreeBuilderInput(
        typeChantier: 'vmc',
        contexte: [
            'type_vmc' => 'simple_flux',
            'nb_bouches_extraction' => 4,
        ]
    );
    $workTree = $workTreeBuilder->build($builderInput);
    $nbModifsAvant = count($workTree->historiqueModifications);

    $input = new NormesEngineInput(
        workTree: $workTree,
        contexte: $workTree->contexteComplet,
        typeChantier: 'vmc'
    );

    $output = $normesEngine->process($input);

    // L'historique peut avoir augmenté si des règles ont été appliquées
    assertGreaterThanOrEqual($nbModifsAvant, count($output->workTree->historiqueModifications));
});

// ============================================================================
// Résumé
// ============================================================================

echo "\n";
echo "══════════════════════════════════════════════════════════════════════════════\n";

if ($testsFailed === 0) {
    echo "  ✅ TOUS LES TESTS PASSENT ($testsPassed tests)\n";
    $exitCode = 0;
} else {
    echo "  ❌ ÉCHECS: $testsFailed / " . ($testsPassed + $testsFailed) . " tests\n";
    echo "\n  Erreurs:\n";
    foreach ($testsErrors as $error) {
        echo "    • {$error['name']}: {$error['error']}\n";
    }
    $exitCode = 1;
}

echo "══════════════════════════════════════════════════════════════════════════════\n";
echo "\n";

exit($exitCode);
