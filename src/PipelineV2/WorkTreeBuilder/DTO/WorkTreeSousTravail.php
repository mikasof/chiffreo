<?php

declare(strict_types=1);

namespace App\PipelineV2\WorkTreeBuilder\DTO;

/**
 * Représente un sous-travail dans l'arbre de travail.
 *
 * Note importante :
 * - quantite_finale représente le nombre d'occurrences de l'ACTION MÉTIER
 * - PAS la quantité de matériel (qui sera calculée par TechnicalNeedsGenerator)
 */
final class WorkTreeSousTravail
{
    public const UNITE_UNITE = 'unite';
    public const UNITE_LIGNE = 'ligne';
    public const UNITE_POINT = 'point';
    public const UNITE_EQUIPEMENT = 'equipement';
    public const UNITE_METRE_LINEAIRE = 'metre_lineaire';

    public const SEMANTIQUE_OCCURRENCE_ACTION = 'occurrence_action';

    public function __construct(
        public readonly string $id,
        public readonly string $instanceId,
        public readonly string $label,
        public readonly int $ordre,
        public readonly Scope $scope,
        public readonly int|float|null $quantiteBrute,
        public readonly ?Multiplicateur $multiplicateur,
        public readonly int|float|null $quantiteFinale,
        public readonly string $uniteMetier,
        public readonly string $semantique,
        public readonly bool $delegueNormes,
        public readonly string $origine,
        public readonly Mutabilite $mutabilite,
    ) {}

    /**
     * Crée un sous-travail à partir de sa définition config.
     */
    public static function fromDefinition(
        string $id,
        array $definition,
        Scope $scope,
        int $ordre,
        int|float|null $quantiteBrute,
        ?Multiplicateur $multiplicateur,
        int|float|null $quantiteFinale,
        bool $delegueNormes,
        string $origine,
    ): self {
        $instanceId = self::generateInstanceId($id, $scope);

        return new self(
            id: $id,
            instanceId: $instanceId,
            label: $definition['label'] ?? $id,
            ordre: $ordre,
            scope: $scope,
            quantiteBrute: $quantiteBrute,
            multiplicateur: $multiplicateur,
            quantiteFinale: $quantiteFinale,
            uniteMetier: $definition['unite_metier'] ?? self::UNITE_UNITE,
            semantique: self::SEMANTIQUE_OCCURRENCE_ACTION,
            delegueNormes: $delegueNormes,
            origine: $origine,
            mutabilite: Mutabilite::parWorkTreeBuilder(),
        );
    }

    /**
     * Génère un identifiant d'instance unique.
     */
    private static function generateInstanceId(string $id, Scope $scope): string
    {
        $parts = [$id];

        if ($scope->reference !== null) {
            $parts[] = $scope->reference;
        } else {
            $parts[] = $scope->type;
        }

        $parts[] = sprintf('%03d', random_int(1, 999));

        return implode('_', $parts);
    }

    /**
     * Retourne une copie avec la quantité finale mise à jour.
     */
    public function withQuantiteFinale(int|float|null $quantiteFinale): self
    {
        return new self(
            id: $this->id,
            instanceId: $this->instanceId,
            label: $this->label,
            ordre: $this->ordre,
            scope: $this->scope,
            quantiteBrute: $this->quantiteBrute,
            multiplicateur: $this->multiplicateur,
            quantiteFinale: $quantiteFinale,
            uniteMetier: $this->uniteMetier,
            semantique: $this->semantique,
            delegueNormes: $this->delegueNormes,
            origine: $this->origine,
            mutabilite: $this->mutabilite,
        );
    }

    /**
     * Retourne une copie avec la mutabilité mise à jour (après modification NormesEngine).
     */
    public function withMutabilite(Mutabilite $mutabilite): self
    {
        return new self(
            id: $this->id,
            instanceId: $this->instanceId,
            label: $this->label,
            ordre: $this->ordre,
            scope: $this->scope,
            quantiteBrute: $this->quantiteBrute,
            multiplicateur: $this->multiplicateur,
            quantiteFinale: $this->quantiteFinale,
            uniteMetier: $this->uniteMetier,
            semantique: $this->semantique,
            delegueNormes: $this->delegueNormes,
            origine: $this->origine,
            mutabilite: $mutabilite,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'instance_id' => $this->instanceId,
            'label' => $this->label,
            'ordre' => $this->ordre,
            'scope' => $this->scope->toArray(),
            'quantite_brute' => $this->quantiteBrute,
            'multiplicateur' => $this->multiplicateur?->toArray(),
            'quantite_finale' => $this->quantiteFinale,
            'unite_metier' => $this->uniteMetier,
            'semantique' => $this->semantique,
            'delegue_normes' => $this->delegueNormes,
            'origine' => $this->origine,
            'mutabilite' => $this->mutabilite->toArray(),
        ];
    }
}
