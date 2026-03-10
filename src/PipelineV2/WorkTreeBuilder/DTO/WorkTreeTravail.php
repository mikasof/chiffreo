<?php

declare(strict_types=1);

namespace App\PipelineV2\WorkTreeBuilder\DTO;

/**
 * Représente un travail dans l'arbre de travail.
 */
final class WorkTreeTravail
{
    public const ORIGINE_CHANTIER = 'chantier';
    public const ORIGINE_NORME = 'norme';
    public const ORIGINE_COMMERCIAL = 'commercial';

    /**
     * @param WorkTreeSousTravail[] $sousTravaux
     */
    public function __construct(
        public readonly string $id,
        public readonly string $instanceId,
        public readonly string $label,
        public readonly int $ordre,
        public readonly string $origine,
        public readonly Scope $scope,
        public readonly Activation $activation,
        public readonly Mutabilite $mutabilite,
        public readonly array $sousTravaux = [],
    ) {}

    /**
     * Crée un travail à partir de sa définition config.
     */
    public static function fromDefinition(
        string $id,
        array $definition,
        int $ordre,
        Activation $activation,
        string $origine,
        Scope $scope,
    ): self {
        $instanceId = self::generateInstanceId($id, $scope);

        return new self(
            id: $id,
            instanceId: $instanceId,
            label: $definition['label'] ?? $id,
            ordre: $ordre,
            origine: $origine,
            scope: $scope,
            activation: $activation,
            mutabilite: Mutabilite::parWorkTreeBuilder(),
            sousTravaux: [],
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
     * Retourne une copie avec les sous-travaux ajoutés.
     *
     * @param WorkTreeSousTravail[] $sousTravaux
     */
    public function withSousTravaux(array $sousTravaux): self
    {
        return new self(
            id: $this->id,
            instanceId: $this->instanceId,
            label: $this->label,
            ordre: $this->ordre,
            origine: $this->origine,
            scope: $this->scope,
            activation: $this->activation,
            mutabilite: $this->mutabilite,
            sousTravaux: $sousTravaux,
        );
    }

    /**
     * Retourne une copie avec un sous-travail ajouté.
     */
    public function addSousTravail(WorkTreeSousTravail $sousTravail): self
    {
        $sousTravaux = $this->sousTravaux;
        $sousTravaux[] = $sousTravail;

        // Trier par ordre
        usort($sousTravaux, fn($a, $b) => $a->ordre <=> $b->ordre);

        return $this->withSousTravaux($sousTravaux);
    }

    /**
     * Retourne une copie avec la mutabilité mise à jour.
     */
    public function withMutabilite(Mutabilite $mutabilite): self
    {
        return new self(
            id: $this->id,
            instanceId: $this->instanceId,
            label: $this->label,
            ordre: $this->ordre,
            origine: $this->origine,
            scope: $this->scope,
            activation: $this->activation,
            mutabilite: $mutabilite,
            sousTravaux: $this->sousTravaux,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'instance_id' => $this->instanceId,
            'label' => $this->label,
            'ordre' => $this->ordre,
            'origine' => $this->origine,
            'scope' => $this->scope->toArray(),
            'activation' => $this->activation->toArray(),
            'mutabilite' => $this->mutabilite->toArray(),
            'sous_travaux' => array_map(fn($st) => $st->toArray(), $this->sousTravaux),
        ];
    }
}
