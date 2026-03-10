<?php

declare(strict_types=1);

namespace App\PipelineV2\WorkTreeBuilder;

use App\PipelineV2\WorkTreeBuilder\DTO\WorkTree;
use App\PipelineV2\WorkTreeBuilder\DTO\WorkTreeBuilderInput;

/**
 * Interface pour le WorkTreeBuilder.
 *
 * Le WorkTreeBuilder transforme une intention de chantier (extraite par l'IA)
 * en un arbre de travail structuré exploitable par les moteurs suivants :
 * - NormesEngine : complète, ajuste, impose des éléments normatifs
 * - TechnicalNeedsGenerator : traduit les actions métier en besoins techniques
 *
 * Position dans le pipeline :
 * [3] aiBuildChantier → [4] WorkTreeBuilder → [5] NormesEngine
 */
interface WorkTreeBuilderInterface
{
    /**
     * Construit l'arbre de travail à partir de l'entrée.
     *
     * Étapes :
     * 1. Charge la définition du type de chantier
     * 2. Complète le contexte avec les valeurs par défaut
     * 3. Active les travaux de base
     * 4. Évalue et active les travaux conditionnels
     * 5. Expande les sous-travaux avec quantités et multiplicateurs
     * 6. Vérifie les domaines obligatoires
     * 7. Collecte les délégations pour NormesEngine
     *
     * @throws Exception\UnknownChantierTypeException Si le type de chantier n'existe pas
     * @throws Exception\UndefinedTravailException Si un travail référencé n'est pas défini
     * @throws Exception\InvalidConditionException Si une condition est malformée
     */
    public function build(WorkTreeBuilderInput $input): WorkTree;

    /**
     * Vérifie si un type de chantier est supporté.
     */
    public function supportsChantierType(string $typeChantier): bool;

    /**
     * Retourne la liste des types de chantier disponibles.
     *
     * @return array<string, string> [id => label]
     */
    public function getAvailableChantierTypes(): array;
}
