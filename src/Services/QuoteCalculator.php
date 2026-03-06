<?php

namespace App\Services;

/**
 * Service de calcul des montants du devis
 * Applique les prix de la grille et calcule les totaux
 * Supporte 3 niveaux de gamme: low, mid, high
 * Intègre la détermination automatique de la TVA
 */
class QuoteCalculator
{
    private array $priceGrid;
    private string $defaultTier = 'mid'; // Gamme par défaut: milieu
    private TvaService $tvaService;

    public function __construct()
    {
        $this->priceGrid = require __DIR__ . '/../../config/prices.php';
        $this->tvaService = new TvaService();
    }

    /**
     * Définit la gamme de prix à utiliser
     * @param string $tier 'low', 'mid', ou 'high'
     */
    public function setTier(string $tier): void
    {
        if (in_array($tier, ['low', 'mid', 'high'])) {
            $this->defaultTier = $tier;
        }
    }

    /**
     * Récupère le prix selon la gamme sélectionnée
     */
    private function getPriceByTier(array $gridItem, ?string $tier = null): float
    {
        $tier = $tier ?? $this->defaultTier;
        $priceKey = 'price_' . $tier;
        return (float) ($gridItem[$priceKey] ?? $gridItem['price_mid'] ?? 0);
    }

    /**
     * Calcule les montants pour chaque ligne et les totaux
     *
     * @param array $quoteData Données brutes du devis (depuis OpenAI)
     * @param string|null $tier Gamme de prix: 'low', 'mid', 'high'
     * @param string|null $description Description des travaux (pour calcul TVA)
     * @param array $tvaContext Contexte additionnel pour TVA (type_batiment, anciennete, etc.)
     * @return array Devis enrichi avec les montants calculés
     */
    public function calculate(array $quoteData, ?string $tier = null, ?string $description = null, array $tvaContext = []): array
    {
        if ($tier) {
            $this->setTier($tier);
        }

        $lignesCalculees = [];
        $totalHT = 0;
        $totalMateriel = 0;
        $totalMainOeuvre = 0;
        $totalForfait = 0;

        foreach ($quoteData['lignes'] as $ligne) {
            $prixRef = $ligne['prix_ref_code'];
            $quantite = (float) $ligne['quantite'];

            // Récupérer le prix unitaire selon la gamme
            if ($prixRef === 'CUSTOM') {
                // Prix suggéré par l'IA pour articles hors catalogue
                $prixUnitaireHT = (float) ($ligne['prix_unitaire_ht_suggere'] ?? 0);
                $label = $ligne['designation'];
                $unite = $ligne['unite'];
            } elseif (isset($this->priceGrid[$prixRef])) {
                $gridItem = $this->priceGrid[$prixRef];
                $prixUnitaireHT = $this->getPriceByTier($gridItem);
                $label = $gridItem['label'];
                $unite = $gridItem['unit'];
            } else {
                // Code inconnu : utiliser le prix suggéré ou 0
                $prixUnitaireHT = (float) ($ligne['prix_unitaire_ht_suggere'] ?? 0);
                $label = $ligne['designation'];
                $unite = $ligne['unite'];
            }

            $totalLigneHT = round($prixUnitaireHT * $quantite, 2);
            $totalHT += $totalLigneHT;

            // Répartition par catégorie
            switch ($ligne['categorie']) {
                case 'materiel':
                    $totalMateriel += $totalLigneHT;
                    break;
                case 'main_oeuvre':
                    $totalMainOeuvre += $totalLigneHT;
                    break;
                case 'forfait':
                    $totalForfait += $totalLigneHT;
                    break;
            }

            $lignesCalculees[] = [
                'designation' => $ligne['designation'],
                'designation_catalogue' => $label,
                'categorie' => $ligne['categorie'],
                'unite' => $unite,
                'quantite' => $quantite,
                'prix_unitaire_ht' => $prixUnitaireHT,
                'total_ligne_ht' => $totalLigneHT,
                'prix_ref_code' => $prixRef,
                'commentaire' => $ligne['commentaire']
            ];
        }

        // Détermination automatique de la TVA si description fournie
        $tvaInfo = null;
        if ($description !== null) {
            $tvaInfo = $this->tvaService->determinerTva($description, $lignesCalculees, $tvaContext);
            $tauxTVA = $tvaInfo['taux'];
        } else {
            // Utiliser le taux suggéré par OpenAI ou 20% par défaut
            $tauxTVA = (float) ($quoteData['taux_tva'] ?? 20);
        }

        // Calculs TVA et TTC
        $montantTVA = round($totalHT * ($tauxTVA / 100), 2);
        $totalTTC = round($totalHT + $montantTVA, 2);

        // Enrichir le devis
        $quoteData['lignes'] = $lignesCalculees;
        $quoteData['totaux'] = [
            'materiel_ht' => round($totalMateriel, 2),
            'main_oeuvre_ht' => round($totalMainOeuvre, 2),
            'forfait_ht' => round($totalForfait, 2),
            'total_ht' => round($totalHT, 2),
            'taux_tva' => $tauxTVA,
            'montant_tva' => $montantTVA,
            'total_ttc' => $totalTTC
        ];

        // Ajouter les informations TVA si calculées automatiquement
        if ($tvaInfo !== null) {
            $quoteData['tva_info'] = [
                'taux' => $tvaInfo['taux'],
                'raison' => $tvaInfo['raison'],
                'message_devis' => $tvaInfo['message_devis'],
                'attestation' => $tvaInfo['attestation'],
                'questions_tva' => $tvaInfo['questions']
            ];

            // Mettre à jour la remarque TVA si applicable
            if ($tvaInfo['message_devis']) {
                $quoteData['remarque_tva'] = $tvaInfo['message_devis'];
            }
        }

        return $quoteData;
    }

    /**
     * Retourne la grille de prix pour référence
     */
    public function getPriceGrid(): array
    {
        return $this->priceGrid;
    }

    /**
     * Vérifie si un code prix existe dans la grille
     */
    public function priceCodeExists(string $code): bool
    {
        return isset($this->priceGrid[$code]);
    }

    /**
     * Obtient le détail d'un code prix
     */
    public function getPriceDetail(string $code): ?array
    {
        return $this->priceGrid[$code] ?? null;
    }

    /**
     * Retourne la gamme de prix actuellement utilisée
     */
    public function getCurrentTier(): string
    {
        return $this->defaultTier;
    }

    /**
     * Retourne les libellés des gammes de prix
     */
    public function getTierLabels(): array
    {
        return [
            'low' => 'Entrée de gamme',
            'mid' => 'Milieu de gamme',
            'high' => 'Haut de gamme'
        ];
    }

    /**
     * Accès au service TVA pour utilisation directe
     */
    public function getTvaService(): TvaService
    {
        return $this->tvaService;
    }

    /**
     * Détermine la TVA sans calculer le devis complet
     * Utile pour pré-analyse
     */
    public function determinerTva(string $description, array $lignes = [], array $context = []): array
    {
        return $this->tvaService->determinerTva($description, $lignes, $context);
    }
}
