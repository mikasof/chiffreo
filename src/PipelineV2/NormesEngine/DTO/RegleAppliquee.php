<?php

declare(strict_types=1);

namespace App\PipelineV2\NormesEngine\DTO;

/**
 * Représente une règle normative qui a été appliquée.
 *
 * Trace l'application d'une règle avec son résultat et les modifications effectuées.
 */
final readonly class RegleAppliquee
{
    public const ACTION_AJOUT_TRAVAIL = 'ajout_travail';
    public const ACTION_AJOUT_SOUS_TRAVAIL = 'ajout_sous_travail';
    public const ACTION_MODIFICATION_QUANTITE = 'modification_quantite';
    public const ACTION_AJOUT_BESOIN = 'ajout_besoin';
    public const ACTION_MODIFICATION_MATERIEL = 'modification_materiel';
    public const ACTION_VERIFICATION = 'verification';
    public const ACTION_AUCUNE = 'aucune';

    /**
     * @param string $regleId ID de la règle appliquée
     * @param string $categorie Catégorie de la règle
     * @param string $label Label lisible de la règle
     * @param string $source Source (norme, reco, pratique)
     * @param string|null $reference Référence normative
     * @param string $action Type d'action effectuée
     * @param array $modifications Détails des modifications
     * @param array $contexteEvaluation Contexte utilisé pour l'évaluation
     */
    public function __construct(
        public string $regleId,
        public string $categorie,
        public string $label,
        public string $source,
        public ?string $reference,
        public string $action,
        public array $modifications = [],
        public array $contexteEvaluation = [],
    ) {}

    /**
     * Crée une règle appliquée avec ajout de travail.
     */
    public static function ajoutTravail(
        string $regleId,
        string $categorie,
        string $label,
        string $source,
        ?string $reference,
        string $travailId,
        array $contexte = [],
    ): self {
        return new self(
            regleId: $regleId,
            categorie: $categorie,
            label: $label,
            source: $source,
            reference: $reference,
            action: self::ACTION_AJOUT_TRAVAIL,
            modifications: ['travail_id' => $travailId],
            contexteEvaluation: $contexte,
        );
    }

    /**
     * Crée une règle appliquée avec modification de quantité.
     */
    public static function modificationQuantite(
        string $regleId,
        string $categorie,
        string $label,
        string $source,
        ?string $reference,
        string $sousTravailId,
        int|float $ancienneQuantite,
        int|float $nouvelleQuantite,
        array $contexte = [],
    ): self {
        return new self(
            regleId: $regleId,
            categorie: $categorie,
            label: $label,
            source: $source,
            reference: $reference,
            action: self::ACTION_MODIFICATION_QUANTITE,
            modifications: [
                'sous_travail_id' => $sousTravailId,
                'ancienne_quantite' => $ancienneQuantite,
                'nouvelle_quantite' => $nouvelleQuantite,
            ],
            contexteEvaluation: $contexte,
        );
    }

    /**
     * Crée une règle de vérification (sans modification).
     */
    public static function verification(
        string $regleId,
        string $categorie,
        string $label,
        string $source,
        ?string $reference,
        bool $conforme,
        array $contexte = [],
    ): self {
        return new self(
            regleId: $regleId,
            categorie: $categorie,
            label: $label,
            source: $source,
            reference: $reference,
            action: self::ACTION_VERIFICATION,
            modifications: ['conforme' => $conforme],
            contexteEvaluation: $contexte,
        );
    }

    /**
     * La règle a-t-elle modifié le WorkTree ?
     */
    public function aModifie(): bool
    {
        return $this->action !== self::ACTION_VERIFICATION
            && $this->action !== self::ACTION_AUCUNE;
    }

    /**
     * Export pour historique.
     */
    public function toArray(): array
    {
        return [
            'regle_id' => $this->regleId,
            'categorie' => $this->categorie,
            'label' => $this->label,
            'source' => $this->source,
            'reference' => $this->reference,
            'action' => $this->action,
            'modifications' => $this->modifications,
        ];
    }
}
