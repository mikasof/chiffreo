<?php

declare(strict_types=1);

namespace App\PipelineV2\WorkTreeBuilder\DTO;

/**
 * Entrée du WorkTreeBuilder.
 * Contient le type de chantier, le contexte extrait par l'IA, et les réponses de qualification.
 */
final class WorkTreeBuilderInput
{
    public function __construct(
        public readonly string $typeChantier,
        public readonly array $contexte,
        public readonly array $reponsesQualification = [],
    ) {}

    /**
     * Crée une entrée depuis les données brutes de l'IA.
     */
    public static function fromAiExtraction(array $extraction): self
    {
        return new self(
            typeChantier: $extraction['type_chantier'] ?? '',
            contexte: $extraction['contexte'] ?? [],
            reponsesQualification: $extraction['reponses'] ?? [],
        );
    }

    /**
     * Retourne la valeur d'un champ du contexte, ou null si absent.
     */
    public function getContexte(string $champ): mixed
    {
        return $this->contexte[$champ] ?? null;
    }

    /**
     * Retourne la valeur d'une réponse de qualification, ou null si absente.
     */
    public function getReponse(string $questionId): mixed
    {
        return $this->reponsesQualification[$questionId] ?? null;
    }

    /**
     * Vérifie si un champ du contexte est présent et non null.
     */
    public function hasContexte(string $champ): bool
    {
        return isset($this->contexte[$champ]) && $this->contexte[$champ] !== null;
    }

    /**
     * Vérifie si une réponse de qualification est présente.
     */
    public function hasReponse(string $questionId): bool
    {
        return isset($this->reponsesQualification[$questionId]);
    }

    /**
     * Retourne une nouvelle instance avec un champ contexte ajouté/modifié.
     */
    public function withContexte(string $champ, mixed $valeur): self
    {
        $contexte = $this->contexte;
        $contexte[$champ] = $valeur;

        return new self(
            typeChantier: $this->typeChantier,
            contexte: $contexte,
            reponsesQualification: $this->reponsesQualification,
        );
    }

    /**
     * Fusionne le contexte avec des valeurs par défaut.
     */
    public function withDefaults(array $defaults): self
    {
        $contexte = array_merge($defaults, $this->contexte);

        return new self(
            typeChantier: $this->typeChantier,
            contexte: $contexte,
            reponsesQualification: $this->reponsesQualification,
        );
    }

    public function toArray(): array
    {
        return [
            'type_chantier' => $this->typeChantier,
            'contexte' => $this->contexte,
            'reponses_qualification' => $this->reponsesQualification,
        ];
    }
}
