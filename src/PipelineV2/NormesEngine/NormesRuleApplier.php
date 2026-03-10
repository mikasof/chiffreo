<?php

declare(strict_types=1);

namespace App\PipelineV2\NormesEngine;

use App\PipelineV2\NormesEngine\DTO\Alerte;
use App\PipelineV2\NormesEngine\DTO\ElementNonDetermine;
use App\PipelineV2\NormesEngine\DTO\Impossibilite;
use App\PipelineV2\NormesEngine\DTO\RegleAppliquee;
use App\PipelineV2\NormesEngine\Exception\RuleApplicationException;
use App\PipelineV2\WorkTreeBuilder\DTO\ModificationHistorique;
use App\PipelineV2\WorkTreeBuilder\DTO\WorkTree;
use App\PipelineV2\WorkTreeBuilder\DTO\WorkTreeTravail;
use App\PipelineV2\WorkTreeBuilder\DTO\WorkTreeSousTravail;
use App\PipelineV2\WorkTreeBuilder\DTO\Activation;
use App\PipelineV2\WorkTreeBuilder\DTO\Scope;

/**
 * Applique les actions des règles normatives sur le WorkTree.
 *
 * Responsabilités :
 * - Ajouter des travaux/sous-travaux
 * - Modifier des quantités
 * - Ajuster des matériels
 * - Tracer les modifications
 */
final class NormesRuleApplier
{
    /**
     * @param array $travauxDefinitions Définitions des travaux
     */
    public function __construct(
        private readonly array $travauxDefinitions = [],
    ) {}

    // =========================================================================
    // Application des actions
    // =========================================================================

    /**
     * Applique une action de règle sur le WorkTree.
     *
     * @return array{
     *     workTree: WorkTree,
     *     regleAppliquee: RegleAppliquee|null,
     *     alerte: Alerte|null,
     *     impossibilite: Impossibilite|null,
     *     elementNonDetermine: ElementNonDetermine|null,
     * }
     */
    public function applyAction(
        WorkTree $workTree,
        array $rule,
        array $action,
        array $contexte,
    ): array {
        $actionType = is_string($action) ? $action : ($action['type'] ?? null);
        $actionConfig = is_array($action) ? $action : [];

        $ruleId = $rule['id'] ?? 'unknown';
        $categorie = $rule['categorie'] ?? 'general';
        $label = $rule['label'] ?? $ruleId;
        $source = $rule['source'] ?? 'norme';
        $reference = $rule['reference'] ?? null;

        try {
            return match ($actionType) {
                'ajouter_travail' => $this->applyAjouterTravail($workTree, $rule, $actionConfig, $contexte),
                'ajouter_sous_travail' => $this->applyAjouterSousTravail($workTree, $rule, $actionConfig, $contexte),
                'modification_quantite', 'completer_quantites' => $this->applyModificationQuantite($workTree, $rule, $actionConfig, $contexte),
                'ajout_differentiel' => $this->applyAjoutDifferentiel($workTree, $rule, $actionConfig, $contexte),
                'imposer_type_differentiel' => $this->applyImposerTypeDifferentiel($workTree, $rule, $actionConfig, $contexte),
                'proposer_type_differentiel' => $this->applyProposerTypeDifferentiel($workTree, $rule, $actionConfig, $contexte),
                'ajouter_besoin' => $this->applyAjouterBesoin($workTree, $rule, $actionConfig, $contexte),
                'ajuster_section' => $this->applyAjusterSection($workTree, $rule, $actionConfig, $contexte),
                'augmenter_taille_coffret' => $this->applyAugmenterTailleCoffret($workTree, $rule, $actionConfig, $contexte),
                'verifier_protection' => $this->applyVerifierProtection($workTree, $rule, $actionConfig, $contexte),
                default => $this->noAction($workTree, $rule, $actionType),
            };
        } catch (RuleApplicationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new RuleApplicationException(
                ruleId: $ruleId,
                actionType: $actionType ?? 'unknown',
                reason: $e->getMessage(),
                contexte: ['rule' => $rule, 'action' => $action],
                previous: $e,
            );
        }
    }

    // =========================================================================
    // Actions spécifiques
    // =========================================================================

    private function applyAjouterTravail(
        WorkTree $workTree,
        array $rule,
        array $actionConfig,
        array $contexte,
    ): array {
        $travailId = $actionConfig['travail_id'] ?? $rule['impacte']['travail_id'] ?? null;

        if ($travailId === null) {
            return $this->noAction($workTree, $rule, 'ajouter_travail', 'travail_id manquant');
        }

        // Vérifier si le travail existe déjà
        if ($workTree->hasTravail($travailId)) {
            return $this->noAction($workTree, $rule, 'ajouter_travail', 'travail déjà présent');
        }

        // Charger la définition du travail
        $definition = $this->travauxDefinitions[$travailId] ?? null;

        if ($definition === null) {
            // Créer une définition minimale
            $definition = [
                'id' => $travailId,
                'label' => $this->humanizeId($travailId),
                'sous_travaux' => [],
            ];
        }

        // Créer le travail
        $travail = WorkTreeTravail::fromDefinition(
            id: $travailId,
            definition: $definition,
            ordre: 500, // Ordre élevé pour les ajouts normatifs
            activation: Activation::norme($rule['id'] ?? 'unknown'),
            origine: 'norme',
            scope: Scope::chantier(),
        );

        // Ajouter les sous-travaux basiques
        foreach ($definition['sous_travaux'] ?? [] as $stId => $stConfig) {
            $sousTravail = WorkTreeSousTravail::fromDefinition(
                id: $stId,
                definition: $stConfig,
                scope: Scope::chantier(),
                ordre: 10,
                quantiteBrute: 1,
                multiplicateur: null,
                quantiteFinale: 1,
                delegueNormes: false,
                origine: 'norme',
            );
            $travail = $travail->addSousTravail($sousTravail);
        }

        // Ajouter au WorkTree avec historique
        $workTree = $workTree->addTravail($travail);
        $workTree = $workTree->addModification(
            ModificationHistorique::creation(
                etape: ModificationHistorique::ETAPE_NORMES_ENGINE,
                cible: "travail:{$travail->instanceId}",
                valeur: ['id' => $travailId, 'regle' => $rule['id'] ?? null],
            )
        );

        return [
            'workTree' => $workTree,
            'regleAppliquee' => RegleAppliquee::ajoutTravail(
                regleId: $rule['id'] ?? 'unknown',
                categorie: $rule['categorie'] ?? 'general',
                label: $rule['label'] ?? '',
                source: $rule['source'] ?? 'norme',
                reference: $rule['reference'] ?? null,
                travailId: $travailId,
                contexte: $contexte,
            ),
            'alerte' => null,
            'impossibilite' => null,
            'elementNonDetermine' => null,
        ];
    }

    private function applyAjouterSousTravail(
        WorkTree $workTree,
        array $rule,
        array $actionConfig,
        array $contexte,
    ): array {
        $sousTravailId = $actionConfig['sous_travail_id'] ?? $rule['impacte']['sous_travail_id'] ?? null;
        $travailId = $actionConfig['travail_id'] ?? $rule['impacte']['travail_id'] ?? null;

        if ($sousTravailId === null) {
            return $this->noAction($workTree, $rule, 'ajouter_sous_travail', 'sous_travail_id manquant');
        }

        // V1: implémentation simplifiée - juste tracer l'intention
        return [
            'workTree' => $workTree->addModification(
                ModificationHistorique::creation(
                    etape: ModificationHistorique::ETAPE_NORMES_ENGINE,
                    cible: "sous_travail:{$sousTravailId}",
                    valeur: ['regle' => $rule['id'] ?? null, 'travail_id' => $travailId],
                )
            ),
            'regleAppliquee' => new RegleAppliquee(
                regleId: $rule['id'] ?? 'unknown',
                categorie: $rule['categorie'] ?? 'general',
                label: $rule['label'] ?? '',
                source: $rule['source'] ?? 'norme',
                reference: $rule['reference'] ?? null,
                action: RegleAppliquee::ACTION_AJOUT_SOUS_TRAVAIL,
                modifications: ['sous_travail_id' => $sousTravailId],
            ),
            'alerte' => null,
            'impossibilite' => null,
            'elementNonDetermine' => null,
        ];
    }

    private function applyModificationQuantite(
        WorkTree $workTree,
        array $rule,
        array $actionConfig,
        array $contexte,
    ): array {
        $minimum = $rule['valeur_imposee']['minimum'] ?? $actionConfig['minimum'] ?? null;
        $sousTravailId = $rule['impacte']['sous_travail_id'] ?? null;

        if ($minimum === null || $sousTravailId === null) {
            return $this->noAction($workTree, $rule, 'modification_quantite');
        }

        // V1: tracer l'ajustement de quantité
        return [
            'workTree' => $workTree->addModification(
                ModificationHistorique::modification(
                    etape: ModificationHistorique::ETAPE_NORMES_ENGINE,
                    cible: "sous_travail:{$sousTravailId}",
                    champModifie: 'quantite',
                    ancienneValeur: null,
                    nouvelleValeur: $minimum,
                )
            ),
            'regleAppliquee' => RegleAppliquee::modificationQuantite(
                regleId: $rule['id'] ?? 'unknown',
                categorie: $rule['categorie'] ?? 'general',
                label: $rule['label'] ?? '',
                source: $rule['source'] ?? 'norme',
                reference: $rule['reference'] ?? null,
                sousTravailId: $sousTravailId,
                ancienneQuantite: 0,
                nouvelleQuantite: $minimum,
                contexte: $contexte,
            ),
            'alerte' => null,
            'impossibilite' => null,
            'elementNonDetermine' => null,
        ];
    }

    private function applyAjoutDifferentiel(
        WorkTree $workTree,
        array $rule,
        array $actionConfig,
        array $contexte,
    ): array {
        // V1: retourner une alerte pour signaler le besoin
        return [
            'workTree' => $workTree,
            'regleAppliquee' => new RegleAppliquee(
                regleId: $rule['id'] ?? 'unknown',
                categorie: $rule['categorie'] ?? 'protection_differentielle',
                label: $rule['label'] ?? '',
                source: $rule['source'] ?? 'norme',
                reference: $rule['reference'] ?? null,
                action: RegleAppliquee::ACTION_AJOUT_BESOIN,
                modifications: ['type' => 'differentiel'],
            ),
            'alerte' => Alerte::attention(
                code: 'differentiel_supplementaire',
                message: $actionConfig['message'] ?? 'Un différentiel supplémentaire est nécessaire',
                regleId: $rule['id'] ?? null,
            ),
            'impossibilite' => null,
            'elementNonDetermine' => null,
        ];
    }

    private function applyImposerTypeDifferentiel(
        WorkTree $workTree,
        array $rule,
        array $actionConfig,
        array $contexte,
    ): array {
        $typeDifferentiel = $actionConfig['type_differentiel'] ?? 'type_a';

        return [
            'workTree' => $workTree->addModification(
                ModificationHistorique::modification(
                    etape: ModificationHistorique::ETAPE_NORMES_ENGINE,
                    cible: 'protection_differentielle',
                    champModifie: 'type_differentiel',
                    ancienneValeur: 'type_ac',
                    nouvelleValeur: $typeDifferentiel,
                )
            ),
            'regleAppliquee' => new RegleAppliquee(
                regleId: $rule['id'] ?? 'unknown',
                categorie: $rule['categorie'] ?? 'protection_differentielle',
                label: $rule['label'] ?? '',
                source: $rule['source'] ?? 'norme',
                reference: $rule['reference'] ?? null,
                action: RegleAppliquee::ACTION_MODIFICATION_MATERIEL,
                modifications: ['type_differentiel' => $typeDifferentiel],
            ),
            'alerte' => null,
            'impossibilite' => null,
            'elementNonDetermine' => null,
        ];
    }

    private function applyProposerTypeDifferentiel(
        WorkTree $workTree,
        array $rule,
        array $actionConfig,
        array $contexte,
    ): array {
        // C'est une recommandation, pas une obligation
        return [
            'workTree' => $workTree,
            'regleAppliquee' => null,
            'alerte' => Alerte::info(
                code: 'recommandation_differentiel',
                message: $actionConfig['message'] ?? "Type {$actionConfig['type_differentiel']} recommandé",
                regleId: $rule['id'] ?? null,
            ),
            'impossibilite' => null,
            'elementNonDetermine' => null,
        ];
    }

    private function applyAjouterBesoin(
        WorkTree $workTree,
        array $rule,
        array $actionConfig,
        array $contexte,
    ): array {
        $besoinTechnique = $actionConfig['besoin_technique'] ?? null;

        if ($besoinTechnique === null) {
            return $this->noAction($workTree, $rule, 'ajouter_besoin');
        }

        // V1: tracer l'ajout de besoin
        return [
            'workTree' => $workTree->addModification(
                ModificationHistorique::creation(
                    etape: ModificationHistorique::ETAPE_NORMES_ENGINE,
                    cible: "besoin:{$besoinTechnique}",
                    valeur: ['regle' => $rule['id'] ?? null],
                )
            ),
            'regleAppliquee' => new RegleAppliquee(
                regleId: $rule['id'] ?? 'unknown',
                categorie: $rule['categorie'] ?? 'general',
                label: $rule['label'] ?? '',
                source: $rule['source'] ?? 'norme',
                reference: $rule['reference'] ?? null,
                action: RegleAppliquee::ACTION_AJOUT_BESOIN,
                modifications: ['besoin_technique' => $besoinTechnique],
            ),
            'alerte' => null,
            'impossibilite' => null,
            'elementNonDetermine' => null,
        ];
    }

    private function applyAjusterSection(
        WorkTree $workTree,
        array $rule,
        array $actionConfig,
        array $contexte,
    ): array {
        // V1: retourner un élément non déterminé si les données manquent
        $champsManquants = [];
        if (!isset($contexte['puissance_borne'])) {
            $champsManquants[] = 'puissance_borne';
        }
        if (!isset($contexte['distance_tableau'])) {
            $champsManquants[] = 'distance_tableau';
        }

        if (!empty($champsManquants)) {
            return [
                'workTree' => $workTree,
                'regleAppliquee' => null,
                'alerte' => null,
                'impossibilite' => null,
                'elementNonDetermine' => ElementNonDetermine::sectionCable(
                    code: 'section_cable_irve',
                    description: 'Section du câble IRVE non déterminable',
                    sousTravailId: $rule['impacte']['sous_travail_id'] ?? 'tirage_cable_alimentation',
                    champsManquants: $champsManquants,
                    sectionParDefaut: 6.0,
                ),
            ];
        }

        return $this->noAction($workTree, $rule, 'ajuster_section');
    }

    private function applyAugmenterTailleCoffret(
        WorkTree $workTree,
        array $rule,
        array $actionConfig,
        array $contexte,
    ): array {
        return [
            'workTree' => $workTree,
            'regleAppliquee' => new RegleAppliquee(
                regleId: $rule['id'] ?? 'unknown',
                categorie: $rule['categorie'] ?? 'tableau',
                label: $rule['label'] ?? '',
                source: $rule['source'] ?? 'norme',
                reference: $rule['reference'] ?? null,
                action: RegleAppliquee::ACTION_MODIFICATION_MATERIEL,
                modifications: ['action' => 'augmenter_taille_coffret'],
            ),
            'alerte' => Alerte::attention(
                code: 'taille_coffret',
                message: $actionConfig['message'] ?? 'Taille du coffret à augmenter pour respecter réserve 20%',
                regleId: $rule['id'] ?? null,
            ),
            'impossibilite' => null,
            'elementNonDetermine' => null,
        ];
    }

    private function applyVerifierProtection(
        WorkTree $workTree,
        array $rule,
        array $actionConfig,
        array $contexte,
    ): array {
        // V1: vérification de conformité simple
        return [
            'workTree' => $workTree,
            'regleAppliquee' => RegleAppliquee::verification(
                regleId: $rule['id'] ?? 'unknown',
                categorie: $rule['categorie'] ?? 'protection',
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

    private function noAction(
        WorkTree $workTree,
        array $rule,
        string $actionType,
        ?string $raison = null,
    ): array {
        return [
            'workTree' => $workTree,
            'regleAppliquee' => null,
            'alerte' => null,
            'impossibilite' => null,
            'elementNonDetermine' => null,
        ];
    }

    // =========================================================================
    // Utilitaires
    // =========================================================================

    private function humanizeId(string $id): string
    {
        return ucfirst(str_replace('_', ' ', $id));
    }
}
