<?php

declare(strict_types=1);

namespace App\PipelineV2\WorkTreeBuilder\DTO;

/**
 * Représente le périmètre d'application d'un travail ou sous-travail.
 *
 * Types possibles :
 * - chantier : s'applique au projet global
 * - piece : s'applique à une pièce spécifique
 * - zone : s'applique à une zone fonctionnelle (ex: zone humide)
 * - equipement : s'applique à un équipement précis
 * - circuit : s'applique à un circuit électrique
 */
final class Scope
{
    public const TYPE_CHANTIER = 'chantier';
    public const TYPE_PIECE = 'piece';
    public const TYPE_ZONE = 'zone';
    public const TYPE_EQUIPEMENT = 'equipement';
    public const TYPE_CIRCUIT = 'circuit';

    public function __construct(
        public readonly string $type,
        public readonly ?string $reference = null,
        public readonly ?string $label = null,
    ) {}

    public static function chantier(): self
    {
        return new self(self::TYPE_CHANTIER);
    }

    public static function piece(string $reference, string $label): self
    {
        return new self(self::TYPE_PIECE, $reference, $label);
    }

    public static function zone(string $reference, string $label): self
    {
        return new self(self::TYPE_ZONE, $reference, $label);
    }

    public static function equipement(string $reference, string $label): self
    {
        return new self(self::TYPE_EQUIPEMENT, $reference, $label);
    }

    public static function circuit(string $reference, string $label): self
    {
        return new self(self::TYPE_CIRCUIT, $reference, $label);
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'reference' => $this->reference,
            'label' => $this->label,
        ];
    }
}
