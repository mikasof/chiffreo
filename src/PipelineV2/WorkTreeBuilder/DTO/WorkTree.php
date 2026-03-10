<?php

declare(strict_types=1);

namespace App\PipelineV2\WorkTreeBuilder\DTO;

/**
 * Arbre de travail complet.
 * Produit par le WorkTreeBuilder, enrichi par le NormesEngine.
 */
final class WorkTree
{
    public const VERSION = '2.0';

    /**
     * @param WorkTreeTravail[] $travaux
     * @param DelegationNormes[] $delegationsNormes
     * @param DomaineNonCouvert[] $domainesNonCouverts
     * @param QuestionManquante[] $questionsManquantes
     * @param ModificationHistorique[] $historiqueModifications
     */
    public function __construct(
        public readonly string $typeChantier,
        public readonly string $labelChantier,
        public readonly array $contexteComplet,
        public readonly array $travaux,
        public readonly array $delegationsNormes,
        public readonly array $domainesNonCouverts,
        public readonly array $questionsManquantes,
        public readonly array $historiqueModifications,
        public readonly WorkTreeMeta $meta,
    ) {}

    /**
     * Crée un WorkTree vide pour un type de chantier.
     */
    public static function create(string $typeChantier, string $label, array $contexte): self
    {
        $questionsObligatoires = array_filter(
            [],
            fn($q) => $q->obligatoirePourChiffrage
        );

        return new self(
            typeChantier: $typeChantier,
            labelChantier: $label,
            contexteComplet: $contexte,
            travaux: [],
            delegationsNormes: [],
            domainesNonCouverts: [],
            questionsManquantes: [],
            historiqueModifications: [],
            meta: WorkTreeMeta::initial(contexteComplet: true),
        );
    }

    // -------------------------------------------------------------------------
    // Méthodes d'ajout (immutables)
    // -------------------------------------------------------------------------

    /**
     * Ajoute un travail au WorkTree.
     */
    public function addTravail(WorkTreeTravail $travail): self
    {
        $travaux = $this->travaux;
        $travaux[] = $travail;

        // Trier par ordre
        usort($travaux, fn($a, $b) => $a->ordre <=> $b->ordre);

        return new self(
            typeChantier: $this->typeChantier,
            labelChantier: $this->labelChantier,
            contexteComplet: $this->contexteComplet,
            travaux: $travaux,
            delegationsNormes: $this->delegationsNormes,
            domainesNonCouverts: $this->domainesNonCouverts,
            questionsManquantes: $this->questionsManquantes,
            historiqueModifications: $this->historiqueModifications,
            meta: $this->meta,
        );
    }

    /**
     * Ajoute une délégation vers le NormesEngine.
     */
    public function addDelegationNormes(DelegationNormes $delegation): self
    {
        $delegations = $this->delegationsNormes;
        $delegations[] = $delegation;

        return new self(
            typeChantier: $this->typeChantier,
            labelChantier: $this->labelChantier,
            contexteComplet: $this->contexteComplet,
            travaux: $this->travaux,
            delegationsNormes: $delegations,
            domainesNonCouverts: $this->domainesNonCouverts,
            questionsManquantes: $this->questionsManquantes,
            historiqueModifications: $this->historiqueModifications,
            meta: $this->meta,
        );
    }

    /**
     * Ajoute un domaine non couvert.
     */
    public function addDomaineNonCouvert(DomaineNonCouvert $domaine): self
    {
        $domaines = $this->domainesNonCouverts;
        $domaines[] = $domaine;

        return new self(
            typeChantier: $this->typeChantier,
            labelChantier: $this->labelChantier,
            contexteComplet: $this->contexteComplet,
            travaux: $this->travaux,
            delegationsNormes: $this->delegationsNormes,
            domainesNonCouverts: $domaines,
            questionsManquantes: $this->questionsManquantes,
            historiqueModifications: $this->historiqueModifications,
            meta: $this->meta,
        );
    }

    /**
     * Ajoute une question manquante.
     */
    public function addQuestionManquante(QuestionManquante $question): self
    {
        $questions = $this->questionsManquantes;
        $questions[] = $question;

        // Trier par priorité
        usort($questions, fn($a, $b) => $a->priorite <=> $b->priorite);

        return new self(
            typeChantier: $this->typeChantier,
            labelChantier: $this->labelChantier,
            contexteComplet: $this->contexteComplet,
            travaux: $this->travaux,
            delegationsNormes: $this->delegationsNormes,
            domainesNonCouverts: $this->domainesNonCouverts,
            questionsManquantes: $questions,
            historiqueModifications: $this->historiqueModifications,
            meta: $this->meta,
        );
    }

    /**
     * Ajoute une entrée à l'historique des modifications.
     */
    public function addModification(ModificationHistorique $modification): self
    {
        $historique = $this->historiqueModifications;
        $historique[] = $modification;

        return new self(
            typeChantier: $this->typeChantier,
            labelChantier: $this->labelChantier,
            contexteComplet: $this->contexteComplet,
            travaux: $this->travaux,
            delegationsNormes: $this->delegationsNormes,
            domainesNonCouverts: $this->domainesNonCouverts,
            questionsManquantes: $this->questionsManquantes,
            historiqueModifications: $historique,
            meta: $this->meta,
        );
    }

    /**
     * Met à jour les métadonnées.
     */
    public function withMeta(WorkTreeMeta $meta): self
    {
        return new self(
            typeChantier: $this->typeChantier,
            labelChantier: $this->labelChantier,
            contexteComplet: $this->contexteComplet,
            travaux: $this->travaux,
            delegationsNormes: $this->delegationsNormes,
            domainesNonCouverts: $this->domainesNonCouverts,
            questionsManquantes: $this->questionsManquantes,
            historiqueModifications: $this->historiqueModifications,
            meta: $meta,
        );
    }

    /**
     * Supprime toutes les délégations (après traitement par NormesEngine).
     */
    public function clearDelegationsNormes(): self
    {
        return new self(
            typeChantier: $this->typeChantier,
            labelChantier: $this->labelChantier,
            contexteComplet: $this->contexteComplet,
            travaux: $this->travaux,
            delegationsNormes: [],
            domainesNonCouverts: $this->domainesNonCouverts,
            questionsManquantes: $this->questionsManquantes,
            historiqueModifications: $this->historiqueModifications,
            meta: $this->meta,
        );
    }

    // -------------------------------------------------------------------------
    // Méthodes de recherche
    // -------------------------------------------------------------------------

    /**
     * Trouve un travail par son ID.
     */
    public function findTravailById(string $id): ?WorkTreeTravail
    {
        foreach ($this->travaux as $travail) {
            if ($travail->id === $id) {
                return $travail;
            }
        }
        return null;
    }

    /**
     * Trouve un travail par son instance ID.
     */
    public function findTravailByInstanceId(string $instanceId): ?WorkTreeTravail
    {
        foreach ($this->travaux as $travail) {
            if ($travail->instanceId === $instanceId) {
                return $travail;
            }
        }
        return null;
    }

    /**
     * Vérifie si un travail est activé.
     */
    public function hasTravail(string $id): bool
    {
        return $this->findTravailById($id) !== null;
    }

    /**
     * Vérifie si le contexte est complet (pas de questions obligatoires manquantes).
     */
    public function isContexteComplet(): bool
    {
        foreach ($this->questionsManquantes as $question) {
            if ($question->obligatoirePourChiffrage) {
                return false;
            }
        }
        return true;
    }

    /**
     * Vérifie si le WorkTree a des domaines bloquants non couverts.
     */
    public function hasDomainesBloquants(): bool
    {
        foreach ($this->domainesNonCouverts as $domaine) {
            if ($domaine->bloquant) {
                return true;
            }
        }
        return false;
    }

    // -------------------------------------------------------------------------
    // Sérialisation
    // -------------------------------------------------------------------------

    public function toArray(): array
    {
        return [
            'type_chantier' => $this->typeChantier,
            'label_chantier' => $this->labelChantier,
            'version' => self::VERSION,
            'contexte_complet' => $this->contexteComplet,
            'travaux' => array_map(fn($t) => $t->toArray(), $this->travaux),
            'delegations_normes' => array_map(fn($d) => $d->toArray(), $this->delegationsNormes),
            'domaines_non_couverts' => array_map(fn($d) => $d->toArray(), $this->domainesNonCouverts),
            'questions_manquantes' => array_map(fn($q) => $q->toArray(), $this->questionsManquantes),
            'historique_modifications' => array_map(fn($h) => $h->toArray(), $this->historiqueModifications),
            'meta' => $this->meta->toArray(),
        ];
    }
}
