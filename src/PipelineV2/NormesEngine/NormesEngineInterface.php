<?php

declare(strict_types=1);

namespace App\PipelineV2\NormesEngine;

use App\PipelineV2\NormesEngine\DTO\NormesEngineInput;
use App\PipelineV2\NormesEngine\DTO\NormesEngineOutput;

/**
 * Interface du NormesEngine.
 *
 * Le NormesEngine est responsable de :
 * - Valider la conformité normative du WorkTree
 * - Ajouter les éléments obligatoires manquants
 * - Ajuster les quantités selon les normes
 * - Produire des alertes et impossibilités
 */
interface NormesEngineInterface
{
    /**
     * Traite le WorkTree et applique les règles normatives.
     *
     * @param NormesEngineInput $input WorkTree et contexte à traiter
     * @return NormesEngineOutput WorkTree enrichi avec statut, alertes et historique
     */
    public function process(NormesEngineInput $input): NormesEngineOutput;

    /**
     * Vérifie si une règle spécifique est supportée.
     */
    public function supportsRule(string $ruleId): bool;

    /**
     * Retourne la liste des catégories de règles disponibles.
     *
     * @return string[]
     */
    public function getAvailableCategories(): array;
}
