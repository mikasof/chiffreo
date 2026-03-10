<?php

declare(strict_types=1);

namespace App\PipelineV2\WorkTreeBuilder\DTO;

/**
 * Représente le statut de mutabilité d'un élément du WorkTree.
 * Définit qui a créé l'élément et qui peut le modifier.
 */
final class Mutabilite
{
    public const SOURCE_WORKTREE_BUILDER = 'worktree_builder';
    public const SOURCE_NORMES_ENGINE = 'normes_engine';
    public const SOURCE_AUDIT = 'audit';
    public const SOURCE_UTILISATEUR = 'utilisateur';

    public function __construct(
        public readonly string $source,
        public readonly bool $verrouille = false,
        public readonly array $modifiablePar = ['normes_engine', 'audit', 'utilisateur'],
    ) {}

    public static function parWorkTreeBuilder(): self
    {
        return new self(self::SOURCE_WORKTREE_BUILDER);
    }

    public static function parNormesEngine(bool $verrouille = false): self
    {
        return new self(
            self::SOURCE_NORMES_ENGINE,
            $verrouille,
            $verrouille ? [] : ['audit', 'utilisateur']
        );
    }

    public static function verrouille(string $source): self
    {
        return new self($source, true, []);
    }

    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'verrouille' => $this->verrouille,
            'modifiable_par' => $this->modifiablePar,
        ];
    }
}
