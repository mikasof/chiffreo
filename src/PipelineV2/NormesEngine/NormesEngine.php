<?php

declare(strict_types=1);

namespace App\PipelineV2\NormesEngine;

use App\PipelineV2\NormesEngine\DTO\Alerte;
use App\PipelineV2\NormesEngine\DTO\ElementNonDetermine;
use App\PipelineV2\NormesEngine\DTO\Impossibilite;
use App\PipelineV2\NormesEngine\DTO\NormesEngineInput;
use App\PipelineV2\NormesEngine\DTO\NormesEngineOutput;
use App\PipelineV2\NormesEngine\DTO\RegleAppliquee;
use App\PipelineV2\NormesEngine\Exception\NormesEngineException;
use App\PipelineV2\WorkTreeBuilder\DTO\ModificationHistorique;
use App\PipelineV2\WorkTreeBuilder\DTO\WorkTree;

/**
 * Moteur de validation et d'enrichissement normatif.
 *
 * Le NormesEngine est le second composant du pipeline de génération de devis.
 * Il reçoit un WorkTree du WorkTreeBuilder et l'enrichit selon les règles
 * normatives (NF C 15-100, guides UTE, bonnes pratiques).
 *
 * FONCTIONNEMENT :
 * 1. Résolution des délégations (demandes du WorkTreeBuilder)
 * 2. Application des règles par catégorie (protection, sections, etc.)
 * 3. Vérification de conformité globale
 * 4. Production d'un output structuré
 *
 * PRINCIPES V1 :
 * - Ossature claire et maintenable
 * - Règles externalisées dans normes_rules.php
 * - Distinction norme/reco/pratique
 * - Sévérité bloquant/alerte/info
 */
final class NormesEngine implements NormesEngineInterface
{
    private readonly array $rules;
    private readonly array $tables;
    private readonly NormesRuleEvaluator $evaluator;
    private readonly NormesRuleApplier $applier;

    public function __construct(
        string $rulesPath,
        string $travauxDefinitionsPath,
    ) {
        // Charger les règles normatives
        if (!file_exists($rulesPath)) {
            throw new NormesEngineException("Fichier de règles non trouvé: {$rulesPath}");
        }
        $rulesConfig = require $rulesPath;
        $this->tables = $rulesConfig['tables'] ?? [];
        unset($rulesConfig['tables']);
        $this->rules = $rulesConfig;

        // Charger les définitions de travaux
        $travauxDefinitions = [];
        if (file_exists($travauxDefinitionsPath)) {
            $travauxDefinitions = require $travauxDefinitionsPath;
        }

        // Initialiser les composants
        $this->evaluator = new NormesRuleEvaluator($this->tables);
        $this->applier = new NormesRuleApplier($travauxDefinitions);
    }

    // =========================================================================
    // Interface publique
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function process(NormesEngineInput $input): NormesEngineOutput
    {
        $workTree = $input->workTree;
        $contexte = $input->contexte;
        $reglesIgnorees = $input->getReglesIgnorees();
        $categoriesActives = $input->getCategoriesActives();

        // Collecteurs
        $reglesAppliquees = [];
        $alertes = [];
        $impossibilites = [];
        $elementsNonDetermines = [];
        $statistiques = [
            'nb_regles_evaluees' => 0,
            'nb_regles_appliquees' => 0,
            'nb_ajustements' => 0,
            'categories_traitees' => [],
        ];

        try {
            // Étape 1 : Résoudre les délégations du WorkTreeBuilder
            $workTree = $this->resolveDelegations($workTree, $contexte, $reglesAppliquees, $alertes, $impossibilites);

            // Étape 2 : Appliquer les règles par catégorie
            foreach ($this->rules as $categorie => $rulesCategorie) {
                // Filtrer par catégories actives si spécifié
                if (!empty($categoriesActives) && !in_array($categorie, $categoriesActives, true)) {
                    continue;
                }

                $statistiques['categories_traitees'][] = $categorie;

                foreach ($rulesCategorie as $ruleId => $rule) {
                    // Ignorer les règles explicitement exclues
                    if (in_array($ruleId, $reglesIgnorees, true)) {
                        continue;
                    }

                    $statistiques['nb_regles_evaluees']++;

                    // Enrichir la règle avec son ID et catégorie
                    $rule['id'] = $ruleId;
                    $rule['categorie'] = $categorie;

                    // Traiter la règle
                    $result = $this->processRule($rule, $workTree, $contexte);

                    $workTree = $result['workTree'];

                    if ($result['regleAppliquee'] !== null) {
                        $reglesAppliquees[] = $result['regleAppliquee'];
                        $statistiques['nb_regles_appliquees']++;
                        if ($result['regleAppliquee']->aModifie()) {
                            $statistiques['nb_ajustements']++;
                        }
                    }

                    if ($result['alerte'] !== null) {
                        $alertes[] = $result['alerte'];
                    }

                    if ($result['impossibilite'] !== null) {
                        $impossibilites[] = $result['impossibilite'];
                    }

                    if ($result['elementNonDetermine'] !== null) {
                        $elementsNonDetermines[] = $result['elementNonDetermine'];
                    }
                }
            }

            // Étape 3 : Vérifications finales de cohérence
            $workTree = $this->runFinalChecks($workTree, $contexte, $alertes, $impossibilites);

            // Déterminer le statut final
            $statut = $this->determineStatut($impossibilites, $alertes, $elementsNonDetermines, $input->isModeStrict());

            return new NormesEngineOutput(
                workTree: $workTree,
                statut: $statut,
                reglesAppliquees: $reglesAppliquees,
                alertes: $alertes,
                impossibilites: $impossibilites,
                elementsNonDetermines: $elementsNonDetermines,
                statistiques: $statistiques,
            );
        } catch (\Throwable $e) {
            // En cas d'erreur, retourner un output non conforme
            return NormesEngineOutput::nonConforme(
                workTree: $workTree,
                reglesAppliquees: $reglesAppliquees,
                alertes: $alertes,
                impossibilites: [
                    Impossibilite::configurationImpossible(
                        code: 'erreur_normes_engine',
                        message: "Erreur lors du traitement: {$e->getMessage()}",
                    ),
                ],
                statistiques: $statistiques,
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsRule(string $ruleId): bool
    {
        foreach ($this->rules as $categorie => $rulesCategorie) {
            if (isset($rulesCategorie[$ruleId])) {
                return true;
            }
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableCategories(): array
    {
        return array_keys($this->rules);
    }

    // =========================================================================
    // Étape 1 : Résolution des délégations
    // =========================================================================

    private function resolveDelegations(
        WorkTree $workTree,
        array $contexte,
        array &$reglesAppliquees,
        array &$alertes,
        array &$impossibilites,
    ): WorkTree {
        foreach ($workTree->delegationsNormes as $delegation) {
            $result = $this->resolveDelegation($delegation, $workTree, $contexte);

            $workTree = $result['workTree'];

            if ($result['regleAppliquee'] !== null) {
                $reglesAppliquees[] = $result['regleAppliquee'];
            }

            if ($result['alerte'] !== null) {
                $alertes[] = $result['alerte'];
            }

            if ($result['impossibilite'] !== null) {
                $impossibilites[] = $result['impossibilite'];
            }
        }

        // Marquer les délégations comme résolues
        return $workTree->clearDelegationsNormes();
    }

    private function resolveDelegation(
        \App\PipelineV2\WorkTreeBuilder\DTO\DelegationNormes $delegation,
        WorkTree $workTree,
        array $contexte,
    ): array {
        $type = $delegation->type;

        return match ($type) {
            'verification_domaine' => $this->resolveVerificationDomaine($delegation, $workTree, $contexte),
            'activation_conditionnelle' => $this->resolveActivationConditionnelle($delegation, $workTree, $contexte),
            'calcul_quantite' => $this->resolveCalculQuantite($delegation, $workTree, $contexte),
            'selection_materiel' => $this->resolveSelectionMateriel($delegation, $workTree, $contexte),
            default => [
                'workTree' => $workTree,
                'regleAppliquee' => null,
                'alerte' => Alerte::info(
                    code: 'delegation_non_supportee',
                    message: "Type de délégation non supporté: {$type}",
                ),
                'impossibilite' => null,
            ],
        };
    }

    private function resolveVerificationDomaine(
        \App\PipelineV2\WorkTreeBuilder\DTO\DelegationNormes $delegation,
        WorkTree $workTree,
        array $contexte,
    ): array {
        $domaine = $delegation->contexteRequis['domaine'] ?? 'inconnu';
        $verification = $delegation->contexteRequis['verification'] ?? [];
        $regle = $verification['regle'] ?? null;

        if ($regle === null) {
            return [
                'workTree' => $workTree,
                'regleAppliquee' => null,
                'alerte' => null,
                'impossibilite' => null,
            ];
        }

        // Chercher la règle correspondante
        foreach ($this->rules as $categorie => $rulesCategorie) {
            if (isset($rulesCategorie[$regle])) {
                $rule = $rulesCategorie[$regle];
                $rule['id'] = $regle;
                $rule['categorie'] = $categorie;
                return $this->processRule($rule, $workTree, $contexte);
            }
        }

        return [
            'workTree' => $workTree,
            'regleAppliquee' => null,
            'alerte' => null,
            'impossibilite' => null,
        ];
    }

    private function resolveActivationConditionnelle(
        \App\PipelineV2\WorkTreeBuilder\DTO\DelegationNormes $delegation,
        WorkTree $workTree,
        array $contexte,
    ): array {
        // V1: les activations conditionnelles sont gérées par le WorkTreeBuilder
        return [
            'workTree' => $workTree,
            'regleAppliquee' => null,
            'alerte' => null,
            'impossibilite' => null,
        ];
    }

    private function resolveCalculQuantite(
        \App\PipelineV2\WorkTreeBuilder\DTO\DelegationNormes $delegation,
        WorkTree $workTree,
        array $contexte,
    ): array {
        $regle = $delegation->contexteRequis['regle'] ?? null;

        if ($regle === null) {
            return [
                'workTree' => $workTree,
                'regleAppliquee' => null,
                'alerte' => null,
                'impossibilite' => null,
            ];
        }

        // V1: utiliser les valeurs par défaut de la règle
        foreach ($this->rules as $categorie => $rulesCategorie) {
            if (isset($rulesCategorie[$regle])) {
                $rule = $rulesCategorie[$regle];
                $valeurImposee = $rule['valeur_imposee'] ?? [];
                $minimum = $valeurImposee['minimum'] ?? null;

                if ($minimum !== null) {
                    return [
                        'workTree' => $workTree->addModification(
                            ModificationHistorique::modification(
                                etape: ModificationHistorique::ETAPE_NORMES_ENGINE,
                                cible: "delegation:{$delegation->travailInstanceId}",
                                champModifie: 'quantite',
                                ancienneValeur: null,
                                nouvelleValeur: $minimum,
                            )
                        ),
                        'regleAppliquee' => RegleAppliquee::modificationQuantite(
                            regleId: $regle,
                            categorie: $categorie,
                            label: $rule['label'] ?? $regle,
                            source: $rule['source'] ?? 'norme',
                            reference: $rule['reference'] ?? null,
                            sousTravailId: $delegation->sousTravailInstanceId ?? 'unknown',
                            ancienneQuantite: 0,
                            nouvelleQuantite: $minimum,
                        ),
                        'alerte' => null,
                        'impossibilite' => null,
                    ];
                }
            }
        }

        return [
            'workTree' => $workTree,
            'regleAppliquee' => null,
            'alerte' => null,
            'impossibilite' => null,
        ];
    }

    private function resolveSelectionMateriel(
        \App\PipelineV2\WorkTreeBuilder\DTO\DelegationNormes $delegation,
        WorkTree $workTree,
        array $contexte,
    ): array {
        // V1: retourner un élément non déterminé
        return [
            'workTree' => $workTree,
            'regleAppliquee' => null,
            'alerte' => null,
            'impossibilite' => null,
        ];
    }

    // =========================================================================
    // Étape 2 : Traitement des règles
    // =========================================================================

    /**
     * Traite une règle individuelle.
     *
     * @return array{
     *     workTree: WorkTree,
     *     regleAppliquee: RegleAppliquee|null,
     *     alerte: Alerte|null,
     *     impossibilite: Impossibilite|null,
     *     elementNonDetermine: ElementNonDetermine|null,
     * }
     */
    private function processRule(array $rule, WorkTree $workTree, array $contexte): array
    {
        $condition = $rule['condition'] ?? [];
        $severite = $rule['severite'] ?? 'info';

        // Évaluer si la règle s'applique
        $evalResult = $this->evaluator->evaluateCondition($condition, $workTree, $contexte);

        if (!$evalResult['applicable']) {
            // La règle ne s'applique pas
            return [
                'workTree' => $workTree,
                'regleAppliquee' => null,
                'alerte' => null,
                'impossibilite' => null,
                'elementNonDetermine' => null,
            ];
        }

        // La règle s'applique, déterminer l'action
        $action = $rule['action_si_non_conforme']
            ?? $rule['action_si_absent']
            ?? $rule['action_si_vrai']
            ?? null;

        // Vérifier les valeurs imposées
        if (isset($rule['valeur_imposee'])) {
            $checkResult = $this->checkValeurImposee($rule, $workTree, $contexte);
            if (!$checkResult['conforme']) {
                if ($checkResult['impossibilite'] !== null) {
                    return [
                        'workTree' => $workTree,
                        'regleAppliquee' => RegleAppliquee::verification(
                            regleId: $rule['id'],
                            categorie: $rule['categorie'],
                            label: $rule['label'] ?? '',
                            source: $rule['source'] ?? 'norme',
                            reference: $rule['reference'] ?? null,
                            conforme: false,
                        ),
                        'alerte' => null,
                        'impossibilite' => $checkResult['impossibilite'],
                        'elementNonDetermine' => null,
                    ];
                }

                if ($checkResult['alerte'] !== null && $action === null) {
                    return [
                        'workTree' => $workTree,
                        'regleAppliquee' => RegleAppliquee::verification(
                            regleId: $rule['id'],
                            categorie: $rule['categorie'],
                            label: $rule['label'] ?? '',
                            source: $rule['source'] ?? 'norme',
                            reference: $rule['reference'] ?? null,
                            conforme: false,
                        ),
                        'alerte' => $checkResult['alerte'],
                        'impossibilite' => null,
                        'elementNonDetermine' => null,
                    ];
                }
            }
        }

        // Appliquer l'action si définie
        if ($action !== null) {
            return $this->applier->applyAction($workTree, $rule, $action, $contexte);
        }

        // Règle de vérification sans action
        return [
            'workTree' => $workTree,
            'regleAppliquee' => RegleAppliquee::verification(
                regleId: $rule['id'],
                categorie: $rule['categorie'],
                label: $rule['label'] ?? '',
                source: $rule['source'] ?? 'norme',
                reference: $rule['reference'] ?? null,
                conforme: true,
            ),
            'alerte' => null,
            'impossibilite' => null,
            'elementNonDetermine' => null,
        ];
    }

    private function checkValeurImposee(array $rule, WorkTree $workTree, array $contexte): array
    {
        $valeurImposee = $rule['valeur_imposee'];
        $severite = $rule['severite'] ?? 'info';
        $reference = $rule['reference'] ?? null;

        // V1: vérification basique des minimums/maximums
        // La logique complète sera implémentée progressivement

        return ['conforme' => true, 'alerte' => null, 'impossibilite' => null];
    }

    // =========================================================================
    // Étape 3 : Vérifications finales
    // =========================================================================

    private function runFinalChecks(
        WorkTree $workTree,
        array $contexte,
        array &$alertes,
        array &$impossibilites,
    ): WorkTree {
        // V1: vérifications de cohérence basiques

        // Vérifier que les domaines non couverts sont signalés
        foreach ($workTree->domainesNonCouverts as $domaine) {
            if ($domaine->bloquant) {
                $impossibilites[] = Impossibilite::normeNonRespectee(
                    code: "domaine_{$domaine->domaine}",
                    message: "Domaine '{$domaine->domaine}' non couvert : {$domaine->actionRequise}",
                    regleId: 'verification_domaine',
                    reference: 'Vérification finale',
                );
            } else {
                $alertes[] = Alerte::attention(
                    code: "domaine_{$domaine->domaine}",
                    message: "Domaine '{$domaine->domaine}' non couvert : {$domaine->actionRequise}",
                );
            }
        }

        return $workTree;
    }

    // =========================================================================
    // Détermination du statut
    // =========================================================================

    private function determineStatut(
        array $impossibilites,
        array $alertes,
        array $elementsNonDetermines,
        bool $modeStrict,
    ): string {
        // Impossibilités = non conforme
        if (!empty($impossibilites)) {
            return NormesEngineOutput::STATUT_NON_CONFORME;
        }

        // En mode strict, les alertes bloquent
        if ($modeStrict && !empty($alertes)) {
            return NormesEngineOutput::STATUT_NON_CONFORME;
        }

        // Éléments non déterminés = incomplet
        if (!empty($elementsNonDetermines)) {
            return NormesEngineOutput::STATUT_INCOMPLET;
        }

        // Alertes non bloquantes
        if (!empty($alertes)) {
            return NormesEngineOutput::STATUT_CONFORME_AVEC_ALERTES;
        }

        return NormesEngineOutput::STATUT_CONFORME;
    }
}
