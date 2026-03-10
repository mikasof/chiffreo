<?php

declare(strict_types=1);

namespace App\PipelineV2\NormesEngine\DTO;

/**
 * Représente une alerte non bloquante.
 *
 * L'alerte signale un point d'attention mais n'empêche pas la génération du devis.
 */
final readonly class Alerte
{
    public const SEVERITE_INFO = 'info';
    public const SEVERITE_ATTENTION = 'attention';
    public const SEVERITE_IMPORTANT = 'important';

    /**
     * @param string $code Code unique de l'alerte
     * @param string $message Message descriptif
     * @param string $severite Niveau de sévérité
     * @param string|null $regleId ID de la règle ayant généré l'alerte
     * @param string|null $travailId ID du travail concerné
     * @param string|null $sousTravailId ID du sous-travail concerné
     * @param array $contexte Contexte additionnel
     */
    public function __construct(
        public string $code,
        public string $message,
        public string $severite = self::SEVERITE_ATTENTION,
        public ?string $regleId = null,
        public ?string $travailId = null,
        public ?string $sousTravailId = null,
        public array $contexte = [],
    ) {}

    /**
     * Crée une alerte info.
     */
    public static function info(
        string $code,
        string $message,
        ?string $regleId = null,
        array $contexte = [],
    ): self {
        return new self(
            code: $code,
            message: $message,
            severite: self::SEVERITE_INFO,
            regleId: $regleId,
            contexte: $contexte,
        );
    }

    /**
     * Crée une alerte attention.
     */
    public static function attention(
        string $code,
        string $message,
        ?string $regleId = null,
        ?string $travailId = null,
        array $contexte = [],
    ): self {
        return new self(
            code: $code,
            message: $message,
            severite: self::SEVERITE_ATTENTION,
            regleId: $regleId,
            travailId: $travailId,
            contexte: $contexte,
        );
    }

    /**
     * Crée une alerte importante.
     */
    public static function important(
        string $code,
        string $message,
        ?string $regleId = null,
        ?string $travailId = null,
        array $contexte = [],
    ): self {
        return new self(
            code: $code,
            message: $message,
            severite: self::SEVERITE_IMPORTANT,
            regleId: $regleId,
            travailId: $travailId,
            contexte: $contexte,
        );
    }

    /**
     * Export pour affichage.
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'severite' => $this->severite,
            'regle_id' => $this->regleId,
            'travail_id' => $this->travailId,
            'sous_travail_id' => $this->sousTravailId,
            'contexte' => $this->contexte,
        ];
    }
}
