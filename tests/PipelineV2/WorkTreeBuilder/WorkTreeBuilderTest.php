<?php

/**
 * Tests fonctionnels du WorkTreeBuilder sur les 6 macro-chantiers.
 *
 * Script standalone - aucune dépendance PHPUnit requise.
 *
 * Exécution : php WorkTreeBuilderTest.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use App\PipelineV2\WorkTreeBuilder\WorkTreeBuilder;
use App\PipelineV2\WorkTreeBuilder\DTO\WorkTreeBuilderInput;
use App\PipelineV2\WorkTreeBuilder\DTO\WorkTree;
use App\PipelineV2\WorkTreeBuilder\Exception\UnknownChantierTypeException;

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

function assertStringContainsString(string $needle, string $haystack, string $message = ''): void
{
    if (strpos($haystack, $needle) === false) {
        $msg = $message ?: sprintf('Expected string to contain "%s"', $needle);
        throw new AssertionError($msg);
    }
}

function assertStringStartsWith(string $prefix, string $string, string $message = ''): void
{
    if (!str_starts_with($string, $prefix)) {
        $msg = $message ?: sprintf('Expected string to start with "%s"', $prefix);
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

function assertWorkTreeHasTravail(WorkTree $workTree, string $travailId): void
{
    assertTrue(
        $workTree->hasTravail($travailId),
        "Le travail '$travailId' devrait être présent. Travaux: " .
        implode(', ', array_map(fn($t) => $t->id, $workTree->travaux))
    );
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
        $testsErrors[] = ['name' => $name, 'error' => $e->getMessage()];
        echo "  ❌ $name\n";
        echo "     └─ " . $e->getMessage() . "\n";
    }
}

// ============================================================================
// Configuration
// ============================================================================

$configDir = __DIR__ . '/../../../config/pipeline_v2';
$builder = new WorkTreeBuilder(
    $configDir . '/chantier_types.php',
    $configDir . '/travaux_definitions.php'
);

// ============================================================================
// Tests
// ============================================================================

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                    TESTS DU WORKTREEBUILDER                                  ║\n";
echo "╚══════════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// -------------------------------------------------------------------------
// Tests généraux
// -------------------------------------------------------------------------

echo "┌─ Tests généraux\n";

runTest('Supporte les 6 macro-chantiers', function () use ($builder) {
    $expectedTypes = [
        'renovation_complete_maison',
        'remplacement_tableau',
        'borne_irve',
        'cuisine',
        'salle_de_bain',
        'vmc',
    ];

    foreach ($expectedTypes as $type) {
        assertTrue(
            $builder->supportsChantierType($type),
            "Le type '$type' devrait être supporté"
        );
    }
});

runTest('Exception pour type inconnu', function () use ($builder) {
    $exceptionThrown = false;
    try {
        $input = new WorkTreeBuilderInput('type_inexistant', []);
        $builder->build($input);
    } catch (UnknownChantierTypeException $e) {
        $exceptionThrown = true;
    }
    assertTrue($exceptionThrown, 'UnknownChantierTypeException attendue');
});

// -------------------------------------------------------------------------
// Rénovation complète maison
// -------------------------------------------------------------------------

echo "│\n├─ Rénovation complète maison\n";

runTest('Travaux de base activés', function () use ($builder) {
    $input = new WorkTreeBuilderInput(
        typeChantier: 'renovation_complete_maison',
        contexte: [
            'surface' => 100,
            'nb_pieces' => 6,
            'nb_pieces_humides' => 2,
            'annee_construction' => 1985,
        ]
    );

    $workTree = $builder->build($input);

    assertWorkTreeHasTravail($workTree, 'tableau_electrique');
    assertWorkTreeHasTravail($workTree, 'mise_a_la_terre');
    assertWorkTreeHasTravail($workTree, 'distribution_prises');
    assertWorkTreeHasTravail($workTree, 'distribution_eclairage');
});

runTest('VMC activée si nb_pieces_humides >= 2', function () use ($builder) {
    $input = new WorkTreeBuilderInput(
        typeChantier: 'renovation_complete_maison',
        contexte: [
            'surface' => 85,
            'nb_pieces' => 5,
            'nb_pieces_humides' => 2,
            'annee_construction' => 1990,
        ]
    );

    $workTree = $builder->build($input);
    assertWorkTreeHasTravail($workTree, 'ventilation_mecanique');
});

runTest('VMC activée si surface > 30', function () use ($builder) {
    $input = new WorkTreeBuilderInput(
        typeChantier: 'renovation_complete_maison',
        contexte: [
            'surface' => 50,
            'nb_pieces' => 3,
            'nb_pieces_humides' => 1,
            'annee_construction' => 2000,
        ]
    );

    $workTree = $builder->build($input);
    assertWorkTreeHasTravail($workTree, 'ventilation_mecanique');
});

runTest('Chauffage électrique activé si type_chauffage=electrique', function () use ($builder) {
    $input = new WorkTreeBuilderInput(
        typeChantier: 'renovation_complete_maison',
        contexte: [
            'surface' => 100,
            'nb_pieces' => 6,
            'nb_pieces_humides' => 2,
            'annee_construction' => 1985,
            'type_chauffage' => 'electrique',
        ]
    );

    $workTree = $builder->build($input);
    assertWorkTreeHasTravail($workTree, 'chauffage_electrique');
});

runTest('Questions manquantes identifiées', function () use ($builder) {
    $input = new WorkTreeBuilderInput(
        typeChantier: 'renovation_complete_maison',
        contexte: []
    );

    $workTree = $builder->build($input);

    $questionsIds = array_map(fn($q) => $q->id, $workTree->questionsManquantes);
    assertContains('surface', $questionsIds);
    assertContains('nb_pieces', $questionsIds);
    assertContains('annee_construction', $questionsIds);
    assertFalse($workTree->isContexteComplet());
});

runTest('Valeurs par défaut appliquées', function () use ($builder) {
    $input = new WorkTreeBuilderInput(
        typeChantier: 'renovation_complete_maison',
        contexte: [
            'surface' => 80,
            'nb_pieces' => 4,
            'annee_construction' => 1990,
        ]
    );

    $workTree = $builder->build($input);

    assertArrayHasKey('nb_etages', $workTree->contexteComplet);
    assertEquals(1, $workTree->contexteComplet['nb_etages']);
    assertArrayHasKey('puissance_abonnement', $workTree->contexteComplet);
    assertEquals('9kVA', $workTree->contexteComplet['puissance_abonnement']);
});

// -------------------------------------------------------------------------
// Remplacement tableau
// -------------------------------------------------------------------------

echo "│\n├─ Remplacement tableau\n";

runTest('Travaux de base activés', function () use ($builder) {
    $input = new WorkTreeBuilderInput(
        typeChantier: 'remplacement_tableau',
        contexte: [
            'nb_circuits_existants' => 12,
            'puissance_abonnement' => '9kVA',
        ]
    );

    $workTree = $builder->build($input);

    assertWorkTreeHasTravail($workTree, 'tableau_electrique');
    assertWorkTreeHasTravail($workTree, 'verification_terre');
});

runTest('Contacteur HP/HC activé', function () use ($builder) {
    $input = new WorkTreeBuilderInput(
        typeChantier: 'remplacement_tableau',
        contexte: [
            'nb_circuits_existants' => 12,
            'puissance_abonnement' => '9kVA',
            'chauffe_eau_electrique' => true,
            'abonnement_hphc' => true,
        ]
    );

    $workTree = $builder->build($input);
    assertWorkTreeHasTravail($workTree, 'gestion_chauffe_eau');
});

// -------------------------------------------------------------------------
// Borne IRVE
// -------------------------------------------------------------------------

echo "│\n├─ Borne IRVE\n";

runTest('Travaux de base activés', function () use ($builder) {
    $input = new WorkTreeBuilderInput(
        typeChantier: 'borne_irve',
        contexte: [
            'puissance_borne' => '7.4kW (mono)',
            'distance_tableau' => 10,
            'installation_existante' => 'monophase',
        ]
    );

    $workTree = $builder->build($input);

    assertWorkTreeHasTravail($workTree, 'installation_borne');
    assertWorkTreeHasTravail($workTree, 'alimentation_borne');
    assertWorkTreeHasTravail($workTree, 'protection_borne');
});

runTest('Coffret secondaire si distance > 25m', function () use ($builder) {
    $input = new WorkTreeBuilderInput(
        typeChantier: 'borne_irve',
        contexte: [
            'puissance_borne' => '7.4kW (mono)',
            'distance_tableau' => 30,
            'installation_existante' => 'monophase',
        ]
    );

    $workTree = $builder->build($input);
    assertWorkTreeHasTravail($workTree, 'coffret_secondaire');
});

runTest('Cheminement extérieur si passage_exterieur', function () use ($builder) {
    $input = new WorkTreeBuilderInput(
        typeChantier: 'borne_irve',
        contexte: [
            'puissance_borne' => '7.4kW (mono)',
            'distance_tableau' => 15,
            'installation_existante' => 'monophase',
            'passage_exterieur' => true,
        ]
    );

    $workTree = $builder->build($input);
    assertWorkTreeHasTravail($workTree, 'cheminement_exterieur');
});

// -------------------------------------------------------------------------
// Cuisine
// -------------------------------------------------------------------------

echo "│\n├─ Cuisine\n";

runTest('Travaux de base activés', function () use ($builder) {
    $input = new WorkTreeBuilderInput(
        typeChantier: 'cuisine',
        contexte: []
    );

    $workTree = $builder->build($input);

    assertWorkTreeHasTravail($workTree, 'circuits_specialises');
    assertWorkTreeHasTravail($workTree, 'distribution_prises_cuisine');
    assertWorkTreeHasTravail($workTree, 'eclairage_cuisine');
});

runTest('Hotte activée si hotte=true', function () use ($builder) {
    $input = new WorkTreeBuilderInput(
        typeChantier: 'cuisine',
        contexte: ['hotte' => true]
    );

    $workTree = $builder->build($input);
    assertWorkTreeHasTravail($workTree, 'alimentation_hotte');
});

runTest('Îlot activé si ilot_central=true', function () use ($builder) {
    $input = new WorkTreeBuilderInput(
        typeChantier: 'cuisine',
        contexte: ['ilot_central' => true]
    );

    $workTree = $builder->build($input);
    assertWorkTreeHasTravail($workTree, 'equipement_ilot');
});

// -------------------------------------------------------------------------
// Salle de bain
// -------------------------------------------------------------------------

echo "│\n├─ Salle de bain\n";

runTest('Travaux activés', function () use ($builder) {
    $input = new WorkTreeBuilderInput(
        typeChantier: 'salle_de_bain',
        contexte: ['surface_sdb' => 6]
    );

    $workTree = $builder->build($input);
    assertNotEmpty($workTree->travaux);
});

// -------------------------------------------------------------------------
// VMC
// -------------------------------------------------------------------------

echo "│\n├─ VMC\n";

runTest('Travaux activés', function () use ($builder) {
    $input = new WorkTreeBuilderInput(
        typeChantier: 'vmc',
        contexte: [
            'type_vmc' => 'simple_flux',
            'nb_bouches_extraction' => 4,
        ]
    );

    $workTree = $builder->build($input);
    assertNotEmpty($workTree->travaux);
});

// -------------------------------------------------------------------------
// Instance ID et Scope
// -------------------------------------------------------------------------

echo "│\n├─ Instance ID et Scope\n";

runTest('Instance ID générés pour travaux et sous-travaux', function () use ($builder) {
    $input = new WorkTreeBuilderInput(
        typeChantier: 'renovation_complete_maison',
        contexte: [
            'surface' => 100,
            'nb_pieces' => 5,
            'annee_construction' => 1990,
        ]
    );

    $workTree = $builder->build($input);

    foreach ($workTree->travaux as $travail) {
        assertNotEmpty($travail->instanceId);
        assertStringContainsString($travail->id, $travail->instanceId);

        foreach ($travail->sousTravaux as $sousTravail) {
            assertNotEmpty($sousTravail->instanceId);
            assertStringContainsString($sousTravail->id, $sousTravail->instanceId);
        }
    }
});

runTest('Scope chantier par défaut', function () use ($builder) {
    $input = new WorkTreeBuilderInput(
        typeChantier: 'borne_irve',
        contexte: [
            'puissance_borne' => '7.4kW (mono)',
            'distance_tableau' => 10,
            'installation_existante' => 'monophase',
        ]
    );

    $workTree = $builder->build($input);

    foreach ($workTree->travaux as $travail) {
        assertEquals('chantier', $travail->scope->type);

        foreach ($travail->sousTravaux as $sousTravail) {
            assertEquals('chantier', $sousTravail->scope->type);
        }
    }
});

// -------------------------------------------------------------------------
// Historique
// -------------------------------------------------------------------------

echo "│\n└─ Historique\n";

runTest('Historique des modifications créé', function () use ($builder) {
    $input = new WorkTreeBuilderInput(
        typeChantier: 'cuisine',
        contexte: []
    );

    $workTree = $builder->build($input);

    assertGreaterThanOrEqual(count($workTree->travaux), count($workTree->historiqueModifications));

    foreach ($workTree->historiqueModifications as $modification) {
        assertEquals('worktree_builder', $modification->etape);
        assertEquals('creation', $modification->action);
        assertStringStartsWith('travail:', $modification->cible);
    }
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
