<?php

declare(strict_types=1);

namespace App\PipelineV2\NormesEngine\DTO;

/**
 * Représente un élément qui n'a pas pu être déterminé.
 *
 * L'élément nécessite des informations supplémentaires ou une décision
 * manuelle pour être résolu.
 */
final readonly class ElementNonDetermine
{
    public const TYPE_QUANTITE = 'quantite';
    public const TYPE_MATERIEL = 'materiel';
    public const TYPE_SECTION_CABLE = 'section_cable';
    public const TYPE_TYPE_DIFFERENTIEL = 'type_differentiel';
    public const TYPE_CONFIGURATION = 'configuration';

    /**
     * @param string $code Code unique de l'élément
     * @param string $type Type d'élément
     * @param string $description Description de ce qui n'est pas déterminé
     * @param string|null $travailId ID du travail concerné
     * @param string|null $sousTravailId ID du sous-travail concerné
     * @param array $champsManquants Champs nécessaires pour déterminer
     * @param array $valeursParDefaut Valeurs par défaut proposées
     * @param array $contexte Contexte additionnel
     */
    public function __construct(
        public string $code,
        public string $type,
        public string $description,
        public ?string $travailId = null,
        public ?string $sousTravailId = null,
        public array $champsManquants = [],
        public array $valeursParDefaut = [],
        public array $contexte = [],
    ) {}

    /**
     * Crée un élément de quantité non déterminée.
     */
    public static function quantite(
        string $code,
        string $description,
        string $sousTravailId,
        array $champsManquants,
        ?int $valeurParDefaut = null,
    ): self {
        return new self(
            code: $code,
            type: self::TYPE_QUANTITE,
            description: $description,
            sousTravailId: $sousTravailId,
            champsManquants: $champsManquants,
            valeursParDefaut: $valeurParDefaut !== null ? ['quantite' => $valeurParDefaut] : [],
        );
    }

    /**
     * Crée un élément de section câble non déterminée.
     */
    public static function sectionCable(
        string $code,
        string $description,
        string $sousTravailId,
        array $champsManquants,
        ?float $sectionParDefaut = null,
    ): self {
        return new self(
            code: $code,
            type: self::TYPE_SECTION_CABLE,
            description: $description,
            sousTravailId: $sousTravailId,
            champsManquants: $champsManquants,
            valeursParDefaut: $sectionParDefaut !== null ? ['section' => $sectionParDefaut] : [],
        );
    }

    /**
     * Crée un élément de type différentiel non déterminé.
     */
    public static function typeDifferentiel(
        string $code,
        string $description,
        string $travailId,
        array $champsManquants,
        ?string $typeParDefaut = null,
    ): self {
        return new self(
            code: $code,
            type: self::TYPE_TYPE_DIFFERENTIEL,
            description: $description,
            travailId: $travailId,
            champsManquants: $champsManquants,
            valeursParDefaut: $typeParDefaut !== null ? ['type_differentiel' => $typeParDefaut] : [],
        );
    }

    /**
     * Peut-on utiliser une valeur par défaut ?
     */
    public function aValeurParDefaut(): bool
    {
        return !empty($this->valeursParDefaut);
    }

    /**
     * Export pour affichage.
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'type' => $this->type,
            'description' => $this->description,
            'travail_id' => $this->travailId,
            'sous_travail_id' => $this->sousTravailId,
            'champs_manquants' => $this->champsManquants,
            'valeurs_par_defaut' => $this->valeursParDefaut,
        ];
    }
}
