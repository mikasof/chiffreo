<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * AGENT "PDF"
 * Génération de PDF propre à partir du JSON de devis
 */
class QuotePdfRenderer
{
    private Dompdf $dompdf;

    public function __construct()
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isFontSubsettingEnabled', true);

        $this->dompdf = new Dompdf($options);
    }

    /**
     * Génère le PDF du devis
     *
     * @param array $quote Données du devis (avec totaux calculés)
     * @param string $quoteRef Référence unique du devis
     * @return string Contenu binaire du PDF
     */
    public function render(array $quote, string $quoteRef): string
    {
        $html = $this->generateHtml($quote, $quoteRef);

        $this->dompdf->loadHtml($html);
        $this->dompdf->setPaper('A4', 'portrait');
        $this->dompdf->render();

        return $this->dompdf->output();
    }

    /**
     * Génère le HTML du devis pour conversion PDF
     */
    private function generateHtml(array $quote, string $quoteRef): string
    {
        $chantier = $quote['chantier'] ?? [];
        $taches = $quote['taches'] ?? [];
        $lignes = $quote['lignes'] ?? [];
        $totaux = $quote['totaux'] ?? [];
        $questions = $quote['questions_a_poser'] ?? [];
        $exclusions = $quote['exclusions'] ?? [];

        $date = date('d/m/Y');
        $validite = date('d/m/Y', strtotime('+30 days'));

        // Grouper les lignes par catégorie
        $lignesMateriel = array_filter($lignes, fn($l) => $l['categorie'] === 'materiel');
        $lignesMO = array_filter($lignes, fn($l) => $l['categorie'] === 'main_oeuvre');
        $lignesForfait = array_filter($lignes, fn($l) => $l['categorie'] === 'forfait');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Devis {$quoteRef}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #1a1a1a;
        }

        .page {
            padding: 20mm 15mm;
        }

        /* En-tête */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 25px;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 15px;
        }

        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: right;
        }

        .company-name {
            font-size: 18pt;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }

        .company-info {
            font-size: 9pt;
            color: #666;
        }

        .quote-title {
            font-size: 16pt;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 8px;
        }

        .quote-meta {
            font-size: 9pt;
            color: #666;
        }

        .quote-ref {
            font-size: 11pt;
            font-weight: bold;
            color: #2563eb;
        }

        /* Sections */
        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 11pt;
            font-weight: bold;
            color: #2563eb;
            background: #eff6ff;
            padding: 8px 12px;
            margin-bottom: 10px;
            border-left: 4px solid #2563eb;
        }

        .section-content {
            padding: 0 12px;
        }

        /* Périmètre et hypothèses */
        .perimetre {
            font-size: 10pt;
            margin-bottom: 10px;
        }

        .hypotheses {
            list-style: none;
            padding-left: 0;
        }

        .hypotheses li {
            padding: 3px 0 3px 15px;
            position: relative;
            font-size: 9pt;
            color: #555;
        }

        .hypotheses li:before {
            content: "•";
            position: absolute;
            left: 0;
            color: #2563eb;
        }

        /* Tâches */
        .task {
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px dotted #ddd;
        }

        .task:last-child {
            border-bottom: none;
        }

        .task-header {
            display: table;
            width: 100%;
        }

        .task-num {
            display: table-cell;
            width: 25px;
            font-weight: bold;
            color: #2563eb;
        }

        .task-title {
            display: table-cell;
            font-weight: bold;
        }

        .task-duration {
            display: table-cell;
            width: 80px;
            text-align: right;
            color: #666;
            font-size: 9pt;
        }

        .task-details {
            margin-left: 25px;
            font-size: 9pt;
            color: #555;
            margin-top: 3px;
        }

        /* Tableaux de lignes */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 9pt;
        }

        th {
            background: #f1f5f9;
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #cbd5e1;
            font-size: 8pt;
            text-transform: uppercase;
            color: #475569;
        }

        th.right, td.right {
            text-align: right;
        }

        th.center, td.center {
            text-align: center;
        }

        td {
            padding: 6px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        tr:nth-child(even) {
            background: #f8fafc;
        }

        .category-header {
            background: #e2e8f0;
            font-weight: bold;
            color: #334155;
        }

        .category-header td {
            padding: 6px 8px;
            border-bottom: 1px solid #cbd5e1;
        }

        /* Totaux */
        .totaux {
            width: 280px;
            margin-left: auto;
            margin-top: 15px;
        }

        .totaux table {
            margin-bottom: 0;
        }

        .totaux td {
            padding: 5px 8px;
        }

        .totaux .label {
            text-align: right;
            color: #555;
        }

        .totaux .value {
            text-align: right;
            font-weight: bold;
            width: 100px;
        }

        .totaux .total-ttc {
            background: #2563eb;
            color: white;
            font-size: 12pt;
        }

        .totaux .total-ttc td {
            padding: 10px 8px;
        }

        /* Questions */
        .questions {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 4px;
            padding: 12px;
            margin-top: 15px;
        }

        .questions-title {
            font-weight: bold;
            color: #b45309;
            margin-bottom: 8px;
        }

        .question-item {
            margin-bottom: 6px;
            padding-left: 15px;
            position: relative;
            font-size: 9pt;
        }

        .question-item:before {
            content: "?";
            position: absolute;
            left: 0;
            color: #b45309;
            font-weight: bold;
        }

        /* Exclusions */
        .exclusions {
            font-size: 9pt;
            color: #666;
        }

        .exclusions ul {
            list-style: none;
            padding-left: 0;
        }

        .exclusions li {
            padding: 2px 0 2px 15px;
            position: relative;
        }

        .exclusions li:before {
            content: "✗";
            position: absolute;
            left: 0;
            color: #dc2626;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            font-size: 8pt;
            color: #666;
        }

        .conditions {
            margin-bottom: 10px;
        }

        .signature-zone {
            margin-top: 20px;
            display: table;
            width: 100%;
        }

        .signature-box {
            display: table-cell;
            width: 45%;
            vertical-align: top;
        }

        .signature-box.right {
            text-align: right;
        }

        .signature-label {
            font-size: 9pt;
            margin-bottom: 5px;
        }

        .signature-line {
            border-bottom: 1px solid #333;
            height: 60px;
            margin-top: 10px;
        }

        .page-break {
            page-break-after: always;
        }

        /* TVA remarque */
        .tva-note {
            font-size: 8pt;
            color: #666;
            font-style: italic;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- En-tête -->
        <div class="header">
            <div class="header-left">
                <div class="company-name">[Votre Entreprise]</div>
                <div class="company-info">
                    [Adresse]<br>
                    Tél: [Téléphone]<br>
                    Email: [Email]<br>
                    SIRET: [SIRET]
                </div>
            </div>
            <div class="header-right">
                <div class="quote-title">DEVIS</div>
                <div class="quote-meta">
                    <span class="quote-ref">Réf: {$quoteRef}</span><br>
                    Date: {$date}<br>
                    Validité: {$validite}
                </div>
            </div>
        </div>

        <!-- Chantier -->
        <div class="section">
            <div class="section-title">{$this->escape($chantier['titre'] ?? 'Devis travaux électriques')}</div>
            <div class="section-content">
HTML;

        if (!empty($chantier['localisation'])) {
            $html .= '<p><strong>Lieu :</strong> ' . $this->escape($chantier['localisation']) . '</p>';
        }

        $html .= '<p class="perimetre"><strong>Périmètre :</strong> ' . $this->escape($chantier['perimetre'] ?? '') . '</p>';

        if (!empty($chantier['hypotheses'])) {
            $html .= '<p><strong>Hypothèses retenues :</strong></p><ul class="hypotheses">';
            foreach ($chantier['hypotheses'] as $hyp) {
                $html .= '<li>' . $this->escape($hyp) . '</li>';
            }
            $html .= '</ul>';
        }

        $html .= '</div></div>';

        // Tâches
        if (!empty($taches)) {
            $html .= '<div class="section">';
            $html .= '<div class="section-title">Travaux à réaliser</div>';
            $html .= '<div class="section-content">';

            foreach ($taches as $tache) {
                $html .= '<div class="task">';
                $html .= '<div class="task-header">';
                $html .= '<span class="task-num">' . $tache['ordre'] . '.</span>';
                $html .= '<span class="task-title">' . $this->escape($tache['titre']) . '</span>';
                $html .= '<span class="task-duration">~' . $tache['duree_estimee_h'] . 'h</span>';
                $html .= '</div>';
                $html .= '<div class="task-details">' . $this->escape($tache['details']) . '</div>';
                $html .= '</div>';
            }

            $html .= '</div></div>';
        }

        // Tableau des lignes
        $html .= '<div class="section">';
        $html .= '<div class="section-title">Détail du devis</div>';
        $html .= '<div class="section-content">';

        $html .= '<table>';
        $html .= '<thead><tr>';
        $html .= '<th style="width:45%">Désignation</th>';
        $html .= '<th class="center" style="width:10%">Qté</th>';
        $html .= '<th class="center" style="width:10%">Unité</th>';
        $html .= '<th class="right" style="width:15%">P.U. HT</th>';
        $html .= '<th class="right" style="width:20%">Total HT</th>';
        $html .= '</tr></thead>';
        $html .= '<tbody>';

        // Matériel
        if (!empty($lignesMateriel)) {
            $html .= '<tr class="category-header"><td colspan="5">FOURNITURES</td></tr>';
            foreach ($lignesMateriel as $ligne) {
                $html .= $this->renderLigneRow($ligne);
            }
        }

        // Main d'oeuvre
        if (!empty($lignesMO)) {
            $html .= '<tr class="category-header"><td colspan="5">MAIN D\'ŒUVRE</td></tr>';
            foreach ($lignesMO as $ligne) {
                $html .= $this->renderLigneRow($ligne);
            }
        }

        // Forfaits
        if (!empty($lignesForfait)) {
            $html .= '<tr class="category-header"><td colspan="5">FORFAITS</td></tr>';
            foreach ($lignesForfait as $ligne) {
                $html .= $this->renderLigneRow($ligne);
            }
        }

        $html .= '</tbody></table>';

        // Totaux
        $html .= '<div class="totaux"><table>';
        $html .= '<tr><td class="label">Total Fournitures HT</td><td class="value">' . $this->formatPrice($totaux['materiel_ht'] ?? 0) . '</td></tr>';
        $html .= '<tr><td class="label">Total Main d\'œuvre HT</td><td class="value">' . $this->formatPrice($totaux['main_oeuvre_ht'] ?? 0) . '</td></tr>';

        if (($totaux['forfait_ht'] ?? 0) > 0) {
            $html .= '<tr><td class="label">Total Forfaits HT</td><td class="value">' . $this->formatPrice($totaux['forfait_ht']) . '</td></tr>';
        }

        $html .= '<tr style="border-top: 2px solid #333;"><td class="label"><strong>Total HT</strong></td><td class="value"><strong>' . $this->formatPrice($totaux['total_ht'] ?? 0) . '</strong></td></tr>';
        $html .= '<tr><td class="label">TVA ' . ($totaux['taux_tva'] ?? 20) . '%</td><td class="value">' . $this->formatPrice($totaux['montant_tva'] ?? 0) . '</td></tr>';
        $html .= '<tr class="total-ttc"><td class="label">TOTAL TTC</td><td class="value">' . $this->formatPrice($totaux['total_ttc'] ?? 0) . '</td></tr>';
        $html .= '</table></div>';

        if (!empty($quote['remarque_tva'])) {
            $html .= '<p class="tva-note">' . $this->escape($quote['remarque_tva']) . '</p>';
        }

        $html .= '</div></div>';

        // Questions à poser
        if (!empty($questions)) {
            $html .= '<div class="questions">';
            $html .= '<div class="questions-title">⚠ Points à préciser avant intervention</div>';
            foreach ($questions as $q) {
                $html .= '<div class="question-item">' . $this->escape($q['question']) . '</div>';
            }
            $html .= '</div>';
        }

        // Exclusions
        if (!empty($exclusions)) {
            $html .= '<div class="section exclusions">';
            $html .= '<p><strong>Non compris dans ce devis :</strong></p>';
            $html .= '<ul>';
            foreach ($exclusions as $excl) {
                $html .= '<li>' . $this->escape($excl) . '</li>';
            }
            $html .= '</ul></div>';
        }

        // Footer
        $html .= <<<HTML
        <div class="footer">
            <div class="conditions">
                <strong>Conditions :</strong> Devis valable 30 jours. Règlement : 40% à la commande, solde à réception des travaux.
                Garantie pièces et main d'œuvre selon conditions fabricants.
            </div>

            <div class="signature-zone">
                <div class="signature-box">
                    <div class="signature-label">L'entreprise</div>
                    <div class="signature-line"></div>
                </div>
                <div class="signature-box right">
                    <div class="signature-label">Le client (bon pour accord)</div>
                    <div class="signature-line"></div>
                    <p style="font-size: 8pt; margin-top: 5px;">Date et signature</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
HTML;

        return $html;
    }

    /**
     * Rendu d'une ligne du tableau
     */
    private function renderLigneRow(array $ligne): string
    {
        $html = '<tr>';
        $html .= '<td>' . $this->escape($ligne['designation']);
        if (!empty($ligne['commentaire'])) {
            $html .= '<br><small style="color:#666;">' . $this->escape($ligne['commentaire']) . '</small>';
        }
        $html .= '</td>';
        $html .= '<td class="center">' . $ligne['quantite'] . '</td>';
        $html .= '<td class="center">' . $this->escape($ligne['unite']) . '</td>';
        $html .= '<td class="right">' . $this->formatPrice($ligne['prix_unitaire_ht']) . '</td>';
        $html .= '<td class="right">' . $this->formatPrice($ligne['total_ligne_ht']) . '</td>';
        $html .= '</tr>';

        return $html;
    }

    /**
     * Échappe les caractères HTML
     */
    private function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Formate un prix en euros
     */
    private function formatPrice(float $amount): string
    {
        return number_format($amount, 2, ',', ' ') . ' €';
    }

    /**
     * Sauvegarde le PDF dans un fichier
     */
    public function saveToFile(array $quote, string $quoteRef, string $filePath): void
    {
        $pdf = $this->render($quote, $quoteRef);
        file_put_contents($filePath, $pdf);
    }
}
