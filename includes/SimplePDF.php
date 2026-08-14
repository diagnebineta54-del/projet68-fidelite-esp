<?php
/**
 * SimplePDF — générateur PDF minimaliste, 100% PHP natif, sans dépendance.
 *
 * Le cahier des charges recommande TCPDF, FPDF ou DOMPDF (bibliothèques qui
 * s'installent via Composer). Pour que ce projet fonctionne immédiatement
 * sur n'importe quel poste XAMPP même sans accès Internet/Composer, cette
 * classe reproduit le strict nécessaire (texte, tableaux, pagination,
 * téléchargement) directement au format PDF.
 *
 * Pour utiliser TCPDF/FPDF/DOMPDF à la place : installez la bibliothèque
 * via Composer, puis remplacez uniquement l'utilisation de SimplePDF dans
 * les fichiers du dossier /export/. Toute la logique métier (les requêtes
 * SQL qui préparent les données) reste inchangée.
 */
class SimplePDF
{
    private array $pages = [];
    private string $contenuPage = '';
    private float $largeur = 595.28; // A4 portrait en points (72 dpi)
    private float $hauteur = 841.89;
    private float $margeGauche = 40;
    private float $y = 0;

    public function __construct()
    {
        $this->y = $this->hauteur - 50;
    }

    public function ajouterPage(): void
    {
        $this->pages[] = $this->contenuPage;
        $this->contenuPage = '';
        $this->y = $this->hauteur - 50;
    }

    private function echapper(string $texte): string
    {
        $texte = @iconv('UTF-8', 'CP1252//TRANSLIT', $texte);
        if ($texte === false) $texte = '';
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $texte);
    }

    private function verifierSautDePage(int $besoin): void
    {
        if ($this->y - $besoin < 45) {
            $this->ajouterPage();
        }
    }

    public function titre(string $texte): void
    {
        $this->verifierSautDePage(30);
        $this->contenuPage .= "BT /F2 16 Tf {$this->margeGauche} {$this->y} Td (" . $this->echapper($texte) . ") Tj ET\n";
        $this->y -= 26;
    }

    public function sousTitre(string $texte): void
    {
        $this->verifierSautDePage(22);
        $this->contenuPage .= "BT /F2 12 Tf {$this->margeGauche} {$this->y} Td (" . $this->echapper($texte) . ") Tj ET\n";
        $this->y -= 18;
    }

    public function texte(string $texte, int $taille = 10): void
    {
        $this->verifierSautDePage(16);
        $this->contenuPage .= "BT /F1 {$taille} Tf {$this->margeGauche} {$this->y} Td (" . $this->echapper($texte) . ") Tj ET\n";
        $this->y -= 15;
    }

    public function ligneHorizontale(): void
    {
        $this->verifierSautDePage(10);
        $droite = $this->largeur - $this->margeGauche;
        $yLigne = $this->y + 10;
        $this->contenuPage .= "{$this->margeGauche} {$yLigne} m {$droite} {$yLigne} l S\n";
        $this->y -= 6;
    }

    /** Ligne de tableau : colonnes = [decalageX => texte] */
    public function ligneColonnes(array $colonnes, int $taille = 9, bool $gras = false): void
    {
        $this->verifierSautDePage(14);
        $police = $gras ? 'F2' : 'F1';
        foreach ($colonnes as $x => $texte) {
            $posX = $this->margeGauche + (float)$x;
            $this->contenuPage .= "BT /$police $taille Tf {$posX} {$this->y} Td (" . $this->echapper((string)$texte) . ") Tj ET\n";
        }
        $this->y -= 14;
    }

    public function espace(int $hauteur = 8): void
    {
        $this->y -= $hauteur;
    }

    /**
     * Génère le PDF final.
     * @param string $destination 'D' = téléchargement direct, 'S' = retourne la chaîne binaire
     */
    public function sortie(string $nomFichier = 'document.pdf', string $destination = 'D')
    {
        $this->pages[] = $this->contenuPage; // dernière page en cours
        $nbPages = count($this->pages);

        // Numérotation séquentielle : 1=Catalog 2=Pages 3=F1 4=F2, puis (Page,Contents) par page
        $refsPages = [];
        $numObjet = 5;
        foreach ($this->pages as $c) {
            $refsPages[] = $numObjet;
            $numObjet += 2;
        }
        $totalObjets = $numObjet - 1;

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];

        $ajouterObjet = function (int $num, string $corps) use (&$pdf, &$offsets) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "{$num} 0 obj\n{$corps}\nendobj\n";
        };

        $ajouterObjet(1, "<< /Type /Catalog /Pages 2 0 R >>");
        $kids = implode(' ', array_map(fn($n) => "$n 0 R", $refsPages));
        $ajouterObjet(2, "<< /Type /Pages /Kids [{$kids}] /Count {$nbPages} >>");
        $ajouterObjet(3, "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>");
        $ajouterObjet(4, "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>");

        foreach ($this->pages as $i => $contenuPage) {
            $numPage = $refsPages[$i];
            $numContenu = $numPage + 1;
            $flux = "q " . $contenuPage . " Q";
            $ajouterObjet($numPage,
                "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$this->largeur} {$this->hauteur}] "
                . "/Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents {$numContenu} 0 R >>");
            $ajouterObjet($numContenu, "<< /Length " . strlen($flux) . " >>\nstream\n{$flux}\nendstream");
        }

        $offsetXref = strlen($pdf);
        $pdf .= "xref\n0 " . ($totalObjets + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($n = 1; $n <= $totalObjets; $n++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$n]);
        }
        $pdf .= "trailer\n<< /Size " . ($totalObjets + 1) . " /Root 1 0 R >>\nstartxref\n{$offsetXref}\n%%EOF";

        if ($destination === 'D') {
            if (ob_get_level()) ob_end_clean();
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $nomFichier . '"');
            header('Content-Length: ' . strlen($pdf));
            echo $pdf;
            exit;
        }
        return $pdf;
    }
}
