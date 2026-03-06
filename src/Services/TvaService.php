<?php
namespace App\Services;

/**
 * Service de détermination automatique du taux de TVA
 *
 * Analyse la description des travaux et les lignes du devis
 * pour déterminer le taux de TVA applicable selon la législation française.
 */
class TvaService
{
    private array $rules;

    public function __construct()
    {
        $this->rules = require __DIR__ . '/../../config/tva_rules.php';
    }

    /**
     * Analyse complète pour déterminer le taux de TVA
     *
     * @param string $description Description des travaux
     * @param array $lignes Lignes du devis (optionnel)
     * @param array $context Contexte additionnel (type_batiment, anciennete, etc.)
     * @return array ['taux' => float, 'raison' => string, 'attestation' => array|null, 'questions' => array]
     */
    public function determinerTva(string $description, array $lignes = [], array $context = []): array
    {
        $descLower = $this->normaliser($description);

        // 1. Vérifier les exclusions (toujours 20%)
        if ($this->estExclu($descLower, $context)) {
            return $this->resultat(20.0, 'Construction neuve ou local professionnel - TVA standard applicable');
        }

        // 2. Détecter le type de bâtiment
        $typeBatiment = $context['type_batiment'] ?? $this->detecterTypeBatiment($descLower);
        $anciennete = $context['anciennete'] ?? $this->detecterAnciennete($descLower);

        // 3. Si pas habitation ou pas > 2 ans, TVA 20%
        if ($typeBatiment === 'professionnel') {
            return $this->resultat(20.0, 'Local professionnel - TVA standard applicable');
        }

        if ($anciennete === 'neuf' || $anciennete === 'recent') {
            return $this->resultat(20.0, 'Bâtiment de moins de 2 ans - TVA standard applicable');
        }

        // 4. Vérifier si travaux éligibles TVA 5.5% (prioritaire)
        $tva55 = $this->verifierTva55($descLower, $lignes);
        if ($tva55['eligible']) {
            return $this->resultat(
                5.5,
                'Travaux d\'amélioration énergétique : ' . implode(', ', $tva55['motifs']),
                $this->rules['attestations']['tva_5_5']
            );
        }

        // 5. Vérifier si travaux éligibles TVA 10%
        $tva10 = $this->verifierTva10($descLower);
        if ($tva10['eligible']) {
            // Ajouter question sur l'ancienneté si non confirmée
            $questions = [];
            if ($anciennete === 'inconnu') {
                $questions[] = $this->rules['questions_tva']['anciennete'];
            }

            return $this->resultat(
                10.0,
                'Travaux de rénovation sur logement > 2 ans : ' . implode(', ', $tva10['motifs']),
                $this->rules['attestations']['tva_10'],
                $questions
            );
        }

        // 6. Par défaut, si habitation > 2 ans détectée = 10%, sinon questions
        if ($typeBatiment === 'habitation' && $anciennete === 'ancien') {
            return $this->resultat(
                10.0,
                'Travaux sur logement achevé depuis plus de 2 ans',
                $this->rules['attestations']['tva_10']
            );
        }

        // 7. Informations insuffisantes - retourner 20% avec questions
        $questions = [];
        if ($typeBatiment === 'inconnu') {
            $questions[] = $this->rules['questions_tva']['usage'];
        }
        if ($anciennete === 'inconnu') {
            $questions[] = $this->rules['questions_tva']['anciennete'];
        }

        return $this->resultat(
            20.0,
            'TVA standard par défaut (informations insuffisantes pour TVA réduite)',
            null,
            $questions
        );
    }

    /**
     * Vérifie si les travaux sont exclus de la TVA réduite
     */
    private function estExclu(string $description, array $context): bool
    {
        // Contexte explicite
        if (isset($context['neuf']) && $context['neuf'] === true) {
            return true;
        }
        if (isset($context['type_batiment']) && $context['type_batiment'] === 'professionnel') {
            return true;
        }

        // Recherche dans la description
        foreach ($this->rules['exclusions_tva_reduite'] as $mot) {
            if (str_contains($description, $mot)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Détecte le type de bâtiment (habitation ou professionnel)
     */
    private function detecterTypeBatiment(string $description): string
    {
        $scoreHabitation = 0;
        $scoreProfessionnel = 0;

        foreach ($this->rules['detection_batiment']['habitation'] as $mot) {
            if (str_contains($description, $mot)) {
                $scoreHabitation++;
            }
        }

        foreach ($this->rules['detection_batiment']['professionnel'] as $mot) {
            if (str_contains($description, $mot)) {
                $scoreProfessionnel++;
            }
        }

        if ($scoreHabitation > $scoreProfessionnel && $scoreHabitation > 0) {
            return 'habitation';
        }
        if ($scoreProfessionnel > $scoreHabitation && $scoreProfessionnel > 0) {
            return 'professionnel';
        }

        return 'inconnu';
    }

    /**
     * Détecte l'ancienneté du bâtiment
     */
    private function detecterAnciennete(string $description): string
    {
        // Bâtiment récent/neuf
        $motsNeuf = ['construction neuve', 'batiment neuf', 'maison neuve', 'appartement neuf', 'moins de 2 ans', 'livraison 2024', 'livraison 2025', 'livraison 2026'];
        foreach ($motsNeuf as $mot) {
            if (str_contains($description, $mot)) {
                return 'neuf';
            }
        }

        // Bâtiment ancien
        $motsAncien = ['renovation', 'ancien', 'plus de 2 ans', 'vieille maison', 'appartement ancien', 'annees 19', 'annees 20', 'mise aux normes', 'remise aux normes', 'refaire', 'refection'];
        foreach ($motsAncien as $mot) {
            if (str_contains($description, $mot)) {
                return 'ancien';
            }
        }

        return 'inconnu';
    }

    /**
     * Vérifie l'éligibilité à la TVA 5.5%
     */
    private function verifierTva55(string $description, array $lignes): array
    {
        $motifs = [];

        // Vérifier mots-clés dans description
        foreach ($this->rules['conditions_tva_5_5']['travaux_eligibles'] as $travail) {
            if (str_contains($description, $travail)) {
                $motifs[] = $travail;
            }
        }

        // Vérifier codes dans les lignes du devis
        foreach ($lignes as $ligne) {
            $code = $ligne['prix_ref_code'] ?? '';
            if (in_array($code, $this->rules['conditions_tva_5_5']['equipements_eligibles'])) {
                $motifs[] = $ligne['designation'] ?? $code;
            }
        }

        return [
            'eligible' => !empty($motifs),
            'motifs' => array_unique($motifs),
        ];
    }

    /**
     * Vérifie l'éligibilité à la TVA 10%
     */
    private function verifierTva10(string $description): array
    {
        $motifs = [];

        foreach ($this->rules['conditions_tva_10']['travaux_eligibles'] as $travail) {
            if (str_contains($description, $travail)) {
                $motifs[] = $travail;
            }
        }

        // Mots-clés génériques de rénovation
        $motsRenovation = ['installer', 'pose', 'remplacement', 'changer', 'ajouter', 'creer', 'tirer'];
        foreach ($motsRenovation as $mot) {
            if (str_contains($description, $mot)) {
                $motifs[] = 'travaux de ' . $mot;
                break; // Un seul suffit
            }
        }

        return [
            'eligible' => !empty($motifs),
            'motifs' => array_unique($motifs),
        ];
    }

    /**
     * Normalise le texte pour la recherche
     */
    private function normaliser(string $text): string
    {
        $text = mb_strtolower($text);
        $text = str_replace(
            ['é', 'è', 'ê', 'ë', 'à', 'â', 'ù', 'û', 'ô', 'î', 'ï', 'ç'],
            ['e', 'e', 'e', 'e', 'a', 'a', 'u', 'u', 'o', 'i', 'i', 'c'],
            $text
        );
        return $text;
    }

    /**
     * Construit le résultat
     */
    private function resultat(float $taux, string $raison, ?array $attestation = null, array $questions = []): array
    {
        // Convertir le taux en string pour accéder aux messages (PHP 8 n'accepte pas les floats comme clés)
        $tauxKey = (string) $taux;

        return [
            'taux' => $taux,
            'raison' => $raison,
            'message_devis' => $this->rules['messages'][$tauxKey] ?? null,
            'attestation' => $attestation,
            'questions' => $questions,
        ];
    }

    /**
     * Obtenir les règles de TVA (pour debug/affichage)
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Calculer le montant TTC à partir du HT
     */
    public function calculerTTC(float $montantHT, float $taux): float
    {
        return round($montantHT * (1 + $taux / 100), 2);
    }

    /**
     * Calculer le montant de TVA
     */
    public function calculerMontantTVA(float $montantHT, float $taux): float
    {
        return round($montantHT * $taux / 100, 2);
    }
}
