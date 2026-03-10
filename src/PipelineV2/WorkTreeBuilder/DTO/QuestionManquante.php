<?php

declare(strict_types=1);

namespace App\PipelineV2\WorkTreeBuilder\DTO;

/**
 * Représente une question de qualification manquante pour compléter le contexte.
 */
final class QuestionManquante
{
    public function __construct(
        public readonly string $id,
        public readonly string $question,
        public readonly string $type,
        public readonly int $priorite,
        public readonly bool $obligatoirePourChiffrage,
        public readonly mixed $valeurDefaut,
        public readonly string $origine,
    ) {}

    public static function fromConfig(string $id, array $config): self
    {
        return new self(
            id: $id,
            question: $config['question'] ?? $id,
            type: $config['type'] ?? 'string',
            priorite: $config['priorite'] ?? 5,
            obligatoirePourChiffrage: $config['obligatoire_pour_chiffrage'] ?? false,
            valeurDefaut: $config['valeur_defaut'] ?? null,
            origine: $config['origine'] ?? 'chantier',
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'type' => $this->type,
            'priorite' => $this->priorite,
            'obligatoire_pour_chiffrage' => $this->obligatoirePourChiffrage,
            'valeur_defaut' => $this->valeurDefaut,
            'origine' => $this->origine,
        ];
    }
}
