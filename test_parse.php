<?php
/**
 * Script de test pour le parsing de lignes vocales
 * Usage: php test_parse.php "votre texte à tester"
 */

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

use App\Services\OpenAIClient;

// Textes de test
$testCases = [
    "un digicode radio",
    "un digicode Legrand",
    "un digicode radio pas cher",
    "2 interrupteurs Schneider Odace référence S520201",
    "15 mètres de câble 3G2.5",
    "3 heures de main d'oeuvre",
    "un visiophone Aiphone",
    "5 prises premier prix",
];

// Si un argument est passé, l'utiliser comme test
if (isset($argv[1])) {
    $testCases = [$argv[1]];
}

$client = new OpenAIClient();

echo "\n=== TEST PARSING LIGNES VOCALES ===\n\n";

foreach ($testCases as $text) {
    echo "📝 Input: \"$text\"\n";
    echo str_repeat('-', 60) . "\n";

    try {
        $result = $client->parseLineFromText($text);

        echo "✅ Résultat:\n";
        echo "   Désignation: {$result['designation']}\n";
        echo "   Marque: " . ($result['marque'] ?? 'non détectée') . "\n";
        echo "   Référence: " . ($result['reference'] ?? 'non détectée') . "\n";
        echo "   Gamme: {$result['gamme']}\n";
        echo "   Catégorie: {$result['categorie']}\n";
        echo "   Quantité: {$result['quantite']} {$result['unite']}\n";
        echo "   Prix unitaire: {$result['prix_unitaire_ht']} €\n";

    } catch (Exception $e) {
        echo "❌ Erreur: " . $e->getMessage() . "\n";
    }

    echo "\n";
}
