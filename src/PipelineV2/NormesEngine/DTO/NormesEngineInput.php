<?php

declare(strict_types=1);

namespace App\PipelineV2\NormesEngine\DTO;

use App\PipelineV2\WorkTreeBuilder\DTO\WorkTree;

/**
 * Input du NormesEngine.
 *
 * Contient le WorkTree à traiter et le contexte complet du chantier.
 */
final readonly class NormesEngineInput
{
    /**
     * @param WorkTree $workTree Le WorkTree à enrichir
     * @param array $contexte Le contexte complet du chantier
     * @param string $typeChantier Le type de macro-chantier
     * @param array $options Options de traitement (mode strict, etc.)
     */
    public function __construct(
        public WorkTree $workTree,
        public array $contexte,
        public string $typeChantier,
        public array $options = [],
    ) {}

    /**
     * Mode strict : les alertes deviennent bloquantes.
     */
    public function isModeStrict(): bool
    {
        return $this->options['mode_strict'] ?? false;
    }

    /**
     * Catégories de règles à appliquer (vide = toutes).
     *
     * @return string[]
     */
    public function getCategoriesActives(): array
    {
        return $this->options['categories'] ?? [];
    }

    /**
     * Règles à ignorer explicitement.
     *
     * @return string[]
     */
    public function getReglesIgnorees(): array
    {
        return $this->options['regles_ignorees'] ?? [];
    }
}
