<?php

declare(strict_types=1);

namespace App\PipelineV2\WorkTreeBuilder\DTO;

/**
 * Représente un domaine obligatoire non couvert dans le WorkTree.
 */
final class DomaineNonCouvert
{
    public function __construct(
        public readonly string $domaine,
        public readonly array $verificationEchouee,
        public readonly string $actionRequise,
        public readonly bool $bloquant,
    ) {}

    public static function alerte(string $domaine, array $verification, string $action): self
    {
        return new self(
            domaine: $domaine,
            verificationEchouee: $verification,
            actionRequise: $action,
            bloquant: false,
        );
    }

    public static function bloquant(string $domaine, array $verification, string $action): self
    {
        return new self(
            domaine: $domaine,
            verificationEchouee: $verification,
            actionRequise: $action,
            bloquant: true,
        );
    }

    public function toArray(): array
    {
        return [
            'domaine' => $this->domaine,
            'verification_echouee' => $this->verificationEchouee,
            'action_requise' => $this->actionRequise,
            'bloquant' => $this->bloquant,
        ];
    }
}
