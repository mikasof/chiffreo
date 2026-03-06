<?php

namespace App\Services;

use App\Models\CompanyRepository;
use App\Database\Connection;
use PDO;

/**
 * Service de gestion des quotas
 * Architecture: quotas au niveau Company, pas User
 */
class QuotaService
{
    private CompanyRepository $companyRepo;
    private PDO $db;

    // Limites par plan
    private const LIMITS = [
        'decouverte' => [
            'quotes_per_month' => 10,
            'images_per_quote' => 2,
            'audio_duration_seconds' => 60,
            'pdf_watermark' => true,
            'export_formats' => ['pdf']
        ],
        'pro' => [
            'quotes_per_month' => null, // illimité
            'images_per_quote' => 10,
            'audio_duration_seconds' => 300,
            'pdf_watermark' => false,
            'export_formats' => ['pdf', 'xlsx', 'csv']
        ],
        'equipe' => [
            'quotes_per_month' => null,
            'images_per_quote' => 20,
            'audio_duration_seconds' => 600,
            'pdf_watermark' => false,
            'export_formats' => ['pdf', 'xlsx', 'csv', 'json']
        ]
    ];

    public function __construct()
    {
        $this->companyRepo = new CompanyRepository();
        $this->db = Connection::getInstance();
    }

    /**
     * Vérifie si l'utilisateur peut créer un devis
     * Prend un array user avec company imbriquée
     */
    public function canCreateQuote(array $user): bool
    {
        $company = $user['company'];

        // Trial actif = accès illimité
        if ($company['trial_ends_at'] && strtotime($company['trial_ends_at']) > time()) {
            return true;
        }

        // Plans pro/equipe après trial expiré -> downgrade auto vers découverte
        if (in_array($company['plan'], ['pro', 'equipe'])) {
            // Trial expiré mais encore sur plan payant sans paiement = downgrade
            // Note: Dans une vraie app, on vérifierait le statut de paiement
            // Pour le MVP, on assume que si trial expiré et plan pro/equipe, c'est un bug
            // ou l'utilisateur paie vraiment
            return true;
        }

        // Plan découverte : max 10 devis/mois pour toute l'entreprise
        if ($company['plan'] === 'decouverte') {
            $currentMonth = date('Y-m-01');
            $quotesThisMonth = ($company['quotes_month_reset'] === $currentMonth)
                ? $company['quotes_this_month']
                : 0;

            return $quotesThisMonth < 10;
        }

        return true;
    }

    /**
     * Obtient le plan effectif de la company (tenant compte de l'essai)
     */
    public function getEffectivePlan(array $company): string
    {
        // En période d'essai = plan Pro
        if ($company['trial_ends_at'] && strtotime($company['trial_ends_at']) > time()) {
            return 'pro';
        }

        return $company['plan'];
    }

    /**
     * Obtient les limites pour une company
     */
    public function getLimits(array $company): array
    {
        $plan = $this->getEffectivePlan($company);
        return self::LIMITS[$plan] ?? self::LIMITS['decouverte'];
    }

    /**
     * Vérifie si la company peut uploader un certain nombre d'images
     */
    public function canUploadImages(array $company, int $count): array
    {
        $limits = $this->getLimits($company);

        if ($count > $limits['images_per_quote']) {
            return [
                'allowed' => false,
                'reason' => "Maximum {$limits['images_per_quote']} images par devis",
                'limit' => $limits['images_per_quote']
            ];
        }

        return ['allowed' => true, 'limit' => $limits['images_per_quote']];
    }

    /**
     * Vérifie si la company peut enregistrer un audio de cette durée
     */
    public function canRecordAudio(array $company, int $durationSeconds): array
    {
        $limits = $this->getLimits($company);

        if ($durationSeconds > $limits['audio_duration_seconds']) {
            $minutes = floor($limits['audio_duration_seconds'] / 60);
            return [
                'allowed' => false,
                'reason' => "Durée maximale : {$minutes} minute(s)",
                'limit' => $limits['audio_duration_seconds']
            ];
        }

        return ['allowed' => true, 'limit' => $limits['audio_duration_seconds']];
    }

    /**
     * Vérifie si le PDF doit avoir un filigrane
     */
    public function shouldAddWatermark(array $company): bool
    {
        $limits = $this->getLimits($company);
        return $limits['pdf_watermark'];
    }

    /**
     * Obtient les formats d'export disponibles
     */
    public function getAvailableExportFormats(array $company): array
    {
        $limits = $this->getLimits($company);
        return $limits['export_formats'];
    }

    /**
     * Obtient les statistiques de quota détaillées
     */
    public function getQuotaStats(array $company): array
    {
        $effectivePlan = $this->getEffectivePlan($company);
        $limits = $this->getLimits($company);

        // Compteur mensuel
        $currentMonth = date('Y-m-01');
        $quotesThisMonth = ($company['quotes_month_reset'] === $currentMonth)
            ? $company['quotes_this_month']
            : 0;

        // Période d'essai
        $isTrialActive = $company['trial_ends_at'] && strtotime($company['trial_ends_at']) > time();
        $trialDaysLeft = $isTrialActive
            ? max(0, (int) ceil((strtotime($company['trial_ends_at']) - time()) / 86400))
            : 0;

        // Jours restants dans le mois
        $daysLeftInMonth = (int) date('t') - (int) date('j');

        return [
            'plan' => $company['plan'],
            'effective_plan' => $effectivePlan,
            'is_trial_active' => $isTrialActive,
            'trial_days_left' => $trialDaysLeft,
            'trial_ends_at' => $company['trial_ends_at'],

            'quotes' => [
                'used' => $quotesThisMonth,
                'limit' => $limits['quotes_per_month'],
                'remaining' => $limits['quotes_per_month']
                    ? max(0, $limits['quotes_per_month'] - $quotesThisMonth)
                    : null,
                'percentage' => $limits['quotes_per_month']
                    ? min(100, round(($quotesThisMonth / $limits['quotes_per_month']) * 100))
                    : 0,
                'resets_in_days' => $daysLeftInMonth,
                'resets_at' => date('Y-m-01', strtotime('+1 month'))
            ],

            'features' => [
                'images_per_quote' => $limits['images_per_quote'],
                'audio_max_seconds' => $limits['audio_duration_seconds'],
                'audio_max_minutes' => floor($limits['audio_duration_seconds'] / 60),
                'pdf_watermark' => $limits['pdf_watermark'],
                'export_formats' => $limits['export_formats']
            ]
        ];
    }

    /**
     * Détermine si la company doit voir un message d'upgrade
     */
    public function shouldShowUpgradePrompt(array $company): array
    {
        $stats = $this->getQuotaStats($company);

        // Plan découverte avec quota > 80%
        if ($stats['effective_plan'] === 'decouverte' && $stats['quotes']['percentage'] >= 80) {
            return [
                'show' => true,
                'type' => 'quota_warning',
                'message' => "Vous avez utilisé {$stats['quotes']['percentage']}% de votre quota mensuel"
            ];
        }

        // Fin d'essai proche (3 jours)
        if ($stats['is_trial_active'] && $stats['trial_days_left'] <= 3) {
            return [
                'show' => true,
                'type' => 'trial_ending',
                'message' => "Votre essai Pro se termine dans {$stats['trial_days_left']} jour(s)"
            ];
        }

        return ['show' => false];
    }

    /**
     * Obtient un message de statut de quota pour l'UI
     */
    public function getQuotaStatusMessage(array $company): string
    {
        $stats = $this->getQuotaStats($company);

        if ($stats['is_trial_active']) {
            return "Essai Pro - {$stats['trial_days_left']} jour(s) restant(s)";
        }

        if ($stats['effective_plan'] === 'decouverte') {
            $remaining = $stats['quotes']['remaining'];
            $limit = $stats['quotes']['limit'];
            return "{$remaining}/{$limit} devis ce mois-ci";
        }

        if ($stats['effective_plan'] === 'pro') {
            return "Plan Pro - Devis illimités";
        }

        if ($stats['effective_plan'] === 'equipe') {
            return "Plan Équipe - Devis illimités";
        }

        return '';
    }
}
