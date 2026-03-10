<?php

declare(strict_types=1);

namespace App\PipelineV2\WorkTreeBuilder;

use App\PipelineV2\WorkTreeBuilder\DTO\Activation;
use App\PipelineV2\WorkTreeBuilder\DTO\DelegationNormes;
use App\PipelineV2\WorkTreeBuilder\DTO\DomaineNonCouvert;
use App\PipelineV2\WorkTreeBuilder\DTO\ModificationHistorique;
use App\PipelineV2\WorkTreeBuilder\DTO\Multiplicateur;
use App\PipelineV2\WorkTreeBuilder\DTO\QuestionManquante;
use App\PipelineV2\WorkTreeBuilder\DTO\Scope;
use App\PipelineV2\WorkTreeBuilder\DTO\WorkTree;
use App\PipelineV2\WorkTreeBuilder\DTO\WorkTreeBuilderInput;
use App\PipelineV2\WorkTreeBuilder\DTO\WorkTreeSousTravail;
use App\PipelineV2\WorkTreeBuilder\DTO\WorkTreeTravail;
use App\PipelineV2\WorkTreeBuilder\Exception\UndefinedTravailException;
use App\PipelineV2\WorkTreeBuilder\Exception\UnknownChantierTypeException;

/**
 * Implémentation du WorkTreeBuilder.
 *
 * Transforme une intention de chantier en arbre de travail structuré.
 */
final class WorkTreeBuilder implements WorkTreeBuilderInterface
{
    private array $chantierTypes = [];
    private array $travauxDefinitions = [];
    private ConditionEvaluator $conditionEvaluator;

    public function __construct(
        string $chantierTypesPath,
        string $travauxDefinitionsPath,
        ?ConditionEvaluator $conditionEvaluator = null,
    ) {
        $this->loadConfiguration($chantierTypesPath, $travauxDefinitionsPath);
        $this->conditionEvaluator = $conditionEvaluator ?? new ConditionEvaluator();
    }

    /**
     * Charge les fichiers de configuration.
     */
    private function loadConfiguration(string $chantierTypesPath, string $travauxDefinitionsPath): void
    {
        $this->chantierTypes = require $chantierTypesPath;
        $this->travauxDefinitions = require $travauxDefinitionsPath;
    }

    // =========================================================================
    // Interface publique
    // =========================================================================

    public function build(WorkTreeBuilderInput $input): WorkTree
    {
        // Étape 1 : Validation du type de chantier
        $chantierConfig = $this->loadChantierConfig($input->typeChantier);

        // Étape 2 : Complétion du contexte
        $contexteComplet = $this->completeContexte($input, $chantierConfig);

        // Initialisation du WorkTree
        $workTree = WorkTree::create(
            $input->typeChantier,
            $chantierConfig['label'],
            $contexteComplet
        );

        // Étape 3 : Activation des travaux de base
        $workTree = $this->activateTravauxBase($workTree, $chantierConfig, $contexteComplet);

        // Étape 4 : Évaluation des travaux conditionnels
        $workTree = $this->evaluateTravauxConditionnels($workTree, $chantierConfig, $contexteComplet);

        // Étape 5 : Expansion des sous-travaux (fait dans les étapes 3 et 4)
        // Les sous-travaux sont déjà expandés lors de l'activation

        // Étape 6 : Vérification des domaines obligatoires
        $workTree = $this->checkDomainesObligatoires($workTree, $chantierConfig, $contexteComplet);

        // Étape 7 : Identification des questions manquantes
        $workTree = $this->identifyQuestionsManquantes($workTree, $chantierConfig, $contexteComplet);

        // Étape 8 : Finalisation des métadonnées
        $workTree = $this->finalizeWorkTree($workTree);

        return $workTree;
    }

    public function supportsChantierType(string $typeChantier): bool
    {
        return isset($this->chantierTypes[$typeChantier]);
    }

    public function getAvailableChantierTypes(): array
    {
        $types = [];
        foreach ($this->chantierTypes as $id => $config) {
            $types[$id] = $config['label'] ?? $id;
        }
        return $types;
    }

    // =========================================================================
    // Étape 1 : Chargement et validation
    // =========================================================================

    private function loadChantierConfig(string $typeChantier): array
    {
        if (!$this->supportsChantierType($typeChantier)) {
            throw new UnknownChantierTypeException(
                $typeChantier,
                array_keys($this->chantierTypes)
            );
        }

        return $this->chantierTypes[$typeChantier];
    }

    // =========================================================================
    // Étape 2 : Complétion du contexte
    // =========================================================================

    private function completeContexte(WorkTreeBuilderInput $input, array $chantierConfig): array
    {
        $contexte = $input->contexte;

        // Appliquer les valeurs par défaut des questions de qualification
        $questions = $chantierConfig['questions_qualification'] ?? [];

        foreach ($questions as $questionId => $questionConfig) {
            if (!isset($contexte[$questionId]) && isset($questionConfig['valeur_defaut'])) {
                $contexte[$questionId] = $questionConfig['valeur_defaut'];
            }
        }

        // Appliquer les réponses de qualification
        foreach ($input->reponsesQualification as $questionId => $valeur) {
            $contexte[$questionId] = $valeur;
        }

        return $contexte;
    }

    // =========================================================================
    // Étape 3 : Activation des travaux de base
    // =========================================================================

    private function activateTravauxBase(WorkTree $workTree, array $chantierConfig, array $contexte): WorkTree
    {
        $travauxBase = $chantierConfig['travaux_base'] ?? [];
        $ordreConfig = $chantierConfig['ordre_travaux'] ?? [];
        $ordreIndex = 10;

        // Structure: travaux_base[travailId] = ['sous_travaux' => [...], 'origine' => '...']
        foreach ($travauxBase as $travailId => $travailConfig) {
            // Charger la définition du travail depuis travaux_definitions.php (ou créer minimale)
            $travailDefinition = $this->loadTravailDefinitionOrCreate($travailId, $travailConfig);

            // Déterminer l'ordre
            $ordre = $ordreConfig[$travailId] ?? $ordreIndex;
            $ordreIndex += 10;

            // Créer le travail
            $travail = WorkTreeTravail::fromDefinition(
                id: $travailId,
                definition: $travailDefinition,
                ordre: $ordre,
                activation: Activation::base(),
                origine: $travailConfig['origine'] ?? 'chantier',
                scope: Scope::chantier(),
            );

            // Expander les sous-travaux (filtrer selon la liste dans chantier_types)
            $sousTravailIds = $travailConfig['sous_travaux'] ?? [];
            $travail = $this->expandSousTravaux($travail, $travailDefinition, $contexte, $sousTravailIds);

            // Ajouter au WorkTree avec historique
            $workTree = $workTree->addTravail($travail);
            $workTree = $workTree->addModification(
                ModificationHistorique::creation(
                    etape: ModificationHistorique::ETAPE_WORKTREE_BUILDER,
                    cible: "travail:{$travail->instanceId}",
                    valeur: ['id' => $travailId, 'label' => $travail->label],
                    raison: "Travail de base du chantier {$workTree->typeChantier}"
                )
            );
        }

        return $workTree;
    }

    // =========================================================================
    // Étape 4 : Évaluation des travaux conditionnels
    // =========================================================================

    private function evaluateTravauxConditionnels(WorkTree $workTree, array $chantierConfig, array $contexte): WorkTree
    {
        $travauxConditionnels = $chantierConfig['travaux_conditionnels'] ?? [];
        $ordreConfig = $chantierConfig['ordre_travaux'] ?? [];
        $ordreIndex = 100;

        // Structure: travaux_conditionnels[travailId] = ['condition' => [...], 'sous_travaux' => [...], 'origine' => '...']
        foreach ($travauxConditionnels as $travailId => $travailConfig) {
            $condition = $travailConfig['condition'] ?? null;

            if ($condition === null) {
                continue;
            }

            // Vérifier si le travail n'est pas déjà activé
            if ($workTree->hasTravail($travailId)) {
                continue;
            }

            // Évaluer la condition
            $result = $this->conditionEvaluator->evaluate($condition, $contexte);

            if ($result === 'delegate') {
                // Créer une délégation vers NormesEngine
                $workTree = $this->createDelegationForConditionalTravail($workTree, $travailId, $travailConfig);
                continue;
            }

            if ($result !== true) {
                continue;
            }

            // La condition est vraie, activer le travail
            // Essayer de charger depuis travaux_definitions, sinon créer dynamiquement
            $travailDefinition = $this->loadTravailDefinitionOrCreate($travailId, $travailConfig);
            $ordre = $ordreConfig[$travailId] ?? $ordreIndex;
            $ordreIndex += 10;

            $travail = WorkTreeTravail::fromDefinition(
                id: $travailId,
                definition: $travailDefinition,
                ordre: $ordre,
                activation: Activation::condition(
                    $this->conditionEvaluator->evaluateWithDetails($condition, $contexte)
                ),
                origine: $travailConfig['origine'] ?? 'chantier',
                scope: Scope::chantier(),
            );

            // Expander les sous-travaux
            $sousTravailIds = $travailConfig['sous_travaux'] ?? [];
            $travail = $this->expandSousTravaux($travail, $travailDefinition, $contexte, $sousTravailIds);

            // Ajouter au WorkTree
            $workTree = $workTree->addTravail($travail);
            $workTree = $workTree->addModification(
                ModificationHistorique::creation(
                    etape: ModificationHistorique::ETAPE_WORKTREE_BUILDER,
                    cible: "travail:{$travail->instanceId}",
                    valeur: ['id' => $travailId, 'condition' => $condition],
                    raison: "Condition remplie"
                )
            );
        }

        return $workTree;
    }

    // =========================================================================
    // Étape 5 : Expansion des sous-travaux
    // =========================================================================

    /**
     * Expande les sous-travaux d'un travail.
     *
     * @param array $filterIds Si non vide, ne garder que ces sous-travaux
     */
    private function expandSousTravaux(
        WorkTreeTravail $travail,
        array $travailDefinition,
        array $contexte,
        array $filterIds = [],
    ): WorkTreeTravail {
        $sousTravaux = $travailDefinition['sous_travaux'] ?? [];
        $ordreConfig = $travailDefinition['ordre_sous_travaux'] ?? [];
        $ordreIndex = 10;

        foreach ($sousTravaux as $sousTravailId => $sousTravailConfig) {
            // Si des filtres sont spécifiés, ne garder que ceux listés
            if (!empty($filterIds) && !in_array($sousTravailId, $filterIds, true)) {
                continue;
            }

            $ordre = $ordreConfig[$sousTravailId] ?? $ordreIndex;
            $ordreIndex += 10;

            // Calculer la quantité
            $quantiteResult = $this->calculateQuantite($sousTravailConfig, $contexte);

            // Créer le(s) sous-travail(aux)
            $instances = $this->createSousTravailInstances(
                $sousTravailId,
                $sousTravailConfig,
                $ordre,
                $quantiteResult,
                $contexte
            );

            foreach ($instances as $instance) {
                $travail = $travail->addSousTravail($instance);
            }
        }

        // Si des sous-travaux sont spécifiés mais pas définis dans travaux_definitions,
        // créer des sous-travaux minimaux
        if (!empty($filterIds)) {
            $existingIds = array_keys($sousTravaux);
            foreach ($filterIds as $sousTravailId) {
                if (!in_array($sousTravailId, $existingIds, true)) {
                    $travail = $this->addMinimalSousTravail($travail, $sousTravailId, $ordreIndex, $contexte);
                    $ordreIndex += 10;
                }
            }
        }

        return $travail;
    }

    /**
     * Ajoute un sous-travail minimal quand il n'est pas défini dans travaux_definitions.
     */
    private function addMinimalSousTravail(
        WorkTreeTravail $travail,
        string $sousTravailId,
        int $ordre,
        array $contexte,
    ): WorkTreeTravail {
        $config = [
            'label' => $this->humanizeId($sousTravailId),
            'quantite' => ['type' => 'fixed', 'valeur' => 1],
        ];

        $quantiteResult = $this->calculateQuantite($config, $contexte);

        $instance = WorkTreeSousTravail::fromDefinition(
            id: $sousTravailId,
            definition: $config,
            scope: Scope::chantier(),
            ordre: $ordre,
            quantiteBrute: $quantiteResult['quantite_brute'],
            multiplicateur: null,
            quantiteFinale: $quantiteResult['quantite_finale'],
            delegueNormes: false,
            origine: 'chantier',
        );

        return $travail->addSousTravail($instance);
    }

    /**
     * Convertit un ID en label lisible.
     */
    private function humanizeId(string $id): string
    {
        return ucfirst(str_replace('_', ' ', $id));
    }

    /**
     * Calcule la quantité pour un sous-travail.
     */
    private function calculateQuantite(array $config, array $contexte): array
    {
        $quantiteConfig = $config['quantite'] ?? ['type' => 'fixed', 'valeur' => 1];

        return match ($quantiteConfig['type']) {
            'fixed' => [
                'quantite_brute' => $quantiteConfig['valeur'],
                'multiplicateur' => null,
                'quantite_finale' => $quantiteConfig['valeur'],
                'delegue_normes' => false,
            ],
            'field' => $this->calculateFromField($quantiteConfig, $contexte),
            'formula' => $this->calculateFromFormula($quantiteConfig, $contexte),
            'delegate_normes_engine' => [
                'quantite_brute' => null,
                'multiplicateur' => null,
                'quantite_finale' => null,
                'delegue_normes' => true,
            ],
            default => [
                'quantite_brute' => 1,
                'multiplicateur' => null,
                'quantite_finale' => 1,
                'delegue_normes' => false,
            ],
        };
    }

    private function calculateFromField(array $config, array $contexte): array
    {
        $champ = $config['champ'] ?? null;
        $valeur = $champ !== null ? ($contexte[$champ] ?? null) : null;

        return [
            'quantite_brute' => $valeur,
            'multiplicateur' => null,
            'quantite_finale' => $valeur,
            'delegue_normes' => false,
        ];
    }

    private function calculateFromFormula(array $config, array $contexte): array
    {
        // TODO: Implémenter l'évaluation de formules
        // Pour l'instant, retourner null et marquer comme délégation
        return [
            'quantite_brute' => null,
            'multiplicateur' => null,
            'quantite_finale' => null,
            'delegue_normes' => true,
        ];
    }

    /**
     * Crée les instances de sous-travail (une par scope si nécessaire).
     *
     * @return WorkTreeSousTravail[]
     */
    private function createSousTravailInstances(
        string $sousTravailId,
        array $config,
        int $ordre,
        array $quantiteResult,
        array $contexte,
    ): array {
        $instances = [];
        $scopeConfig = $config['scope'] ?? 'chantier';

        if ($scopeConfig === 'par_piece' && isset($contexte['pieces'])) {
            // Créer une instance par pièce
            foreach ($contexte['pieces'] as $piece) {
                $scope = Scope::piece($piece['id'], $piece['label']);

                // Recalculer la quantité pour cette pièce si nécessaire
                $pieceContexte = array_merge($contexte, ['piece_courante' => $piece]);
                $pieceQuantite = $this->calculateQuantiteForScope($config, $pieceContexte);

                $instances[] = WorkTreeSousTravail::fromDefinition(
                    id: $sousTravailId,
                    definition: $config,
                    scope: $scope,
                    ordre: $ordre,
                    quantiteBrute: $pieceQuantite['quantite_brute'],
                    multiplicateur: $pieceQuantite['multiplicateur'],
                    quantiteFinale: $pieceQuantite['quantite_finale'],
                    delegueNormes: $pieceQuantite['delegue_normes'],
                    origine: $config['origine'] ?? 'chantier',
                );
            }
        } else {
            // Instance unique scope chantier
            $instances[] = WorkTreeSousTravail::fromDefinition(
                id: $sousTravailId,
                definition: $config,
                scope: Scope::chantier(),
                ordre: $ordre,
                quantiteBrute: $quantiteResult['quantite_brute'],
                multiplicateur: $quantiteResult['multiplicateur'] !== null
                    ? new Multiplicateur(
                        $quantiteResult['multiplicateur']['type'],
                        $quantiteResult['multiplicateur']['valeur'],
                        $quantiteResult['multiplicateur']['source']
                    )
                    : null,
                quantiteFinale: $quantiteResult['quantite_finale'],
                delegueNormes: $quantiteResult['delegue_normes'],
                origine: $config['origine'] ?? 'chantier',
            );
        }

        return $instances;
    }

    private function calculateQuantiteForScope(array $config, array $contexte): array
    {
        // Simplifié : utiliser le calcul standard
        return $this->calculateQuantite($config, $contexte);
    }

    // =========================================================================
    // Étape 6 : Vérification des domaines obligatoires
    // =========================================================================

    private function checkDomainesObligatoires(WorkTree $workTree, array $chantierConfig, array $contexte): WorkTree
    {
        $domaines = $chantierConfig['domaines_obligatoires_a_couvrir'] ?? [];

        foreach ($domaines as $domaineId => $domaineConfig) {
            $verification = $domaineConfig['verification'] ?? null;
            $actionSiAbsent = $domaineConfig['action_si_absent'] ?? null;

            if ($verification === null) {
                continue;
            }

            // Évaluer la vérification
            $result = $this->evaluateDomaineVerification($verification, $workTree, $contexte);

            if ($result === true) {
                // Le domaine est couvert
                continue;
            }

            if ($result === 'delegate') {
                // Créer une délégation vers NormesEngine
                $workTree = $workTree->addDelegationNormes(new DelegationNormes(
                    type: 'verification_domaine',
                    travailInstanceId: $domaineId,
                    sousTravailInstanceId: null,
                    contexteRequis: [
                        'domaine' => $domaineId,
                        'verification' => $verification,
                        'action_si_absent' => $actionSiAbsent,
                    ],
                ));
                continue;
            }

            // Le domaine n'est pas couvert, appliquer l'action
            $workTree = $this->applyDomaineAction($workTree, $domaineConfig, $actionSiAbsent, $contexte);
        }

        return $workTree;
    }

    private function evaluateDomaineVerification(array $verification, WorkTree $workTree, array $contexte): bool|string
    {
        $type = $verification['type'] ?? null;

        return match ($type) {
            'travail_present', 'has_travail' => $workTree->hasTravail($verification['travail']),
            'has_sous_travail' => $this->workTreeHasSousTravail($workTree, $verification['sous_travail']),
            'has_besoin', 'has_besoin_in_list' => true, // TODO: vérifier les besoins techniques
            'condition' => $this->conditionEvaluator->evaluate($verification, $contexte) === true,
            'conditional_has_travail' => $this->evaluateConditionalHasTravail($verification, $workTree, $contexte),
            'delegate_normes_engine' => 'delegate', // Délégué au NormesEngine
            'or' => $this->evaluateOrVerification($verification, $workTree, $contexte),
            default => true, // Par défaut, on considère le domaine couvert
        };
    }

    private function workTreeHasSousTravail(WorkTree $workTree, string $sousTravailId): bool
    {
        foreach ($workTree->travaux as $travail) {
            foreach ($travail->sousTravaux as $sousTravail) {
                if ($sousTravail->id === $sousTravailId) {
                    return true;
                }
            }
        }
        return false;
    }

    private function evaluateConditionalHasTravail(array $verification, WorkTree $workTree, array $contexte): bool
    {
        $condition = $verification['condition'] ?? null;
        if ($condition === null) {
            return true;
        }

        // Si la condition n'est pas remplie, le domaine n'est pas requis (donc "couvert")
        $conditionResult = $this->conditionEvaluator->evaluate($condition, $contexte);
        if ($conditionResult !== true) {
            return true;
        }

        // La condition est remplie, vérifier si le travail est présent
        return $workTree->hasTravail($verification['travail']);
    }

    private function evaluateOrVerification(array $verification, WorkTree $workTree, array $contexte): bool
    {
        $conditions = $verification['conditions'] ?? [];
        foreach ($conditions as $subVerification) {
            $result = $this->evaluateDomaineVerification($subVerification, $workTree, $contexte);
            if ($result === true) {
                return true;
            }
        }
        return false;
    }

    private function applyDomaineAction(WorkTree $workTree, array $domaineConfig, string|array|null $action, array $contexte): WorkTree
    {
        if ($action === null) {
            return $workTree;
        }

        // Normaliser : si c'est une string, on la traite comme un type d'action simple
        $actionType = is_string($action) ? $action : ($action['type'] ?? null);
        $actionConfig = is_array($action) ? $action : [];

        return match ($actionType) {
            'ajouter_travail' => $this->addTravailForDomaine($workTree, $actionConfig, $domaineConfig, $contexte),
            'ajouter_sous_travail' => $workTree, // TODO: implémenter
            'completer_quantites' => $workTree, // Délégué au NormesEngine
            'augmenter_taille_coffret' => $workTree, // Délégué au NormesEngine
            'ajuster_section' => $workTree, // Délégué au NormesEngine
            'ajouter_besoin' => $workTree, // TODO: implémenter
            'question_audit' => $workTree, // Pas d'action immédiate
            'alerte_devis' => $workTree->addDomaineNonCouvert(
                DomaineNonCouvert::alerte(
                    $domaineConfig['domaine'] ?? $domaineConfig['label'] ?? 'inconnu',
                    $domaineConfig['verification'] ?? [],
                    $actionConfig['message'] ?? 'Domaine non couvert'
                )
            ),
            'bloquant' => $workTree->addDomaineNonCouvert(
                DomaineNonCouvert::bloquant(
                    $domaineConfig['domaine'] ?? $domaineConfig['label'] ?? 'inconnu',
                    $domaineConfig['verification'] ?? [],
                    $actionConfig['message'] ?? 'Domaine obligatoire non couvert'
                )
            ),
            default => $workTree,
        };
    }

    private function addTravailForDomaine(WorkTree $workTree, array $action, array $domaineConfig, array $contexte): WorkTree
    {
        $travailId = $action['travail'] ?? null;
        if ($travailId === null || $workTree->hasTravail($travailId)) {
            return $workTree;
        }

        $travailDefinition = $this->loadTravailDefinition($travailId, $workTree->typeChantier);

        $travail = WorkTreeTravail::fromDefinition(
            id: $travailId,
            definition: $travailDefinition,
            ordre: 100,
            activation: Activation::domaineObligatoire($domaineConfig['domaine'] ?? $travailId),
            origine: 'chantier',
            scope: Scope::chantier(),
        );

        $travail = $this->expandSousTravaux($travail, $travailDefinition, $contexte);

        return $workTree->addTravail($travail);
    }

    // =========================================================================
    // Étape 7 : Identification des questions manquantes
    // =========================================================================

    private function identifyQuestionsManquantes(WorkTree $workTree, array $chantierConfig, array $contexte): WorkTree
    {
        $questions = $chantierConfig['questions_qualification'] ?? [];

        foreach ($questions as $questionId => $questionConfig) {
            // Vérifier si la question a une réponse dans le contexte
            if (isset($contexte[$questionId]) && $contexte[$questionId] !== null) {
                continue;
            }

            // Vérifier si une valeur par défaut existe
            if (isset($questionConfig['valeur_defaut'])) {
                continue;
            }

            // La question est manquante
            $workTree = $workTree->addQuestionManquante(
                QuestionManquante::fromConfig($questionId, $questionConfig)
            );
        }

        return $workTree;
    }

    // =========================================================================
    // Étape 8 : Finalisation
    // =========================================================================

    private function finalizeWorkTree(WorkTree $workTree): WorkTree
    {
        // Mettre à jour les métadonnées
        $meta = $workTree->meta;

        // Vérifier si le contexte est complet
        $contexteComplet = $workTree->isContexteComplet();

        $newMeta = new \App\PipelineV2\WorkTreeBuilder\DTO\WorkTreeMeta(
            versionConfig: $meta->versionConfig,
            timestamp: new \DateTimeImmutable(),
            contexteComplet: $contexteComplet,
            etapeCourante: $meta->etapeCourante,
        );

        return $workTree->withMeta($newMeta);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function loadTravailDefinition(string $travailId, string $typeChantier): array
    {
        if (!isset($this->travauxDefinitions[$travailId])) {
            throw new UndefinedTravailException($travailId, $typeChantier);
        }

        return $this->travauxDefinitions[$travailId];
    }

    /**
     * Charge la définition du travail depuis travaux_definitions, ou crée une définition minimale.
     */
    private function loadTravailDefinitionOrCreate(string $travailId, array $travailConfig): array
    {
        if (isset($this->travauxDefinitions[$travailId])) {
            return $this->travauxDefinitions[$travailId];
        }

        // Créer une définition minimale à partir de la config du chantier
        $sousTravaux = [];
        foreach ($travailConfig['sous_travaux'] ?? [] as $sousTravailId) {
            $sousTravaux[$sousTravailId] = [
                'label' => $this->humanizeId($sousTravailId),
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ];
        }

        return [
            'id' => $travailId,
            'label' => $this->humanizeId($travailId),
            'sous_travaux' => $sousTravaux,
        ];
    }

    private function createDelegationForConditionalTravail(WorkTree $workTree, string $travailId, array $travailConfig): WorkTree
    {
        // Créer une délégation vers NormesEngine pour ce travail conditionnel
        $delegation = new DelegationNormes(
            type: 'activation_conditionnelle',
            travailInstanceId: $travailId,
            sousTravailInstanceId: null,
            contexteRequis: [
                'condition' => $travailConfig['condition'],
                'travail' => $travailId,
            ],
        );

        return $workTree->addDelegationNormes($delegation);
    }
}
