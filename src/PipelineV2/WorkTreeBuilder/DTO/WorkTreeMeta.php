<?php

declare(strict_types=1);

namespace App\PipelineV2\WorkTreeBuilder\DTO;

use DateTimeImmutable;

/**
 * Métadonnées du WorkTree.
 */
final class WorkTreeMeta
{
    public const ETAPE_WORKTREE_BUILDER = 'worktree_builder';
    public const ETAPE_NORMES_ENGINE = 'normes_engine';
    public const ETAPE_TECHNICAL_NEEDS = 'technical_needs';
    public const ETAPE_CATALOG_RESOLVER = 'catalog_resolver';
    public const ETAPE_TVA_ENGINE = 'tva_engine';
    public const ETAPE_AUDIT = 'audit';
    public const ETAPE_FINALIZED = 'finalized';

    public function __construct(
        public readonly string $versionConfig,
        public readonly DateTimeImmutable $timestamp,
        public readonly bool $contexteComplet,
        public readonly string $etapeCourante,
    ) {}

    public static function initial(bool $contexteComplet): self
    {
        return new self(
            versionConfig: '2.0.0',
            timestamp: new DateTimeImmutable(),
            contexteComplet: $contexteComplet,
            etapeCourante: self::ETAPE_WORKTREE_BUILDER,
        );
    }

    public function withEtape(string $etape): self
    {
        return new self(
            versionConfig: $this->versionConfig,
            timestamp: new DateTimeImmutable(),
            contexteComplet: $this->contexteComplet,
            etapeCourante: $etape,
        );
    }

    public function toArray(): array
    {
        return [
            'version_config' => $this->versionConfig,
            'timestamp' => $this->timestamp->format('c'),
            'contexte_complet' => $this->contexteComplet,
            'etape_courante' => $this->etapeCourante,
        ];
    }
}
