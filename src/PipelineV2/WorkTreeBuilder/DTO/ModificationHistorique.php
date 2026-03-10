<?php

declare(strict_types=1);

namespace App\PipelineV2\WorkTreeBuilder\DTO;

use DateTimeImmutable;

/**
 * Représente une entrée dans l'historique des modifications du WorkTree.
 * Permet la traçabilité des changements effectués par chaque étape du pipeline.
 */
final class ModificationHistorique
{
    public const ETAPE_WORKTREE_BUILDER = 'worktree_builder';
    public const ETAPE_NORMES_ENGINE = 'normes_engine';
    public const ETAPE_AUDIT = 'audit';
    public const ETAPE_UTILISATEUR = 'utilisateur';

    public const ACTION_CREATION = 'creation';
    public const ACTION_AJOUT = 'ajout';
    public const ACTION_MODIFICATION = 'modification';
    public const ACTION_SUPPRESSION = 'suppression';

    public function __construct(
        public readonly string $etape,
        public readonly string $action,
        public readonly string $cible,
        public readonly ?array $avant,
        public readonly array $apres,
        public readonly string $raison,
        public readonly DateTimeImmutable $timestamp,
    ) {}

    public static function creation(string $etape, string $cible, array $valeur, string $raison = ''): self
    {
        return new self(
            etape: $etape,
            action: self::ACTION_CREATION,
            cible: $cible,
            avant: null,
            apres: $valeur,
            raison: $raison,
            timestamp: new DateTimeImmutable(),
        );
    }

    public static function modification(
        string $etape,
        string $cible,
        ?string $champModifie = null,
        mixed $ancienneValeur = null,
        mixed $nouvelleValeur = null,
        ?array $avant = null,
        ?array $apres = null,
        string $raison = '',
    ): self {
        // Support deux signatures : détaillée (champModifie) ou batch (avant/apres)
        if ($champModifie !== null) {
            $avant = $avant ?? [$champModifie => $ancienneValeur];
            $apres = $apres ?? [$champModifie => $nouvelleValeur];
        }

        return new self(
            etape: $etape,
            action: self::ACTION_MODIFICATION,
            cible: $cible,
            avant: $avant,
            apres: $apres ?? [],
            raison: $raison,
            timestamp: new DateTimeImmutable(),
        );
    }

    public static function ajout(string $etape, string $cible, array $valeur, string $raison = ''): self
    {
        return new self(
            etape: $etape,
            action: self::ACTION_AJOUT,
            cible: $cible,
            avant: null,
            apres: $valeur,
            raison: $raison,
            timestamp: new DateTimeImmutable(),
        );
    }

    public function toArray(): array
    {
        return [
            'etape' => $this->etape,
            'action' => $this->action,
            'cible' => $this->cible,
            'avant' => $this->avant,
            'apres' => $this->apres,
            'raison' => $this->raison,
            'timestamp' => $this->timestamp->format('c'),
        ];
    }
}
