<?php

declare(strict_types=1);

namespace App\PipelineV2\NormesEngine;

use App\PipelineV2\NormesEngine\DTO\Alerte;
use App\PipelineV2\NormesEngine\DTO\ElementNonDetermine;
use App\PipelineV2\NormesEngine\DTO\Impossibilite;
use App\PipelineV2\NormesEngine\Exception\InvalidRuleException;
use App\PipelineV2\NormesEngine\Exception\RuleEvaluationException;
use App\PipelineV2\WorkTreeBuilder\DTO\WorkTree;

/**
 * Évalue les règles normatives contre le WorkTree et le contexte.
 *
 * Responsabilités :
 * - Évaluer les conditions des règles
 * - Effectuer les calculs (table lookup, formules)
 * - Détecter les non-conformités
 * - Identifier les éléments non déterminés
 */
final class NormesRuleEvaluator
{
    /**
     * @param array $tables Tables de référence (sections, débits, etc.)
     */
    public function __construct(
        private readonly array $tables = [],
    ) {}

    // =========================================================================
    // Évaluation des conditions
    // =========================================================================

    /**
     * Évalue si une règle doit s'appliquer.
     *
     * @return array{applicable: bool, raison: string|null, contexte: array}
     */
    public function evaluateCondition(array $condition, WorkTree $workTree, array $contexte): array
    {
        if (empty($condition)) {
            return ['applicable' => true, 'raison' => null, 'contexte' => []];
        }

        $type = $condition['type'] ?? null;

        if ($type === null) {
            throw new InvalidRuleException('unknown', "Type de condition manquant", $condition);
        }

        try {
            $result = match ($type) {
                'always' => $this->evaluateAlways(),
                'context_has' => $this->evaluateContextHas($condition, $contexte),
                'field_eq' => $this->evaluateFieldEq($condition, $contexte),
                'field_neq' => $this->evaluateFieldNeq($condition, $contexte),
                'field_gt' => $this->evaluateFieldGt($condition, $contexte),
                'field_gte' => $this->evaluateFieldGte($condition, $contexte),
                'field_lt' => $this->evaluateFieldLt($condition, $contexte),
                'field_lte' => $this->evaluateFieldLte($condition, $contexte),
                'has_travail' => $this->evaluateHasTravail($condition, $workTree),
                'has_circuit' => $this->evaluateHasCircuit($condition, $workTree, $contexte),
                'circuit_type' => $this->evaluateCircuitType($condition, $workTree, $contexte),
                'circuit_in_list' => $this->evaluateCircuitInList($condition, $workTree, $contexte),
                'has_point_lumineux' => $this->evaluateHasPointLumineux($workTree),
                'all_circuits_protected' => $this->evaluateAllCircuitsProtected($condition, $workTree),
                'comparison' => $this->evaluateComparison($condition, $contexte),
                'or' => $this->evaluateOr($condition, $workTree, $contexte),
                'and' => $this->evaluateAnd($condition, $workTree, $contexte),
                'not' => $this->evaluateNot($condition, $workTree, $contexte),
                default => throw new InvalidRuleException('unknown', "Type de condition non supporté: {$type}", $condition),
            };

            return $result;
        } catch (InvalidRuleException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new RuleEvaluationException(
                ruleId: 'condition',
                reason: "Erreur d'évaluation: {$e->getMessage()}",
                contexte: ['condition' => $condition],
                previous: $e,
            );
        }
    }

    // =========================================================================
    // Évaluateurs de conditions
    // =========================================================================

    private function evaluateAlways(): array
    {
        return ['applicable' => true, 'raison' => null, 'contexte' => []];
    }

    private function evaluateContextHas(array $condition, array $contexte): array
    {
        $champs = $condition['champs'] ?? (isset($condition['champ']) ? [$condition['champ']] : []);

        foreach ($champs as $champ) {
            if (!isset($contexte[$champ]) || $contexte[$champ] === null || $contexte[$champ] === false) {
                return [
                    'applicable' => false,
                    'raison' => "Champ '{$champ}' absent ou faux",
                    'contexte' => ['champ' => $champ],
                ];
            }
        }

        return ['applicable' => true, 'raison' => null, 'contexte' => ['champs' => $champs]];
    }

    private function evaluateFieldEq(array $condition, array $contexte): array
    {
        $champ = $condition['champ'] ?? null;
        $valeur = $condition['valeur'] ?? null;

        if ($champ === null) {
            throw new InvalidRuleException('field_eq', "Champ requis pour field_eq", $condition);
        }

        $champValue = $contexte[$champ] ?? null;

        return [
            'applicable' => $champValue === $valeur,
            'raison' => $champValue !== $valeur ? "'{$champ}' = '{$champValue}' (attendu: '{$valeur}')" : null,
            'contexte' => ['champ' => $champ, 'valeur' => $champValue, 'attendu' => $valeur],
        ];
    }

    private function evaluateFieldNeq(array $condition, array $contexte): array
    {
        $result = $this->evaluateFieldEq($condition, $contexte);
        return [
            'applicable' => !$result['applicable'],
            'raison' => $result['applicable'] ? "'{$condition['champ']}' ne devrait pas être '{$condition['valeur']}'" : null,
            'contexte' => $result['contexte'],
        ];
    }

    private function evaluateFieldGt(array $condition, array $contexte): array
    {
        $champ = $condition['champ'] ?? null;
        $valeur = $condition['valeur'] ?? null;

        if ($champ === null) {
            throw new InvalidRuleException('field_gt', "Champ requis pour field_gt", $condition);
        }

        $champValue = $contexte[$champ] ?? null;

        if (!is_numeric($champValue)) {
            return [
                'applicable' => false,
                'raison' => "Champ '{$champ}' non numérique ou absent",
                'contexte' => ['champ' => $champ, 'valeur' => $champValue],
            ];
        }

        return [
            'applicable' => $champValue > $valeur,
            'raison' => $champValue <= $valeur ? "'{$champ}' = {$champValue} (doit être > {$valeur})" : null,
            'contexte' => ['champ' => $champ, 'valeur' => $champValue, 'seuil' => $valeur],
        ];
    }

    private function evaluateFieldGte(array $condition, array $contexte): array
    {
        $champ = $condition['champ'] ?? null;
        $valeur = $condition['valeur'] ?? null;
        $champValue = $contexte[$champ] ?? null;

        if (!is_numeric($champValue)) {
            return ['applicable' => false, 'raison' => "Champ non numérique", 'contexte' => []];
        }

        return [
            'applicable' => $champValue >= $valeur,
            'raison' => $champValue < $valeur ? "'{$champ}' = {$champValue} (doit être >= {$valeur})" : null,
            'contexte' => ['champ' => $champ, 'valeur' => $champValue, 'seuil' => $valeur],
        ];
    }

    private function evaluateFieldLt(array $condition, array $contexte): array
    {
        $champ = $condition['champ'] ?? null;
        $valeur = $condition['valeur'] ?? null;
        $champValue = $contexte[$champ] ?? null;

        if (!is_numeric($champValue)) {
            return ['applicable' => false, 'raison' => "Champ non numérique", 'contexte' => []];
        }

        return [
            'applicable' => $champValue < $valeur,
            'raison' => $champValue >= $valeur ? "'{$champ}' = {$champValue} (doit être < {$valeur})" : null,
            'contexte' => ['champ' => $champ, 'valeur' => $champValue, 'seuil' => $valeur],
        ];
    }

    private function evaluateFieldLte(array $condition, array $contexte): array
    {
        $champ = $condition['champ'] ?? null;
        $valeur = $condition['valeur'] ?? null;
        $champValue = $contexte[$champ] ?? null;

        if (!is_numeric($champValue)) {
            return ['applicable' => false, 'raison' => "Champ non numérique", 'contexte' => []];
        }

        return [
            'applicable' => $champValue <= $valeur,
            'raison' => $champValue > $valeur ? "'{$champ}' = {$champValue} (doit être <= {$valeur})" : null,
            'contexte' => ['champ' => $champ, 'valeur' => $champValue, 'seuil' => $valeur],
        ];
    }

    private function evaluateHasTravail(array $condition, WorkTree $workTree): array
    {
        $travail = $condition['travail'] ?? null;

        if ($travail === null) {
            throw new InvalidRuleException('has_travail', "Travail requis pour has_travail", $condition);
        }

        $present = $workTree->hasTravail($travail);

        return [
            'applicable' => $present,
            'raison' => !$present ? "Travail '{$travail}' absent" : null,
            'contexte' => ['travail' => $travail],
        ];
    }

    private function evaluateHasCircuit(array $condition, WorkTree $workTree, array $contexte): array
    {
        $circuit = $condition['circuit'] ?? null;

        // V1: vérification simplifiée basée sur le contexte
        $circuits = $contexte['circuits'] ?? [];

        return [
            'applicable' => in_array($circuit, $circuits, true),
            'raison' => null,
            'contexte' => ['circuit' => $circuit],
        ];
    }

    private function evaluateCircuitType(array $condition, WorkTree $workTree, array $contexte): array
    {
        // V1: toujours applicable si le circuit est présent
        return ['applicable' => true, 'raison' => null, 'contexte' => []];
    }

    private function evaluateCircuitInList(array $condition, WorkTree $workTree, array $contexte): array
    {
        $liste = $condition['liste'] ?? [];
        $circuits = $contexte['circuits'] ?? [];

        $intersection = array_intersect($circuits, $liste);

        return [
            'applicable' => !empty($intersection),
            'raison' => empty($intersection) ? "Aucun circuit de la liste présent" : null,
            'contexte' => ['circuits_trouves' => $intersection],
        ];
    }

    private function evaluateHasPointLumineux(WorkTree $workTree): array
    {
        // V1: vérifier si un travail d'éclairage est présent
        $eclairagePresent = $workTree->hasTravail('distribution_eclairage')
            || $workTree->hasTravail('eclairage_cuisine')
            || $workTree->hasTravail('eclairage_sdb');

        return [
            'applicable' => $eclairagePresent,
            'raison' => !$eclairagePresent ? "Aucun point lumineux détecté" : null,
            'contexte' => [],
        ];
    }

    private function evaluateAllCircuitsProtected(array $condition, WorkTree $workTree): array
    {
        // V1: vérifier si le tableau électrique a des différentiels
        if (!$workTree->hasTravail('tableau_electrique')) {
            return ['applicable' => false, 'raison' => "Pas de tableau électrique", 'contexte' => []];
        }

        // On suppose conforme si le travail existe (V1 simplifiée)
        return ['applicable' => true, 'raison' => null, 'contexte' => []];
    }

    private function evaluateComparison(array $condition, array $contexte): array
    {
        $gauche = $this->resolveValue($condition['gauche'] ?? null, $contexte);
        $operateur = $condition['operateur'] ?? 'eq';
        $droite = $this->resolveValue($condition['droite'] ?? null, $contexte);

        if ($gauche === null || $droite === null) {
            return [
                'applicable' => false,
                'raison' => "Valeurs de comparaison manquantes",
                'contexte' => ['gauche' => $gauche, 'droite' => $droite],
            ];
        }

        $result = match ($operateur) {
            'eq' => $gauche === $droite,
            'neq' => $gauche !== $droite,
            'gt' => $gauche > $droite,
            'gte' => $gauche >= $droite,
            'lt' => $gauche < $droite,
            'lte' => $gauche <= $droite,
            default => false,
        };

        return [
            'applicable' => $result,
            'raison' => !$result ? "{$gauche} {$operateur} {$droite} est faux" : null,
            'contexte' => ['gauche' => $gauche, 'operateur' => $operateur, 'droite' => $droite],
        ];
    }

    private function evaluateOr(array $condition, WorkTree $workTree, array $contexte): array
    {
        $conditions = $condition['conditions'] ?? [];

        foreach ($conditions as $subCondition) {
            $result = $this->evaluateCondition($subCondition, $workTree, $contexte);
            if ($result['applicable']) {
                return ['applicable' => true, 'raison' => null, 'contexte' => $result['contexte']];
            }
        }

        return ['applicable' => false, 'raison' => "Aucune condition OR satisfaite", 'contexte' => []];
    }

    private function evaluateAnd(array $condition, WorkTree $workTree, array $contexte): array
    {
        $conditions = $condition['conditions'] ?? [];
        $allContexte = [];

        foreach ($conditions as $subCondition) {
            $result = $this->evaluateCondition($subCondition, $workTree, $contexte);
            if (!$result['applicable']) {
                return ['applicable' => false, 'raison' => $result['raison'], 'contexte' => $result['contexte']];
            }
            $allContexte = array_merge($allContexte, $result['contexte']);
        }

        return ['applicable' => true, 'raison' => null, 'contexte' => $allContexte];
    }

    private function evaluateNot(array $condition, WorkTree $workTree, array $contexte): array
    {
        $subCondition = $condition['condition'] ?? [];
        $result = $this->evaluateCondition($subCondition, $workTree, $contexte);

        return [
            'applicable' => !$result['applicable'],
            'raison' => $result['applicable'] ? "Condition NOT inversée" : null,
            'contexte' => $result['contexte'],
        ];
    }

    // =========================================================================
    // Résolution de valeurs
    // =========================================================================

    private function resolveValue(mixed $value, array $contexte): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_scalar($value)) {
            return $value;
        }

        if (!is_array($value)) {
            return $value;
        }

        $type = $value['type'] ?? null;

        return match ($type) {
            'field' => $contexte[$value['champ']] ?? null,
            'formula' => $this->evaluateFormula($value['expression'] ?? '', $contexte),
            default => $value,
        };
    }

    // =========================================================================
    // Calculs (table lookup, formules)
    // =========================================================================

    /**
     * Effectue une recherche dans une table de référence.
     *
     * @return array{valeur: mixed, trouve: bool, source: string|null}
     */
    public function tableLookup(string $tableName, array $cles, array $contexte): array
    {
        if (!isset($this->tables[$tableName])) {
            return ['valeur' => null, 'trouve' => false, 'source' => null];
        }

        $table = $this->tables[$tableName];

        // Navigation dans la table
        $current = $table;
        $path = [];

        foreach ($cles as $cleConfig) {
            $cleName = is_string($cleConfig) ? $cleConfig : ($cleConfig['champ'] ?? null);

            if ($cleName === null) {
                continue;
            }

            $cleValue = $contexte[$cleName] ?? null;

            if ($cleValue === null) {
                return ['valeur' => null, 'trouve' => false, 'source' => "Clé '{$cleName}' manquante"];
            }

            $path[] = $cleValue;

            if (is_array($current) && isset($current[$cleValue])) {
                $current = $current[$cleValue];
            } else {
                return ['valeur' => null, 'trouve' => false, 'source' => "Valeur non trouvée pour '{$cleValue}'"];
            }
        }

        return [
            'valeur' => $current,
            'trouve' => true,
            'source' => $tableName . ':' . implode('.', $path),
        ];
    }

    /**
     * Évalue une formule simple.
     */
    public function evaluateFormula(string $expression, array $contexte): mixed
    {
        if (empty($expression)) {
            return null;
        }

        // Substituer les variables
        $expr = preg_replace_callback(
            '/\b([a-z_][a-z0-9_]*)\b/i',
            function ($matches) use ($contexte) {
                $var = $matches[1];
                // Fonctions autorisées
                if (in_array($var, ['ceil', 'floor', 'round', 'max', 'min', 'abs'])) {
                    return $var;
                }
                return $contexte[$var] ?? 0;
            },
            $expression
        );

        // Évaluation sécurisée (V1: seulement opérations mathématiques basiques)
        try {
            // Vérifier que l'expression ne contient que des caractères autorisés
            if (!preg_match('/^[\d\s\+\-\*\/\(\)\.,ceillfoorrundmaxinbs]+$/i', $expr)) {
                return null;
            }

            // Utiliser eval avec précaution (uniquement expressions mathématiques)
            $result = @eval("return {$expr};");
            return $result;
        } catch (\Throwable $e) {
            return null;
        }
    }

    // =========================================================================
    // Vérification de conformité
    // =========================================================================

    /**
     * Vérifie si une valeur respecte les contraintes imposées.
     *
     * @return array{conforme: bool, alerte: Alerte|null, impossibilite: Impossibilite|null}
     */
    public function checkConformite(
        mixed $valeur,
        array $valeurImposee,
        string $ruleId,
        string $severite,
        string $reference,
    ): array {
        // V1: vérifications basiques
        if (isset($valeurImposee['minimum'])) {
            if ($valeur < $valeurImposee['minimum']) {
                return $this->createNonConformite(
                    ruleId: $ruleId,
                    severite: $severite,
                    reference: $reference,
                    message: "Valeur {$valeur} inférieure au minimum {$valeurImposee['minimum']}",
                );
            }
        }

        if (isset($valeurImposee['maximum'])) {
            if ($valeur > $valeurImposee['maximum']) {
                return $this->createNonConformite(
                    ruleId: $ruleId,
                    severite: $severite,
                    reference: $reference,
                    message: "Valeur {$valeur} supérieure au maximum {$valeurImposee['maximum']}",
                );
            }
        }

        if (isset($valeurImposee['section_min'])) {
            if ($valeur < $valeurImposee['section_min']) {
                return $this->createNonConformite(
                    ruleId: $ruleId,
                    severite: $severite,
                    reference: $reference,
                    message: "Section {$valeur}mm² inférieure au minimum {$valeurImposee['section_min']}mm²",
                );
            }
        }

        return ['conforme' => true, 'alerte' => null, 'impossibilite' => null];
    }

    private function createNonConformite(
        string $ruleId,
        string $severite,
        string $reference,
        string $message,
    ): array {
        if ($severite === 'bloquant') {
            return [
                'conforme' => false,
                'alerte' => null,
                'impossibilite' => Impossibilite::normeNonRespectee(
                    code: $ruleId,
                    message: $message,
                    regleId: $ruleId,
                    reference: $reference,
                ),
            ];
        }

        return [
            'conforme' => false,
            'alerte' => Alerte::attention(
                code: $ruleId,
                message: $message,
                regleId: $ruleId,
            ),
            'impossibilite' => null,
        ];
    }
}
