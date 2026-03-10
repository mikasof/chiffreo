<?php

declare(strict_types=1);

namespace App\PipelineV2\NormesEngine\DTO;

use App\PipelineV2\WorkTreeBuilder\DTO\WorkTree;

/**
 * Output du NormesEngine.
 *
 * Contient le WorkTree enrichi et toutes les informations de traitement.
 */
final readonly class NormesEngineOutput
{
    public const STATUT_CONFORME = 'conforme';
    public const STATUT_CONFORME_AVEC_ALERTES = 'conforme_avec_alertes';
    public const STATUT_NON_CONFORME = 'non_conforme';
    public const STATUT_INCOMPLET = 'incomplet';

    /**
     * @param WorkTree $workTree Le WorkTree enrichi
     * @param string $statut Le statut global de conformité
     * @param RegleAppliquee[] $reglesAppliquees Règles qui ont été appliquées
     * @param Alerte[] $alertes Alertes (non bloquantes)
     * @param Impossibilite[] $impossibilites Impossibilités (bloquantes)
     * @param ElementNonDetermine[] $elementsNonDetermines Éléments nécessitant plus d'infos
     * @param array $statistiques Statistiques de traitement
     */
    public function __construct(
        public WorkTree $workTree,
        public string $statut,
        public array $reglesAppliquees = [],
        public array $alertes = [],
        public array $impossibilites = [],
        public array $elementsNonDetermines = [],
        public array $statistiques = [],
    ) {}

    /**
     * Crée un output conforme (aucune impossibilité, aucune alerte).
     */
    public static function conforme(
        WorkTree $workTree,
        array $reglesAppliquees = [],
        array $statistiques = [],
    ): self {
        return new self(
            workTree: $workTree,
            statut: self::STATUT_CONFORME,
            reglesAppliquees: $reglesAppliquees,
            alertes: [],
            impossibilites: [],
            elementsNonDetermines: [],
            statistiques: $statistiques,
        );
    }

    /**
     * Crée un output conforme avec alertes.
     */
    public static function conformeAvecAlertes(
        WorkTree $workTree,
        array $reglesAppliquees,
        array $alertes,
        array $statistiques = [],
    ): self {
        return new self(
            workTree: $workTree,
            statut: self::STATUT_CONFORME_AVEC_ALERTES,
            reglesAppliquees: $reglesAppliquees,
            alertes: $alertes,
            impossibilites: [],
            elementsNonDetermines: [],
            statistiques: $statistiques,
        );
    }

    /**
     * Crée un output non conforme (impossibilités bloquantes).
     */
    public static function nonConforme(
        WorkTree $workTree,
        array $reglesAppliquees,
        array $alertes,
        array $impossibilites,
        array $statistiques = [],
    ): self {
        return new self(
            workTree: $workTree,
            statut: self::STATUT_NON_CONFORME,
            reglesAppliquees: $reglesAppliquees,
            alertes: $alertes,
            impossibilites: $impossibilites,
            elementsNonDetermines: [],
            statistiques: $statistiques,
        );
    }

    /**
     * Crée un output incomplet (éléments non déterminés).
     */
    public static function incomplet(
        WorkTree $workTree,
        array $reglesAppliquees,
        array $alertes,
        array $elementsNonDetermines,
        array $statistiques = [],
    ): self {
        return new self(
            workTree: $workTree,
            statut: self::STATUT_INCOMPLET,
            reglesAppliquees: $reglesAppliquees,
            alertes: $alertes,
            impossibilites: [],
            elementsNonDetermines: $elementsNonDetermines,
            statistiques: $statistiques,
        );
    }

    /**
     * Le traitement peut-il continuer vers l'étape suivante ?
     */
    public function peutContinuer(): bool
    {
        return $this->statut !== self::STATUT_NON_CONFORME;
    }

    /**
     * Le WorkTree est-il complètement déterminé ?
     */
    public function estComplet(): bool
    {
        return empty($this->elementsNonDetermines);
    }

    /**
     * Nombre total d'ajustements effectués.
     */
    public function getNbAjustements(): int
    {
        return $this->statistiques['nb_ajustements'] ?? count($this->reglesAppliquees);
    }
}
