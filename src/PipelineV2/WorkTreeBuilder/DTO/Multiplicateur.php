<?php

declare(strict_types=1);

namespace App\PipelineV2\WorkTreeBuilder\DTO;

/**
 * Représente un multiplicateur appliqué à une quantité.
 */
final class Multiplicateur
{
    public const TYPE_PAR_PIECE = 'par_piece';
    public const TYPE_PAR_PIECE_HUMIDE = 'par_piece_humide';
    public const TYPE_PAR_CIRCUIT = 'par_circuit';
    public const TYPE_FIXE = 'fixe';

    public function __construct(
        public readonly string $type,
        public readonly int|float $valeur,
        public readonly string $source,
    ) {}

    public static function parPiece(int $nbPieces): self
    {
        return new self(self::TYPE_PAR_PIECE, $nbPieces, 'nb_pieces');
    }

    public static function parPieceHumide(int $nbPiecesHumides): self
    {
        return new self(self::TYPE_PAR_PIECE_HUMIDE, $nbPiecesHumides, 'nb_pieces_humides');
    }

    public static function fixe(int|float $valeur): self
    {
        return new self(self::TYPE_FIXE, $valeur, 'fixe');
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'valeur' => $this->valeur,
            'source' => $this->source,
        ];
    }
}
