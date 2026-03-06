<?php

/**
 * Chiffreo - Smoke Tests
 * Tests rapides pour vérifier la configuration
 *
 * Usage: php tests/smoke_test.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

echo "\n========================================\n";
echo "Chiffreo - Smoke Tests\n";
echo "========================================\n\n";

$tests = [];
$passed = 0;
$failed = 0;

// === Test 1: Fichier .env ===
$tests[] = test('Fichier .env existe', function () {
    $envFile = __DIR__ . '/../.env';
    if (!file_exists($envFile)) {
        throw new Exception("Fichier .env manquant. Copier .env.example vers .env");
    }
    return true;
});

// === Test 2: Variables d'environnement ===
$tests[] = test('Variables d\'environnement chargées', function () {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();

    $required = ['DB_HOST', 'DB_NAME', 'DB_USER', 'OPENAI_API_KEY'];
    foreach ($required as $var) {
        if (empty($_ENV[$var])) {
            throw new Exception("Variable {$var} manquante dans .env");
        }
    }
    return true;
});

// === Test 3: Clé OpenAI configurée ===
$tests[] = test('Clé OpenAI configurée', function () {
    if ($_ENV['OPENAI_API_KEY'] === 'sk-votre-cle-api-ici') {
        throw new Exception("Remplacer OPENAI_API_KEY par votre vraie clé API");
    }
    return true;
});

// === Test 4: Connexion MySQL ===
$tests[] = test('Connexion MySQL', function () {
    try {
        $pdo = \App\Database\Connection::getInstance();
        $pdo->query('SELECT 1');
        return true;
    } catch (Exception $e) {
        throw new Exception("Erreur MySQL: " . $e->getMessage());
    }
});

// === Test 5: Tables créées ===
$tests[] = test('Tables MySQL créées', function () {
    $pdo = \App\Database\Connection::getInstance();
    $tables = ['users', 'quotes', 'quote_items', 'attachments', 'logs', 'rate_limits'];

    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() === 0) {
            throw new Exception("Table '{$table}' manquante. Exécuter: mysql < database/migrations/001_create_tables.sql");
        }
    }
    return true;
});

// === Test 6: Grille de prix ===
$tests[] = test('Grille de prix chargée', function () {
    $prices = require __DIR__ . '/../config/prices.php';
    if (!is_array($prices) || count($prices) < 10) {
        throw new Exception("Grille de prix vide ou invalide");
    }
    if (!isset($prices['MO_H'])) {
        throw new Exception("Code MO_H manquant dans la grille");
    }
    return true;
});

// === Test 7: JSON Schema ===
$tests[] = test('JSON Schema configuré', function () {
    $schema = require __DIR__ . '/../config/quote_schema.php';
    if (!isset($schema['json_schema']) || !isset($schema['system_prompt'])) {
        throw new Exception("Configuration quote_schema.php incomplète");
    }
    return true;
});

// === Test 8: Dossiers de stockage ===
$tests[] = test('Dossiers de stockage accessibles', function () {
    $dirs = ['storage/uploads', 'storage/audio', 'storage/logs'];
    foreach ($dirs as $dir) {
        $path = __DIR__ . '/../' . $dir;
        if (!is_dir($path)) {
            throw new Exception("Dossier {$dir} manquant");
        }
        if (!is_writable($path)) {
            throw new Exception("Dossier {$dir} non accessible en écriture");
        }
    }
    return true;
});

// === Test 9: Service QuoteCalculator ===
$tests[] = test('Service QuoteCalculator', function () {
    $calculator = new \App\Services\QuoteCalculator();

    $testQuote = [
        'lignes' => [
            [
                'designation' => 'Test',
                'categorie' => 'materiel',
                'unite' => 'h',
                'quantite' => 2,
                'prix_ref_code' => 'MO_H',
                'prix_unitaire_ht_suggere' => null,
                'commentaire' => null
            ]
        ],
        'taux_tva' => 20
    ];

    $result = $calculator->calculate($testQuote);

    if (!isset($result['totaux']['total_ttc'])) {
        throw new Exception("Calcul des totaux échoué");
    }

    // 2h * 45€ = 90€ HT, TVA 18€, TTC 108€
    if ($result['totaux']['total_ht'] !== 90.0) {
        throw new Exception("Calcul HT incorrect: attendu 90, obtenu " . $result['totaux']['total_ht']);
    }

    return true;
});

// === Test 10: Classe PDF ===
$tests[] = test('Service QuotePdfRenderer', function () {
    $renderer = new \App\Services\QuotePdfRenderer();

    $testQuote = [
        'chantier' => ['titre' => 'Test', 'perimetre' => 'Test', 'hypotheses' => []],
        'taches' => [],
        'lignes' => [],
        'totaux' => ['total_ht' => 0, 'montant_tva' => 0, 'total_ttc' => 0, 'taux_tva' => 20],
        'questions_a_poser' => [],
        'exclusions' => []
    ];

    $pdf = $renderer->render($testQuote, 'TEST-0001');

    if (strlen($pdf) < 1000) {
        throw new Exception("PDF généré semble vide ou trop petit");
    }

    // Vérifier signature PDF
    if (substr($pdf, 0, 4) !== '%PDF') {
        throw new Exception("Le fichier généré n'est pas un PDF valide");
    }

    return true;
});

// === Résumé ===
echo "\n========================================\n";
echo "Résumé\n";
echo "========================================\n";

foreach ($tests as $result) {
    if ($result['success']) {
        $passed++;
        echo "✅ {$result['name']}\n";
    } else {
        $failed++;
        echo "❌ {$result['name']}\n";
        echo "   → {$result['error']}\n";
    }
}

echo "\n----------------------------------------\n";
echo "Passés: {$passed} | Échoués: {$failed}\n";
echo "----------------------------------------\n\n";

if ($failed > 0) {
    echo "⚠️  Certains tests ont échoué. Vérifiez la configuration.\n\n";
    exit(1);
} else {
    echo "✅ Tous les tests sont passés. L'application est prête !\n\n";
    echo "Lancer le serveur :\n";
    echo "  php -S localhost:8000 -t public\n\n";
    exit(0);
}

// === Fonction helper ===
function test(string $name, callable $fn): array
{
    try {
        $fn();
        return ['name' => $name, 'success' => true, 'error' => null];
    } catch (Exception $e) {
        return ['name' => $name, 'success' => false, 'error' => $e->getMessage()];
    }
}
