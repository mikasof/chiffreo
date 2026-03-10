<?php

declare(strict_types=1);

namespace App\PipelineV2\WorkTreeBuilder\DTO;

/**
 * Représente la raison d'activation d'un travail dans le WorkTree.
 */
final class Activation
{
    public const RAISON_BASE = 'base';
    public const RAISON_CONDITION = 'condition';
    public const RAISON_DOMAINE_OBLIGATOIRE = 'domaine_obligatoire';
    public const RAISON_NORME = 'norme';

    public function __construct(
        public readonly string $raison,
        public readonly ?array $conditionEvaluee = null,
    ) {}

    public static function base(): self
    {
        return new self(self::RAISON_BASE);
    }

    public static function condition(array $conditionEvaluee): self
    {
        return new self(self::RAISON_CONDITION, $conditionEvaluee);
    }

    public static function domaineObligatoire(string $domaine): self
    {
        return new self(self::RAISON_DOMAINE_OBLIGATOIRE, ['domaine' => $domaine]);
    }

    public static function norme(string $regleNorme): self
    {
        return new self(self::RAISON_NORME, ['regle' => $regleNorme]);
    }

    public function toArray(): array
    {
        return [
            'raison' => $this->raison,
            'condition_evaluee' => $this->conditionEvaluee,
        ];
    }
}
