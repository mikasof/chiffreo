<?php

declare(strict_types=1);

namespace App\PipelineV2\TechnicalNeedsGenerator\DTO;

use App\PipelineV2\NormesEngine\DTO\NormesEngineOutput;

/**
 * Input du TechnicalNeedsGenerator.
 */
final readonly class TechnicalNeedsInput
{
    /**
     * @param NormesEngineOutput $normesOutput Output du NormesEngine (contient WorkTree enrichi)
     * @param array $contexte Contexte complet du chantier
     * @param string $typeChantier Type de macro-chantier
     * @param array $options Options (gamme, préférences, etc.)
     */
    public function __construct(
        public NormesEngineOutput $normesOutput,
        public array $contexte,
        public string $typeChantier,
        public array $options = [],
    ) {}

    /**
     * Gamme de matériel sélectionnée.
     */
    public function getGamme(): string
    {
        return $this->options['gamme'] ?? 'standard';
    }

    /**
     * Inclure les besoins non visibles sur devis ?
     */
    public function inclureNonVisibles(): bool
    {
        return $this->options['inclure_non_visibles'] ?? true;
    }

    /**
     * Agréger les besoins identiques ?
     */
    public function agregerBesoins(): bool
    {
        return $this->options['agreger'] ?? true;
    }

    /**
     * Distance moyenne par défaut pour les points.
     */
    public function getDistanceMoyennePoint(): float
    {
        return $this->options['distance_moyenne_point']
            ?? $this->contexte['distance_moyenne_point']
            ?? 8.0;
    }
}
