<?php

declare(strict_types=1);

namespace App\PipelineV2\WorkTreeBuilder\DTO;

/**
 * Représente une délégation vers le NormesEngine.
 * Indique qu'un calcul ou une vérification doit être effectué par le moteur de normes.
 */
final class DelegationNormes
{
    public function __construct(
        public readonly string $type,
        public readonly string $travailInstanceId,
        public readonly ?string $sousTravailInstanceId,
        public readonly array $contexteRequis,
    ) {}

    public static function calculModules(
        string $travailInstanceId,
        string $sousTravailInstanceId,
        array $contexte,
    ): self {
        return new self(
            type: 'calcul_modules_requis',
            travailInstanceId: $travailInstanceId,
            sousTravailInstanceId: $sousTravailInstanceId,
            contexteRequis: $contexte,
        );
    }

    public static function parafoudreObligatoire(
        string $travailInstanceId,
        array $contexte,
    ): self {
        return new self(
            type: 'parafoudre_obligatoire',
            travailInstanceId: $travailInstanceId,
            sousTravailInstanceId: null,
            contexteRequis: $contexte,
        );
    }

    public static function sectionCable(
        string $travailInstanceId,
        string $sousTravailInstanceId,
        array $contexte,
    ): self {
        return new self(
            type: 'section_cable',
            travailInstanceId: $travailInstanceId,
            sousTravailInstanceId: $sousTravailInstanceId,
            contexteRequis: $contexte,
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'travail_instance_id' => $this->travailInstanceId,
            'sous_travail_instance_id' => $this->sousTravailInstanceId,
            'contexte_requis' => $this->contexteRequis,
        ];
    }
}
