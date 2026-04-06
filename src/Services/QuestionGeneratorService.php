<?php

namespace App\Services;

/**
 * Service de génération de questions contextuelles
 * Analyse la description des travaux et retourne les questions pertinentes
 */
class QuestionGeneratorService
{
    private array $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/questions_config.php';
    }

    /**
     * Génère les questions pertinentes basées sur la description
     *
     * @param string $description Description des travaux (texte ou transcription)
     * @param int $maxQuestions Nombre maximum de questions (défaut: 5)
     * @return array Liste des questions avec leurs métadonnées
     */
    public function generateQuestions(string $description, int $maxQuestions = 5): array
    {
        // Détecter le type de travaux
        $workType = $this->detectWorkType($description);

        // Récupérer les questions pour ce type
        $questions = $this->config['questions_par_type'][$workType] ?? [];

        // Trier par priorité
        usort($questions, fn($a, $b) => ($a['priorite'] ?? 10) <=> ($b['priorite'] ?? 10));

        // Limiter au nombre max
        $questions = array_slice($questions, 0, $maxQuestions);

        return [
            'work_type' => $workType,
            'questions' => $questions
        ];
    }

    /**
     * Détecte le type de travaux à partir de la description
     *
     * @param string $description
     * @return string Type détecté (irve, tableau, renovation, general)
     */
    public function detectWorkType(string $description): string
    {
        $descriptionLower = mb_strtolower($description, 'UTF-8');
        $keywords = $this->config['detection_keywords'] ?? [];

        // Score pour chaque type
        $scores = [];

        foreach ($keywords as $type => $typeKeywords) {
            $scores[$type] = 0;
            foreach ($typeKeywords as $keyword) {
                if (mb_strpos($descriptionLower, mb_strtolower($keyword, 'UTF-8')) !== false) {
                    $scores[$type]++;
                }
            }
        }

        // Trouver le type avec le meilleur score
        arsort($scores);
        $bestType = array_key_first($scores);

        // Si aucun mot-clé trouvé, retourner 'general'
        if ($scores[$bestType] === 0) {
            return 'general';
        }

        return $bestType;
    }

    /**
     * Traite une réponse et détermine s'il y a une sous-question
     *
     * @param array $question La question posée
     * @param string $answer La réponse donnée
     * @return array|null Sous-question si applicable, null sinon
     */
    public function getSubQuestion(array $question, string $answer): ?array
    {
        if (!isset($question['sous_question_si'])) {
            return null;
        }

        // Chercher une correspondance exacte
        if (isset($question['sous_question_si'][$answer])) {
            return $question['sous_question_si'][$answer];
        }

        // Chercher une correspondance partielle (pour les réponses longues)
        foreach ($question['sous_question_si'] as $trigger => $subQuestion) {
            if (mb_strpos($answer, $trigger) !== false) {
                return $subQuestion;
            }
        }

        return null;
    }

    /**
     * Formate les réponses pour injection dans le prompt de génération
     *
     * @param array $answers Liste des réponses [{question, reponse, impact}, ...]
     * @return string Texte formaté pour enrichir la description
     */
    public function formatAnswersForPrompt(array $answers): string
    {
        if (empty($answers)) {
            return '';
        }

        $lines = ["\n\n## Informations complémentaires fournies par le client:"];

        foreach ($answers as $answer) {
            $reponse = $answer['reponse'] ?? '';

            // Ignorer les "Je ne sais pas"
            if ($reponse === 'Je ne sais pas' || empty($reponse)) {
                continue;
            }

            $question = $answer['question'] ?? '';
            $lines[] = "- {$question}: {$reponse}";
        }

        // Si aucune réponse utile, retourner vide
        if (count($lines) === 1) {
            return '';
        }

        return implode("\n", $lines);
    }

    /**
     * Extrait les paramètres automatiques des réponses
     * (ex: TVA, type de local, etc.)
     *
     * @param array $answers Liste des réponses
     * @return array Paramètres détectés
     */
    public function extractParameters(array $answers): array
    {
        $params = [];

        foreach ($answers as $answer) {
            $questionId = $answer['question_id'] ?? '';
            $reponse = $answer['reponse'] ?? '';

            // Ancienneté du logement → TVA
            if ($questionId === 'anciennete_logement') {
                if ($reponse === 'Oui') {
                    $params['logement_ancien'] = true;
                    $params['tva_reduite_possible'] = true;
                } elseif ($reponse === 'Non') {
                    $params['logement_ancien'] = false;
                    $params['tva_reduite_possible'] = false;
                    $params['tva_forcee'] = 20;
                }
            }

            // Type de local → TVA
            if ($questionId === 'type_local') {
                if (in_array($reponse, ['Local commercial', 'Bureau', 'ERP'])) {
                    $params['local_professionnel'] = true;
                    $params['tva_forcee'] = 20;
                }
            }

            // Surface → estimation
            if ($questionId === 'surface') {
                $params['surface_estimee'] = $reponse;
            }

            // Puissance → avertissement triphasé
            if ($questionId === 'puissance_souscrite') {
                $params['puissance_actuelle'] = $reponse;
            }
        }

        return $params;
    }
}
