<?php

namespace App\Services;

/**
 * Service de détection des normes et équipements obligatoires
 *
 * Analyse la description des travaux pour identifier :
 * - Les normes applicables (NF C 15-100, etc.)
 * - Les équipements obligatoires à inclure
 * - Les certifications requises
 * - Les points de contrôle
 */
class NormsService
{
    private array $rules;

    public function __construct()
    {
        $this->rules = require __DIR__ . '/../../config/norms_rules.php';
    }

    /**
     * Analyse une description de travaux et retourne les règles applicables
     *
     * @param string $description Description des travaux (texte libre)
     * @return array Règles applicables avec normes, équipements, certifications
     */
    public function analyzeWorkDescription(string $description): array
    {
        $description = mb_strtolower($description);
        $matchedRules = [];

        foreach ($this->rules as $ruleKey => $rule) {
            $score = $this->calculateMatchScore($description, $rule['mots_cles']);

            if ($score > 0) {
                $matchedRules[] = [
                    'key' => $ruleKey,
                    'score' => $score,
                    'rule' => $rule,
                ];
            }
        }

        // Trier par score décroissant
        usort($matchedRules, fn($a, $b) => $b['score'] <=> $a['score']);

        return $this->formatResults($matchedRules);
    }

    /**
     * Calcule un score de correspondance entre la description et les mots-clés
     */
    private function calculateMatchScore(string $description, array $keywords): int
    {
        $score = 0;

        foreach ($keywords as $keyword) {
            $keyword = mb_strtolower($keyword);

            // Correspondance exacte : +10 points
            if (str_contains($description, $keyword)) {
                $score += 10;

                // Bonus si le mot-clé est long (plus spécifique)
                if (strlen($keyword) > 8) {
                    $score += 5;
                }
            }
        }

        return $score;
    }

    /**
     * Formate les résultats pour utilisation dans le prompt ou l'interface
     */
    private function formatResults(array $matchedRules): array
    {
        if (empty($matchedRules)) {
            return [
                'detected_types' => [],
                'normes' => [],
                'equipements_obligatoires' => [],
                'equipements_recommandes' => [],
                'certifications' => [],
                'points_controle' => [],
                'tva_applicable' => null,
                'aides_disponibles' => [],
            ];
        }

        $result = [
            'detected_types' => [],
            'normes' => [],
            'equipements_obligatoires' => [],
            'equipements_recommandes' => [],
            'certifications' => [],
            'points_controle' => [],
            'tva_applicable' => null,
            'aides_disponibles' => [],
        ];

        foreach ($matchedRules as $match) {
            $rule = $match['rule'];
            $key = $match['key'];

            // Types de travaux détectés
            $result['detected_types'][] = [
                'key' => $key,
                'nom' => $rule['nom'],
                'score' => $match['score'],
            ];

            // Normes (éviter doublons)
            foreach ($rule['normes'] ?? [] as $ref => $desc) {
                $result['normes'][$ref] = $desc;
            }

            // Équipements obligatoires
            foreach ($rule['equipements_obligatoires'] ?? [] as $equip) {
                $result['equipements_obligatoires'][] = $equip;
            }

            // Équipements recommandés
            foreach ($rule['equipements_recommandes'] ?? [] as $equip) {
                $result['equipements_recommandes'][] = $equip;
            }

            // Certifications
            foreach ($rule['certifications'] ?? [] as $cert) {
                $result['certifications'][] = $cert;
            }

            // Points de contrôle
            foreach ($rule['points_controle'] ?? [] as $point) {
                if (!in_array($point, $result['points_controle'])) {
                    $result['points_controle'][] = $point;
                }
            }

            // TVA applicable (prendre le taux le plus avantageux)
            if (isset($rule['tva_applicable'])) {
                if (
                    $result['tva_applicable'] === null ||
                    $rule['tva_applicable']['taux'] < $result['tva_applicable']['taux']
                ) {
                    $result['tva_applicable'] = $rule['tva_applicable'];
                }
            }

            // Aides disponibles
            if (isset($rule['aides_disponibles'])) {
                foreach ($rule['aides_disponibles'] as $aide => $desc) {
                    $result['aides_disponibles'][$aide] = $desc;
                }
            }
        }

        // Dédoublonner équipements obligatoires par code
        $result['equipements_obligatoires'] = $this->deduplicateEquipments(
            $result['equipements_obligatoires']
        );

        // Dédoublonner équipements recommandés par code
        $result['equipements_recommandes'] = $this->deduplicateEquipments(
            $result['equipements_recommandes']
        );

        return $result;
    }

    /**
     * Supprime les équipements en double (même code)
     */
    private function deduplicateEquipments(array $equipments): array
    {
        $seen = [];
        $result = [];

        foreach ($equipments as $equip) {
            if (!isset($seen[$equip['code']])) {
                $seen[$equip['code']] = true;
                $result[] = $equip;
            }
        }

        return $result;
    }

    /**
     * Génère un texte formaté pour injection dans le prompt IA
     *
     * @param string $description Description des travaux
     * @return string Texte formaté avec normes et équipements
     */
    public function generatePromptContext(string $description): string
    {
        $analysis = $this->analyzeWorkDescription($description);

        if (empty($analysis['detected_types'])) {
            return '';
        }

        $lines = [];
        $lines[] = "\n## NORMES ET ÉQUIPEMENTS DÉTECTÉS AUTOMATIQUEMENT\n";

        // Types de travaux
        $types = array_column($analysis['detected_types'], 'nom');
        $lines[] = "**Types de travaux identifiés :** " . implode(', ', $types);

        // Normes
        if (!empty($analysis['normes'])) {
            $lines[] = "\n### Normes applicables";
            foreach ($analysis['normes'] as $ref => $desc) {
                $lines[] = "- **{$ref}** : {$desc}";
            }
        }

        // Équipements obligatoires
        if (!empty($analysis['equipements_obligatoires'])) {
            $lines[] = "\n### Équipements OBLIGATOIRES à inclure dans le devis";
            foreach ($analysis['equipements_obligatoires'] as $equip) {
                $lines[] = "- **{$equip['designation']}** (code: {$equip['code']}) - {$equip['raison']}";
            }
        }

        // Équipements recommandés
        if (!empty($analysis['equipements_recommandes'])) {
            $lines[] = "\n### Équipements recommandés";
            foreach ($analysis['equipements_recommandes'] as $equip) {
                $lines[] = "- {$equip['designation']} (code: {$equip['code']}) - {$equip['raison']}";
            }
        }

        // Certifications
        if (!empty($analysis['certifications'])) {
            $lines[] = "\n### Certifications requises";
            foreach ($analysis['certifications'] as $cert) {
                $lines[] = "- **{$cert['nom']}** : {$cert['description']} (obligatoire si : {$cert['obligatoire_si']})";
            }
        }

        // Points de contrôle
        if (!empty($analysis['points_controle'])) {
            $lines[] = "\n### Points de contrôle importants";
            foreach ($analysis['points_controle'] as $point) {
                $lines[] = "- {$point}";
            }
        }

        // TVA
        if ($analysis['tva_applicable']) {
            $lines[] = "\n### TVA applicable";
            $lines[] = "- Taux : {$analysis['tva_applicable']['taux']}%";
            $lines[] = "- Condition : {$analysis['tva_applicable']['condition']}";
        }

        // Aides
        if (!empty($analysis['aides_disponibles'])) {
            $lines[] = "\n### Aides financières possibles";
            foreach ($analysis['aides_disponibles'] as $aide => $desc) {
                $lines[] = "- **{$aide}** : {$desc}";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Retourne les règles pour un type de travaux spécifique
     *
     * @param string $type Clé du type de travaux (ex: 'irve', 'vmc', 'piscine')
     * @return array|null Règles ou null si type inconnu
     */
    public function getRulesByType(string $type): ?array
    {
        return $this->rules[$type] ?? null;
    }

    /**
     * Liste tous les types de travaux disponibles
     *
     * @return array Liste des types avec nom et mots-clés
     */
    public function listAvailableTypes(): array
    {
        $types = [];

        foreach ($this->rules as $key => $rule) {
            $types[] = [
                'key' => $key,
                'nom' => $rule['nom'],
                'mots_cles' => $rule['mots_cles'],
            ];
        }

        return $types;
    }
}
