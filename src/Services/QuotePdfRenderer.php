<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Génération de PDF de devis avec design Chiffreo
 */
class QuotePdfRenderer
{
    private Dompdf $dompdf;
    private string $basePath;

    // Couleurs Chiffreo
    private const COLOR_PRIMARY = '#1B4D3E';      // Vert foncé
    private const COLOR_PRIMARY_LIGHT = '#2D6A4F'; // Vert moyen
    private const COLOR_ACCENT = '#EC9824';        // Orange
    private const COLOR_BG = '#F5F5F0';           // Fond crème
    private const COLOR_TEXT = '#1a1a1a';
    private const COLOR_TEXT_LIGHT = '#666666';

    public function __construct()
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('isRemoteEnabled', true); // Pour charger les images
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isFontSubsettingEnabled', true);
        $options->set('chroot', realpath(__DIR__ . '/../../public'));

        $this->dompdf = new Dompdf($options);
        $this->basePath = realpath(__DIR__ . '/../../public');
    }

    /**
     * Génère le PDF du devis
     *
     * @param array $quote Données du devis
     * @param string $quoteRef Référence unique
     * @param array $company Données de l'entreprise
     * @param array $client Données du client
     * @param array $chantierInfo Adresse du chantier
     * @return string Contenu binaire du PDF
     */
    public function render(
        array $quote,
        string $quoteRef,
        array $company = [],
        array $client = [],
        array $chantierInfo = []
    ): string {
        $html = $this->generateHtml($quote, $quoteRef, $company, $client, $chantierInfo);

        $this->dompdf->loadHtml($html);
        $this->dompdf->setPaper('A4', 'portrait');
        $this->dompdf->render();

        return $this->dompdf->output();
    }

    /**
     * Génère le HTML du devis
     */
    private function generateHtml(
        array $quote,
        string $quoteRef,
        array $company,
        array $client,
        array $chantierInfo
    ): string {
        $chantier = $quote['chantier'] ?? [];
        $taches = $quote['taches'] ?? [];
        $lignes = $quote['lignes'] ?? [];
        $totaux = $quote['totaux'] ?? [];
        $questions = $quote['questions_a_poser'] ?? [];
        $exclusions = $quote['exclusions'] ?? [];

        $date = date('d/m/Y');
        $validite = date('d/m/Y', strtotime('+30 days'));

        // Grouper les lignes par catégorie
        $lignesMateriel = array_filter($lignes, fn($l) => ($l['categorie'] ?? '') === 'materiel');
        $lignesMO = array_filter($lignes, fn($l) => ($l['categorie'] ?? '') === 'main_oeuvre');
        $lignesForfait = array_filter($lignes, fn($l) => ($l['categorie'] ?? '') === 'forfait');

        // Logo
        $logoHtml = '';
        if (!empty($company['logo_path'])) {
            $logoPath = $this->basePath . '/' . $company['logo_path'];
            if (file_exists($logoPath)) {
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoMime = mime_content_type($logoPath);
                $logoHtml = '<img src="data:' . $logoMime . ';base64,' . $logoData . '" class="logo" alt="Logo">';
            }
        }

        // Infos entreprise
        $companyName = $this->escape($company['name'] ?? 'Votre Entreprise');
        $companyAddress = $this->escape($company['address_line1'] ?? '');
        if (!empty($company['address_line2'])) {
            $companyAddress .= '<br>' . $this->escape($company['address_line2']);
        }
        $companyAddress .= '<br>' . $this->escape($company['postal_code'] ?? '') . ' ' . $this->escape($company['city'] ?? '');
        $companyPhone = $this->escape($company['phone'] ?? '');
        $companyEmail = $this->escape($company['email_contact'] ?? $company['email'] ?? '');
        $companySiret = $this->escape($company['siret'] ?? '');
        $companyVat = $this->escape($company['vat_number'] ?? '');
        $companyLegalForm = $this->escape($company['legal_form'] ?? '');
        $companyCapital = $company['capital'] ?? '';
        $companyRcs = '';
        if (!empty($company['rcs_number']) && !empty($company['rcs_city'])) {
            $companyRcs = 'RCS ' . $this->escape($company['rcs_city']) . ' ' . $this->escape($company['rcs_number']);
        }

        // Infos client
        $clientName = $this->escape($client['nom'] ?? $client['name'] ?? '');
        $clientCompany = $this->escape($client['societe'] ?? $client['company'] ?? '');
        $clientAddress = $this->escape($client['adresse'] ?? $client['address'] ?? '');
        $clientEmail = $this->escape($client['email'] ?? '');
        $clientPhone = $this->escape($client['telephone'] ?? $client['phone'] ?? '');

        // Adresse chantier
        $chantierAddr = '';
        if (!empty($chantierInfo['adresse']) || !empty($chantierInfo['ville'])) {
            $chantierAddr = $this->escape($chantierInfo['adresse'] ?? '');
            if (!empty($chantierInfo['codePostal']) || !empty($chantierInfo['ville'])) {
                $chantierAddr .= '<br>' . $this->escape($chantierInfo['codePostal'] ?? '') . ' ' . $this->escape($chantierInfo['ville'] ?? '');
            }
        }

        // Assurance
        $insuranceInfo = '';
        if (!empty($company['insurance_name'])) {
            $insuranceInfo = 'Assurance décennale : ' . $this->escape($company['insurance_name']);
            if (!empty($company['insurance_number'])) {
                $insuranceInfo .= ' - Police n° ' . $this->escape($company['insurance_number']);
            }
            if (!empty($company['insurance_coverage'])) {
                $insuranceInfo .= ' - ' . $this->escape($company['insurance_coverage']);
            }
        }

        $colorPrimary = self::COLOR_PRIMARY;
        $colorPrimaryLight = self::COLOR_PRIMARY_LIGHT;
        $colorAccent = self::COLOR_ACCENT;
        $colorBg = self::COLOR_BG;

        $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Devis {$quoteRef}</title>
    <style>
        @page {
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.4;
            color: #1a1a1a;
            background: white;
            padding: 0;
            margin: 0;
        }

        .page-wrapper {
            padding: 50px 45px 60px 45px;
        }

        /* === EN-TÊTE === */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .header-left {
            display: table-cell;
            width: 55%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 45%;
            vertical-align: top;
            text-align: right;
        }

        .logo {
            max-width: 140px;
            max-height: 60px;
            margin-bottom: 8px;
        }

        .company-name {
            font-size: 14pt;
            font-weight: bold;
            color: {$colorPrimary};
            margin-bottom: 4px;
        }

        .company-info {
            font-size: 8pt;
            color: #555;
            line-height: 1.5;
        }

        .quote-badge {
            display: inline-block;
            background: {$colorPrimary};
            color: white;
            padding: 8px 20px;
            font-size: 14pt;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .quote-meta {
            font-size: 9pt;
            color: #333;
            text-align: right;
        }

        .quote-ref {
            font-size: 11pt;
            font-weight: bold;
            color: {$colorPrimary};
        }

        /* === BLOC CLIENT / CHANTIER === */
        .info-blocks {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .info-block {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 15px;
        }

        .info-block:last-child {
            padding-right: 0;
            padding-left: 15px;
        }

        .info-block-title {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            color: {$colorPrimary};
            padding: 6px 10px;
            background: {$colorBg};
            border-left: 3px solid {$colorPrimary};
            margin-bottom: 8px;
        }

        .info-block-content {
            padding: 0 10px;
            font-size: 9pt;
        }

        .info-block-content strong {
            color: #333;
        }

        /* === SECTIONS === */
        .section {
            margin-bottom: 18px;
        }

        .section-title {
            font-size: 10pt;
            font-weight: bold;
            color: white;
            background: {$colorPrimary};
            padding: 8px 12px;
            margin-bottom: 10px;
        }

        .section-subtitle {
            font-size: 9pt;
            font-weight: bold;
            color: {$colorPrimary};
            padding: 6px 12px;
            background: {$colorBg};
            border-left: 3px solid {$colorAccent};
            margin-bottom: 8px;
        }

        .section-content {
            padding: 0 12px;
        }

        /* === PÉRIMÈTRE === */
        .perimetre {
            font-size: 9pt;
            margin-bottom: 8px;
            padding: 10px;
            background: {$colorBg};
            border-radius: 4px;
        }

        .hypotheses {
            list-style: none;
            padding-left: 0;
            margin-top: 8px;
        }

        .hypotheses li {
            padding: 3px 0 3px 18px;
            position: relative;
            font-size: 8pt;
            color: #555;
        }

        .hypotheses li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: {$colorPrimary};
            font-weight: bold;
        }

        /* === TÂCHES === */
        .task {
            margin-bottom: 10px;
            padding: 8px 10px;
            background: {$colorBg};
            border-left: 3px solid {$colorAccent};
        }

        .task-header {
            display: table;
            width: 100%;
        }

        .task-num {
            display: table-cell;
            width: 25px;
            font-weight: bold;
            color: {$colorAccent};
            font-size: 10pt;
        }

        .task-title {
            display: table-cell;
            font-weight: bold;
            color: {$colorPrimary};
        }

        .task-duration {
            display: table-cell;
            width: 60px;
            text-align: right;
            color: #666;
            font-size: 8pt;
        }

        .task-details {
            margin-left: 25px;
            font-size: 8pt;
            color: #555;
            margin-top: 4px;
        }

        /* === TABLEAUX === */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }

        th {
            background: {$colorPrimary};
            color: white;
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            font-size: 7pt;
            text-transform: uppercase;
        }

        th.right, td.right {
            text-align: right;
        }

        th.center, td.center {
            text-align: center;
        }

        td {
            padding: 6px;
            border-bottom: 1px solid #e5e5e5;
            vertical-align: top;
        }

        tr:nth-child(even) td {
            background: #fafafa;
        }

        .category-header {
            background: {$colorBg} !important;
        }

        .category-header td {
            font-weight: bold;
            color: {$colorPrimary};
            padding: 6px 8px;
            border-bottom: 2px solid {$colorPrimary};
            font-size: 8pt;
        }

        /* === TOTAUX === */
        .totaux-wrapper {
            margin-top: 15px;
            text-align: right;
        }

        .totaux {
            display: inline-block;
            width: 260px;
            text-align: left;
        }

        .totaux table {
            margin-bottom: 0;
        }

        .totaux td {
            padding: 5px 10px;
            background: transparent !important;
        }

        .totaux .label {
            text-align: right;
            color: #555;
        }

        .totaux .value {
            text-align: right;
            font-weight: bold;
            width: 90px;
        }

        .totaux .subtotal td {
            border-top: 2px solid {$colorPrimary};
        }

        .totaux .total-ttc {
            background: {$colorPrimary} !important;
        }

        .totaux .total-ttc td {
            color: white;
            font-size: 11pt;
            padding: 10px;
        }

        /* === QUESTIONS === */
        .questions {
            background: #FEF3C7;
            border: 1px solid {$colorAccent};
            padding: 12px;
            margin-top: 15px;
        }

        .questions-title {
            font-weight: bold;
            color: #92400E;
            margin-bottom: 8px;
            font-size: 9pt;
        }

        .question-item {
            margin-bottom: 5px;
            padding-left: 18px;
            position: relative;
            font-size: 8pt;
            color: #78350F;
        }

        .question-item:before {
            content: "?";
            position: absolute;
            left: 0;
            color: {$colorAccent};
            font-weight: bold;
        }

        /* === EXCLUSIONS === */
        .exclusions {
            font-size: 8pt;
            color: #666;
            margin-top: 12px;
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

        /* === FOOTER === */
        .footer {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 2px solid {$colorPrimary};
        }

        .conditions {
            font-size: 7pt;
            color: #555;
            margin-bottom: 8px;
            line-height: 1.5;
        }

        .insurance {
            font-size: 7pt;
            color: #555;
            margin-bottom: 15px;
            padding: 6px 10px;
            background: {$colorBg};
        }

        .signature-zone {
            display: table;
            width: 100%;
            margin-top: 20px;
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
            font-size: 8pt;
            font-weight: bold;
            color: {$colorPrimary};
            margin-bottom: 5px;
        }

        .signature-line {
            border-bottom: 1px solid #333;
            height: 50px;
            margin-top: 8px;
        }

        .mention-bon-pour {
            font-size: 7pt;
            color: #666;
            margin-top: 5px;
        }

        /* === TVA === */
        .tva-note {
            font-size: 7pt;
            color: #666;
            font-style: italic;
            margin-top: 5px;
            text-align: right;
        }

        .legal-footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 6pt;
            color: #888;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="page-wrapper">
    <!-- EN-TÊTE -->
    <div class="header">
        <div class="header-left">
            {$logoHtml}
            <div class="company-name">{$companyName}</div>
            <div class="company-info">
                {$companyAddress}<br>
HTML;

        if ($companyPhone) {
            $html .= "Tél : {$companyPhone}<br>";
        }
        if ($companyEmail) {
            $html .= "Email : {$companyEmail}<br>";
        }
        if ($companySiret) {
            $html .= "SIRET : {$companySiret}";
            if ($companyLegalForm) {
                $html .= " - {$companyLegalForm}";
            }
            $html .= "<br>";
        }
        if ($companyVat) {
            $html .= "TVA : {$companyVat}<br>";
        }
        if ($companyRcs) {
            $html .= "{$companyRcs}";
            if ($companyCapital) {
                $html .= " - Capital : {$companyCapital} €";
            }
        }

        $html .= <<<HTML
            </div>
        </div>
        <div class="header-right">
            <div class="quote-badge">DEVIS</div>
            <div class="quote-meta">
                <span class="quote-ref">{$quoteRef}</span><br>
                Date : {$date}<br>
                Validité : {$validite}
            </div>
        </div>
    </div>

    <!-- BLOCS CLIENT / CHANTIER -->
    <div class="info-blocks">
        <div class="info-block">
            <div class="info-block-title">Client</div>
            <div class="info-block-content">
HTML;

        if ($clientCompany) {
            $html .= "<strong>{$clientCompany}</strong><br>";
        }
        if ($clientName) {
            $html .= "{$clientName}<br>";
        }
        if ($clientAddress) {
            $html .= "{$clientAddress}<br>";
        }
        if ($clientPhone) {
            $html .= "Tél : {$clientPhone}<br>";
        }
        if ($clientEmail) {
            $html .= "Email : {$clientEmail}";
        }

        $html .= <<<HTML
            </div>
        </div>
        <div class="info-block">
            <div class="info-block-title">Lieu d'intervention</div>
            <div class="info-block-content">
HTML;

        if ($chantierAddr) {
            $html .= $chantierAddr;
        } elseif ($clientAddress) {
            $html .= "<em>Même adresse que le client</em>";
        } else {
            $html .= "<em>À définir</em>";
        }

        $html .= <<<HTML
            </div>
        </div>
    </div>

    <!-- CHANTIER -->
    <div class="section">
        <div class="section-title">{$this->escape($chantier['titre'] ?? 'Travaux électriques')}</div>
        <div class="section-content">
            <div class="perimetre">
                <strong>Périmètre des travaux :</strong><br>
                {$this->escape($chantier['perimetre'] ?? '')}
            </div>
HTML;

        if (!empty($chantier['hypotheses'])) {
            $html .= '<p style="font-size:8pt;font-weight:bold;color:#333;margin-top:8px;">Hypothèses retenues :</p><ul class="hypotheses">';
            foreach ($chantier['hypotheses'] as $hyp) {
                $html .= '<li>' . $this->escape($hyp) . '</li>';
            }
            $html .= '</ul>';
        }

        $html .= '</div></div>';

        // Tâches
        if (!empty($taches)) {
            $html .= '<div class="section">';
            $html .= '<div class="section-subtitle">Travaux à réaliser</div>';
            $html .= '<div class="section-content">';

            foreach ($taches as $tache) {
                $html .= '<div class="task">';
                $html .= '<div class="task-header">';
                $html .= '<span class="task-num">' . ($tache['ordre'] ?? '') . '</span>';
                $html .= '<span class="task-title">' . $this->escape($tache['titre'] ?? '') . '</span>';
                if (!empty($tache['duree_estimee_h'])) {
                    $html .= '<span class="task-duration">~' . $tache['duree_estimee_h'] . 'h</span>';
                }
                $html .= '</div>';
                if (!empty($tache['details'])) {
                    $html .= '<div class="task-details">' . $this->escape($tache['details']) . '</div>';
                }
                $html .= '</div>';
            }

            $html .= '</div></div>';
        }

        // Tableau des lignes
        $html .= '<div class="section">';
        $html .= '<div class="section-title">Détail chiffré</div>';
        $html .= '<div class="section-content">';

        $html .= '<table>';
        $html .= '<thead><tr>';
        $html .= '<th style="width:48%">Désignation</th>';
        $html .= '<th class="center" style="width:8%">Qté</th>';
        $html .= '<th class="center" style="width:10%">Unité</th>';
        $html .= '<th class="right" style="width:14%">P.U. HT</th>';
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
        $html .= '<div class="totaux-wrapper"><div class="totaux"><table>';

        if (($totaux['materiel_ht'] ?? 0) > 0) {
            $html .= '<tr><td class="label">Fournitures HT</td><td class="value">' . $this->formatPrice($totaux['materiel_ht']) . '</td></tr>';
        }
        if (($totaux['main_oeuvre_ht'] ?? 0) > 0) {
            $html .= '<tr><td class="label">Main d\'œuvre HT</td><td class="value">' . $this->formatPrice($totaux['main_oeuvre_ht']) . '</td></tr>';
        }
        if (($totaux['forfait_ht'] ?? 0) > 0) {
            $html .= '<tr><td class="label">Forfaits HT</td><td class="value">' . $this->formatPrice($totaux['forfait_ht']) . '</td></tr>';
        }

        $html .= '<tr class="subtotal"><td class="label"><strong>Total HT</strong></td><td class="value"><strong>' . $this->formatPrice($totaux['total_ht'] ?? 0) . '</strong></td></tr>';
        $html .= '<tr><td class="label">TVA ' . ($totaux['taux_tva'] ?? 20) . '%</td><td class="value">' . $this->formatPrice($totaux['montant_tva'] ?? 0) . '</td></tr>';
        $html .= '<tr class="total-ttc"><td class="label">TOTAL TTC</td><td class="value">' . $this->formatPrice($totaux['total_ttc'] ?? 0) . '</td></tr>';
        $html .= '</table></div></div>';

        if (!empty($quote['remarque_tva'])) {
            $html .= '<p class="tva-note">' . $this->escape($quote['remarque_tva']) . '</p>';
        }

        $html .= '</div></div>';

        // Questions
        if (!empty($questions)) {
            $html .= '<div class="questions">';
            $html .= '<div class="questions-title">⚠ Points à préciser avant intervention</div>';
            foreach ($questions as $q) {
                $question = is_array($q) ? ($q['question'] ?? '') : $q;
                $html .= '<div class="question-item">' . $this->escape($question) . '</div>';
            }
            $html .= '</div>';
        }

        // Exclusions
        if (!empty($exclusions)) {
            $html .= '<div class="exclusions">';
            $html .= '<p><strong>Non compris dans ce devis :</strong></p>';
            $html .= '<ul>';
            foreach ($exclusions as $excl) {
                $html .= '<li>' . $this->escape($excl) . '</li>';
            }
            $html .= '</ul></div>';
        }

        // Footer
        $html .= '<div class="footer">';

        if ($insuranceInfo) {
            $html .= '<div class="insurance">' . $insuranceInfo . '</div>';
        }

        $html .= <<<HTML
            <div class="conditions">
                <strong>Conditions :</strong> Devis gratuit valable 30 jours.
                Règlement : 40% à la commande, solde à réception des travaux.
                Garantie pièces et main d'œuvre selon conditions fabricants.
                TVA applicable au jour de la facturation.
            </div>

            <div class="signature-zone">
                <div class="signature-box">
                    <div class="signature-label">L'entreprise</div>
                    <div class="signature-line"></div>
                </div>
                <div class="signature-box right">
                    <div class="signature-label">Le client</div>
                    <div class="signature-line"></div>
                    <div class="mention-bon-pour">
                        Bon pour accord<br>
                        Date et signature précédées de la mention<br>
                        "Lu et approuvé"
                    </div>
                </div>
            </div>
HTML;

        // Mentions légales en pied de page
        $legalParts = [];
        if ($companyName && $companyName !== 'Votre Entreprise') {
            $legalParts[] = $companyName;
        }
        if ($companyLegalForm) {
            $legalParts[] = $companyLegalForm;
        }
        if ($companyCapital) {
            $legalParts[] = "Capital {$companyCapital} €";
        }
        if ($companyRcs) {
            $legalParts[] = $companyRcs;
        }

        if (!empty($legalParts)) {
            $html .= '<div class="legal-footer">' . implode(' - ', $legalParts) . '</div>';
        }

        $html .= '</div>'; // fin footer
        $html .= '</div>'; // fin page-wrapper

        $html .= <<<HTML
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
        $html .= '<td>' . $this->escape($ligne['designation'] ?? '');

        // Afficher marque et référence si disponibles
        $marque = $ligne['marque'] ?? null;
        $reference = $ligne['reference'] ?? null;
        if ($marque || $reference) {
            $refParts = [];
            if ($marque) {
                $refParts[] = $marque;
            }
            if ($reference) {
                $refParts[] = 'Réf: ' . $reference;
            }
            $html .= '<br><small style="color:#1B4D3E;font-size:7pt;font-weight:500;">' . $this->escape(implode(' - ', $refParts)) . '</small>';
        }

        if (!empty($ligne['commentaire'])) {
            $html .= '<br><small style="color:#888;font-size:7pt;">' . $this->escape($ligne['commentaire']) . '</small>';
        }
        $html .= '</td>';
        $html .= '<td class="center">' . ($ligne['quantite'] ?? '') . '</td>';
        $html .= '<td class="center">' . $this->escape($ligne['unite'] ?? '') . '</td>';
        $html .= '<td class="right">' . $this->formatPrice($ligne['prix_unitaire_ht'] ?? 0) . '</td>';
        $html .= '<td class="right"><strong>' . $this->formatPrice($ligne['total_ligne_ht'] ?? 0) . '</strong></td>';
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
    public function saveToFile(
        array $quote,
        string $quoteRef,
        string $filePath,
        array $company = [],
        array $client = [],
        array $chantierInfo = []
    ): void {
        $pdf = $this->render($quote, $quoteRef, $company, $client, $chantierInfo);
        file_put_contents($filePath, $pdf);
    }
}
