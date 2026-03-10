<?php

declare(strict_types=1);

namespace App\PipelineV2\NormesEngine\DTO;

/**
 * Représente une impossibilité bloquante.
 *
 * L'impossibilité empêche la génération du devis car la conformité
 * normative ne peut pas être assurée.
 */
final readonly class Impossibilite
{
    public const TYPE_NORME_NON_RESPECTEE = 'norme_non_respectee';
    public const TYPE_MATERIEL_INCOMPATIBLE = 'materiel_incompatible';
    public const TYPE_CONFIGURATION_IMPOSSIBLE = 'configuration_impossible';
    public const TYPE_DONNEE_MANQUANTE_CRITIQUE = 'donnee_manquante_critique';

    /**
     * @param string $code Code unique de l'impossibilité
     * @param string $type Type d'impossibilité
     * @param string $message Message descriptif
     * @param string|null $regleId ID de la règle ayant détecté l'impossibilité
     * @param string|null $reference Référence normative
     * @param array $resolutionsPossibles Actions possibles pour résoudre
     * @param array $contexte Contexte additionnel
     */
    public function __construct(
        public string $code,
        public string $type,
        public string $message,
        public ?string $regleId = null,
        public ?string $reference = null,
        public array $resolutionsPossibles = [],
        public array $contexte = [],
    ) {}

    /**
     * Crée une impossibilité pour norme non respectée.
     */
    public static function normeNonRespectee(
        string $code,
        string $message,
        string $regleId,
        string $reference,
        array $resolutions = [],
    ): self {
        return new self(
            code: $code,
            type: self::TYPE_NORME_NON_RESPECTEE,
            message: $message,
            regleId: $regleId,
            reference: $reference,
            resolutionsPossibles: $resolutions,
        );
    }

    /**
     * Crée une impossibilité pour matériel incompatible.
     */
    public static function materielIncompatible(
        string $code,
        string $message,
        ?string $regleId = null,
        array $contexte = [],
    ): self {
        return new self(
            code: $code,
            type: self::TYPE_MATERIEL_INCOMPATIBLE,
            message: $message,
            regleId: $regleId,
            contexte: $contexte,
        );
    }

    /**
     * Crée une impossibilité pour configuration impossible.
     */
    public static function configurationImpossible(
        string $code,
        string $message,
        array $resolutions = [],
        array $contexte = [],
    ): self {
        return new self(
            code: $code,
            type: self::TYPE_CONFIGURATION_IMPOSSIBLE,
            message: $message,
            resolutionsPossibles: $resolutions,
            contexte: $contexte,
        );
    }

    /**
     * Crée une impossibilité pour donnée manquante critique.
     */
    public static function donneeManquanteCritique(
        string $code,
        string $message,
        string $champManquant,
    ): self {
        return new self(
            code: $code,
            type: self::TYPE_DONNEE_MANQUANTE_CRITIQUE,
            message: $message,
            resolutionsPossibles: ["Renseigner le champ '{$champManquant}'"],
            contexte: ['champ_manquant' => $champManquant],
        );
    }

    /**
     * Export pour affichage.
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'type' => $this->type,
            'message' => $this->message,
            'regle_id' => $this->regleId,
            'reference' => $this->reference,
            'resolutions_possibles' => $this->resolutionsPossibles,
            'contexte' => $this->contexte,
        ];
    }
}
