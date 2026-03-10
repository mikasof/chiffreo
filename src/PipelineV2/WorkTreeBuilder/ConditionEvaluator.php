<?php

declare(strict_types=1);

namespace App\PipelineV2\WorkTreeBuilder;

use App\PipelineV2\WorkTreeBuilder\Exception\InvalidConditionException;

/**
 * Évalue les conditions structurées définies dans la configuration.
 *
 * Types de conditions supportés :
 * - context_has : vérifie la présence d'un champ
 * - field_eq, field_neq : égalité / inégalité
 * - field_gt, field_gte, field_lt, field_lte : comparaisons numériques
 * - or, and, not : opérateurs logiques
 * - delegate_normes_engine : délégation (retourne toujours 'delegate')
 */
final class ConditionEvaluator
{
    /**
     * Évalue une condition contre un contexte.
     *
     * @return bool|string true/false pour les conditions normales, 'delegate' pour les délégations
     */
    public function evaluate(array $condition, array $contexte): bool|string
    {
        if (!isset($condition['type'])) {
            throw new InvalidConditionException($condition, "Le champ 'type' est requis");
        }

        return match ($condition['type']) {
            'context_has' => $this->evaluateContextHas($condition, $contexte),
            'field_eq' => $this->evaluateFieldEq($condition, $contexte),
            'field_neq' => $this->evaluateFieldNeq($condition, $contexte),
            'field_gt' => $this->evaluateFieldGt($condition, $contexte),
            'field_gte' => $this->evaluateFieldGte($condition, $contexte),
            'field_lt' => $this->evaluateFieldLt($condition, $contexte),
            'field_lte' => $this->evaluateFieldLte($condition, $contexte),
            'or' => $this->evaluateOr($condition, $contexte),
            'and' => $this->evaluateAnd($condition, $contexte),
            'not' => $this->evaluateNot($condition, $contexte),
            'delegate_normes_engine' => 'delegate',
            'always_true' => true,
            'always_false' => false,
            default => throw new InvalidConditionException(
                $condition,
                sprintf("Type de condition inconnu : '%s'", $condition['type'])
            ),
        };
    }

    /**
     * Évalue une condition et retourne un résultat détaillé pour le logging.
     */
    public function evaluateWithDetails(array $condition, array $contexte): array
    {
        $result = $this->evaluate($condition, $contexte);

        return [
            'condition' => $condition,
            'resultat' => $result,
            'contexte_utilise' => $this->extractUsedContext($condition, $contexte),
        ];
    }

    // -------------------------------------------------------------------------
    // Évaluateurs spécifiques
    // -------------------------------------------------------------------------

    private function evaluateContextHas(array $condition, array $contexte): bool
    {
        // Support both 'champ' (single) and 'champs' (array)
        $champs = $condition['champs'] ?? (isset($condition['champ']) ? [$condition['champ']] : null);

        if ($champs === null || empty($champs)) {
            throw new InvalidConditionException($condition, "Le champ 'champ' ou 'champs' est requis pour context_has");
        }

        // All specified fields must be present
        foreach ($champs as $champ) {
            if (!isset($contexte[$champ]) || $contexte[$champ] === null || $contexte[$champ] === false) {
                return false;
            }
        }

        return true;
    }

    private function evaluateFieldEq(array $condition, array $contexte): bool
    {
        [$champValue, $expectedValue] = $this->extractComparisonValues($condition, $contexte);

        if ($champValue === null) {
            return false;
        }

        return $champValue === $expectedValue;
    }

    private function evaluateFieldNeq(array $condition, array $contexte): bool
    {
        [$champValue, $expectedValue] = $this->extractComparisonValues($condition, $contexte);

        if ($champValue === null) {
            return false;
        }

        return $champValue !== $expectedValue;
    }

    private function evaluateFieldGt(array $condition, array $contexte): bool
    {
        [$champValue, $expectedValue] = $this->extractComparisonValues($condition, $contexte);

        if ($champValue === null || !is_numeric($champValue)) {
            return false;
        }

        return $champValue > $expectedValue;
    }

    private function evaluateFieldGte(array $condition, array $contexte): bool
    {
        [$champValue, $expectedValue] = $this->extractComparisonValues($condition, $contexte);

        if ($champValue === null || !is_numeric($champValue)) {
            return false;
        }

        return $champValue >= $expectedValue;
    }

    private function evaluateFieldLt(array $condition, array $contexte): bool
    {
        [$champValue, $expectedValue] = $this->extractComparisonValues($condition, $contexte);

        if ($champValue === null || !is_numeric($champValue)) {
            return false;
        }

        return $champValue < $expectedValue;
    }

    private function evaluateFieldLte(array $condition, array $contexte): bool
    {
        [$champValue, $expectedValue] = $this->extractComparisonValues($condition, $contexte);

        if ($champValue === null || !is_numeric($champValue)) {
            return false;
        }

        return $champValue <= $expectedValue;
    }

    private function evaluateOr(array $condition, array $contexte): bool|string
    {
        $conditions = $condition['conditions'] ?? [];

        if (empty($conditions)) {
            throw new InvalidConditionException($condition, "Le champ 'conditions' est requis pour 'or'");
        }

        $hasDelegate = false;

        foreach ($conditions as $subCondition) {
            $result = $this->evaluate($subCondition, $contexte);

            if ($result === true) {
                return true;
            }

            if ($result === 'delegate') {
                $hasDelegate = true;
            }
        }

        // Si aucune condition n'est vraie mais qu'il y a une délégation, on délègue
        return $hasDelegate ? 'delegate' : false;
    }

    private function evaluateAnd(array $condition, array $contexte): bool|string
    {
        $conditions = $condition['conditions'] ?? [];

        if (empty($conditions)) {
            throw new InvalidConditionException($condition, "Le champ 'conditions' est requis pour 'and'");
        }

        $hasDelegate = false;

        foreach ($conditions as $subCondition) {
            $result = $this->evaluate($subCondition, $contexte);

            if ($result === false) {
                return false;
            }

            if ($result === 'delegate') {
                $hasDelegate = true;
            }
        }

        // Toutes les conditions sont vraies ou déléguées
        return $hasDelegate ? 'delegate' : true;
    }

    private function evaluateNot(array $condition, array $contexte): bool|string
    {
        $subCondition = $condition['condition'] ?? null;

        if ($subCondition === null) {
            throw new InvalidConditionException($condition, "Le champ 'condition' est requis pour 'not'");
        }

        $result = $this->evaluate($subCondition, $contexte);

        if ($result === 'delegate') {
            return 'delegate';
        }

        return !$result;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Extrait les valeurs pour une comparaison.
     *
     * @return array [valeur_du_champ, valeur_attendue]
     */
    private function extractComparisonValues(array $condition, array $contexte): array
    {
        $champ = $condition['champ'] ?? null;
        $valeur = $condition['valeur'] ?? null;

        if ($champ === null) {
            throw new InvalidConditionException($condition, "Le champ 'champ' est requis");
        }

        $champValue = $contexte[$champ] ?? null;

        return [$champValue, $valeur];
    }

    /**
     * Extrait les parties du contexte utilisées par une condition.
     */
    private function extractUsedContext(array $condition, array $contexte): array
    {
        $used = [];

        if (isset($condition['champ'])) {
            $champ = $condition['champ'];
            $used[$champ] = $contexte[$champ] ?? null;
        }

        if (isset($condition['conditions'])) {
            foreach ($condition['conditions'] as $subCondition) {
                $used = array_merge($used, $this->extractUsedContext($subCondition, $contexte));
            }
        }

        if (isset($condition['condition'])) {
            $used = array_merge($used, $this->extractUsedContext($condition['condition'], $contexte));
        }

        return $used;
    }
}
