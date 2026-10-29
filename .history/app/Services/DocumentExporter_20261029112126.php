<?php
namespace App\Services;

use App\Models\Activity;
use App\Models\Agent;
use App\Support\Logo;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Cell as CellStyle;
use PhpOffice\PhpWord\Style\Table as TableStyle;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentExporter
{
    public function __construct(private DocumentService $docs) {}

    public const TITLES = [
        'decision' => 'Décision',
        'note' => 'Note de service',
        'presence' => 'Feuille de présence',
        'etat' => 'État de vacations',
        'decompte' => 'État de décompte',
    ];

    private function send(string $tmp, string $name): BinaryFileResponse
    {
        return response()->download($tmp, $name)->deleteFileAfterSend(true);
    }
    private function tmp(): string { return tempnam(sys_get_temp_dir(), 'rep'); }
    private function nf($x): string { return number_format((float) $x, 0, ',', ' '); }
    private function showLogo(array $s): bool { return ($s['show_logo'] ?? 'OUI') === 'OUI'; }

    /** Nombre de lignes estimé pour un texte dans une colonne de $cpl caractères (retour à la ligne). */
    private function lines(?string $text, int $cpl): int
    {
        return max(1, (int) ceil(mb_strlen((string) $text) / max(1, $cpl * 0.85)));
    }

    /* ============================ WORD ============================ */
    public function word(string $type, array $d): BinaryFileResponse
    {
        $w = new PhpWord();
        $w->setDefaultFontName('Times New Roman');
        $w->setDefaultFontSize(11);
        $w->addTableStyle('grid', ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 50, 'layout' => TableStyle::LAYOUT_FIXED]);
        match ($type) {
            'decision' => $this->wordDecision($w, $d),
            'note' => $this->wordNote($w, $d),
            'presence' => $this->eachPart($d, fn($p) => $this->wordPresence($w, $p)),
            'etat' => $this->eachPart($d, fn($p) => $this->wordEtat($w, $p)),
            'decompte' => $this->eachPart($d, fn($p) => $this->wordDecompte($w, $p)),
        };
        $tmp = $this->tmp();
        IOFactory::createWriter($w, 'Word2007')->save($tmp);
        return $this->send($tmp, "{$type}-{$d['file']}.docx");
    }

    /** Applique $fn à chaque partie du document : toute l'activité, ou un sous-groupe à la fois (quarantaine). */
    private function eachPart(array $d, callable $fn): void
    {
        foreach ($d['parts'] as $p) $fn($p);
    }

    /** Nom d'onglet Excel (31 caractères max, sans caractères interdits) avec le sous-groupe. */
    private function sheetName(string $base, array $d): string
    {
        $n = ($d['sub'] ?? null) ? $d['sub'] . ' - ' . $base : $base;
        return mb_substr(str_replace([':', '\\', '/', '?', '*', '[', ']'], ' ', $n), 0, 31);
    }

    /** Logo de Madagascar, centré, TOUT EN HAUT du document. */
    private function wordLogoTop($sec, array $s): void
    {
        if (!$this->showLogo($s)) return;
        $h = 53;
        $sec->addImage(Logo::file(), ['width' => (int) round($h * Logo::ratio()), 'height' => $h, 'alignment' => 'center']);
        if (!Logo::isReal()) {   // drapeau dessiné : on ajoute République + devise
            $sec->addText($s['republic'], ['bold' => true, 'size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            $sec->addText($s['motto'], ['italic' => true, 'size' => 8], ['alignment' => 'center']);
        }
    }

    /** Bloc « considérant » à gauche (ministère, secrétariat, direction…) + titre à droite. */
    private function wordHeaderBlock($sec, array $s, array $d, string $titleKey, string $l1Key, string $l2Key, int $titleSize): void
    {
        $t = $sec->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF']);
        $r = $t->addRow();
        $lc = $r->addCell(4300);
        foreach (preg_split('/\R/', $s['doc_header']) as $ln) $lc->addText($ln, ['size' => 9], ['alignment' => 'center']);
        $rc = $r->addCell(5900);
        $rc->addText($this->docs->tpl($s[$titleKey], $d), ['bold' => true, 'size' => $titleSize], ['alignment' => 'center', 'spaceAfter' => 80]);
        $rc->addText($this->docs->tpl($s[$l1Key], $d), ['bold' => true], ['alignment' => 'center']);
        if (trim($s[$l2Key]) !== '') $rc->addText($this->docs->tpl($s[$l2Key], $d), ['size' => 9, 'italic' => true], ['alignment' => 'center']);
        $sec->addTextBreak(1);
    }

    private function wordDecision(PhpWord $w, array $d): void
    {
        $s = $d['s'];
        $sec = $w->addSection(['marginTop' => 600, 'marginBottom' => 700, 'marginLeft' => 900, 'marginRight' => 900]);
        $this->wordLogoTop($sec, $s);
        $head = $sec->addTable();
        $r = $head->addRow();
        $c = $r->addCell(3800);
        $c->addText($s['ministry'], [], ['alignment' => 'center']);
        $c->addText('--------------', [], ['alignment' => 'center']);
        $tc = $r->addCell(6000);
        $tc->addText($this->docs->decisionTitle($d), ['bold' => true], ['alignment' => 'both']);
        $tc->addText($this->docs->decisionSession($d), ['bold' => true], ['alignment' => 'center', 'spaceBefore' => 160]);
        $sec->addTextBreak(1);
        $sec->addText($s['authority'], ['bold' => true], ['alignment' => 'center']);
        foreach ($d['visas'] as $v) $sec->addText($v->text, ['size' => 10], ['alignment' => 'both', 'spaceAfter' => 0]);
        $sec->addPageBreak();
        $sec->addText('DECIDE :', ['bold' => true]);
        $sec->addText($this->docs->article1($d), [], ['alignment' => 'both']);
                      $nPages = count($d['dpages']);
        foreach ($d['dpages'] as $pIdx => $pg) {
            foreach ($pg['runs'] as $run) {
                if ($run['label']) $sec->addText(mb_strtoupper($run['label']), ['bold' => true], ['spaceBefore' => 120]);
                $nIt = count($run['items']);
                $t = $sec->addTable('grid');
                $h = $t->addRow(null, ['tblHeader' => true]);
                foreach ([[900, 'N° d’ordre'], [600, 'n°'], [4700, 'NOM ET PRENOMS'], [1200, 'IM'], [2400, 'LOCALITE DE SERVICE']] as [$wd, $tx])
                    $h->addCell($wd)->addText($tx, ['bold' => true, 'size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
                foreach ($run['items'] as $k => $it) {
                    // les derniers noms restent avec l'arrêtage, les articles et la signature (keepNext)
                    $kn = $run['final'] && $k >= $nIt - $run['keep'];
                    $row = $t->addRow(null, ['cantSplit' => true]);
                    $row->addCell(900)->addText((string) $it['g'], ['size' => 10], ['alignment' => 'center', 'spaceAfter' => 0, 'keepNext' => $kn]);
                    $row->addCell(600)->addText((string) ($d['quarantine'] ? $it['l'] : $k + 1), ['size' => 10], ['alignment' => 'center', 'spaceAfter' => 0, 'keepNext' => $kn]);
                    $row->addCell(4700)->addText($it['ag']->name, ['size' => 10], ['spaceAfter' => 0, 'keepNext' => $kn]);
                    $row->addCell(1200)->addText((string) $it['ag']->im, ['size' => 10], ['alignment' => 'center', 'spaceAfter' => 0, 'keepNext' => $kn]);
                    $row->addCell(2400)->addText((string) $it['ag']->locality, ['size' => 10], ['alignment' => 'center', 'spaceAfter' => 0, 'keepNext' => $kn]);
                }
                $sec->addText($run['tail'], ['bold' => true, 'italic' => true, 'size' => 10], ['keepNext' => $run['final']]);
            }
            if ($pIdx < $nPages - 1) $sec->addPageBreak();
        }
        foreach ($this->docs->articles($d) as $art) {
            $sec->addText($art['label'] . ' : ' . $art['text'], [], ['alignment' => 'both', 'keepNext' => true]);
            if ($art['budget']) {
                $sec->addText('Cette indemnité est imputable sur la ligne budgétaire :', [], ['keepNext' => true]);
                $b = $sec->addTable('grid');
                $r1 = $b->addRow(null, ['cantSplit' => true]);
                $r2 = $b->addRow(null, ['cantSplit' => true]);
                foreach ([['Mission', 'budget_mission'], ['Indicateur d’objectif', 'budget_indicator'], ['Section', 'budget_section'], ['Compte', 'budget_account'], ['Code ORDSEC', 'budget_ordsec']] as [$lab, $key]) {
                    $r1->addCell(1960)->addText($lab, ['bold' => true, 'size' => 10], ['alignment' => 'center', 'spaceAfter' => 0, 'keepNext' => true]);
                    $r2->addCell(1960)->addText($s[$key], ['size' => 10], ['alignment' => 'center', 'spaceAfter' => 0, 'keepNext' => true]);
                }
                $sec->addTextBreak(1, null, ['keepNext' => true]);
            }
        }
        $sec->addText($this->docs->signDate($d), [], ['alignment' => 'right', 'keepNext' => true]);
        $sec->addText($s['signatory'], ['bold' => true], ['alignment' => 'right']);
    }

    /** Note de service — modèle note_vacation_CAP_2026.docx ; logo tout en haut (ou dans la colonne de droite si « COTE »). */
    private function wordNote(PhpWord $w, array $d): void
    {
        $s = $d['s'];
        $a = $d['a'];
        $top = ($s['note_logo_position'] ?? 'HAUT') === 'HAUT';
        $font = trim($s['note_font']) ?: 'Book Antiqua';
        $w->setDefaultFontName($font);
        $w->setDefaultFontSize(12);
        $w->setDefaultParagraphStyle(['spaceAfter' => 0, 'spaceBefore' => 0]);

        $sec = $w->addSection(['marginTop' => 586, 'marginBottom' => 1440, 'marginLeft' => 1080, 'marginRight' => 1080]);
        if ($top) $this->wordLogoTop($sec, $s);
        $head = $sec->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF', 'alignment' => 'center']);
        $row = $head->addRow();
        $left = $row->addCell(4815);
        foreach (preg_split('/\R/', $s['note_header_left']) as $line) $left->addText($line, [], ['alignment' => 'center']);
        $right = $row->addCell(4921);
        if (!$top && $this->showLogo($s)) {
            $right->addImage(Logo::file(), ['width' => 94, 'height' => 53, 'alignment' => 'center']);
        }
        $right->addText($s['note_title'], ['bold' => true], ['alignment' => 'center', 'spaceBefore' => $top ? 0 : 120, 'spaceAfter' => 120]);
        $right->addText($this->docs->tpl(($a->note_number ?: $s['note_number']) . ' ' . $s['note_subject'], $d), ['bold' => true], ['alignment' => 'both']);
        $right->addText('SESSION ' . $s['session'], ['bold' => true], ['alignment' => 'center', 'spaceBefore' => 60]);

        $sec->addTextBreak(1);
        $sec->addText($this->docs->tpl($s['note_intro'], $d), [], ['alignment' => 'both', 'lineHeight' => 1.5, 'indentation' => ['firstLine' => 708], 'spaceAfter' => 120]);

        $t = $sec->addTable(['borderSize' => 4, 'borderColor' => '000000', 'cellMarginLeft' => 70, 'cellMarginRight' => 70, 'layout' => TableStyle::LAYOUT_FIXED]);
        $cols = [[610, 'N°'], [5091, 'NOM ET PRENOMS'], [1394, 'IM'], [2823, 'LOCALITE']];
        $h = $t->addRow(500, ['tblHeader' => true, 'cantSplit' => true]);
        foreach ($cols as [$wd, $tx]) $h->addCell($wd, ['valign' => 'center'])->addText($tx, ['bold' => true], ['alignment' => 'center']);
        // lignes : h = titre de sous-groupe, a = agent, f = arrêtage de sous-groupe
        $nrows = [];
        if ($d['quarantine']) {
            $num = 0;
            foreach ($d['groups'] as $g) {
                $nrows[] = ['h', mb_strtoupper($g['label'])];
                foreach ($g['rows'] as $ag) $nrows[] = ['a', ++$num, $ag];
                $nrows[] = ['f', $this->docs->groupTail($g)];
            }
        } else {
            foreach ($d['agents'] as $i => $ag) $nrows[] = ['a', $i + 1, $ag];
        }
        // les 2 dernières lignes restent avec l'arrêtage, la date et la signature (keepNext) : jamais l'arrêtage seul sur une page
        $keepFrom = max(0, count($nrows) - 2);
        foreach ($nrows as $idx => $r) {
            $kn = $idx >= $keepFrom;
            $pr = fn(array $extra = []) => $extra + ['keepNext' => $kn];
            if ($r[0] === 'h') {
                $hr = $t->addRow(400, ['cantSplit' => true]);
                $hr->addCell(9918, ['gridSpan' => 4, 'valign' => 'center', 'bgColor' => 'EEEEEE'])->addText($r[1], ['bold' => true], $pr());
            } elseif ($r[0] === 'f') {
                $tr = $t->addRow(400, ['cantSplit' => true]);
                $tr->addCell(9918, ['gridSpan' => 4, 'valign' => 'center'])->addText($r[1], ['bold' => true, 'italic' => true], $pr());
            } else {
                $row = $t->addRow(500, ['cantSplit' => true]);
                $row->addCell(610, ['valign' => 'center'])->addText((string) $r[1], [], $pr(['alignment' => 'right']));
                $row->addCell(5091, ['valign' => 'center'])->addText($r[2]->name, [], $pr());
                $row->addCell(1394, ['valign' => 'center'])->addText((string) $r[2]->im, [], $pr(['alignment' => 'center']));
                $row->addCell(2823, ['valign' => 'center'])->addText((string) $r[2]->locality, [], $pr(['alignment' => 'center']));
            }
        }
        $sec->addTextBreak(1, null, ['keepNext' => true]);
        $sec->addText($this->docs->tpl($s['note_closing'], $d), [], ['alignment' => 'both', 'lineHeight' => 1.5, 'keepNext' => true]);
        $sec->addText($this->docs->tpl($s['note_place_date'], $d), [], ['alignment' => 'both', 'lineHeight' => 1.5, 'keepNext' => true, 'indentation' => ['left' => 4956, 'firstLine' => 708]]);
        $signLines = preg_split('/\R/', $s['note_signature']);
        foreach ($signLines as $si => $line) {
            $sec->addText($line, [], ['alignment' => 'center', 'lineHeight' => 1.5, 'keepNext' => $si < count($signLines) - 1, 'indentation' => ['left' => 4956, 'firstLine' => 708]]);
        }
    }

    /** Fiche de présence : paysage, logo tout en haut, 15 colonnes max par feuille. */
    private function wordPresence(PhpWord $w, array $d): void
    {
        $s = $d['s'];
        $w->setDefaultParagraphStyle(['spaceAfter' => 0, 'spaceBefore' => 0]);
        $total = 15400;
        $idw = max(25, min(60, (int) $s['presence_id_width']));
        $wN = 120 + 70 * strlen((string) max(1, $d['n']));   // N° : juste la place des chiffres (330 pour 3 chiffres)
        $wIm = 1100; $wLoc = 1800;
        $wName = max((int) round($total * $idw / 100) - $wN - $wIm - $wLoc, $wIm + $wLoc + 200);   // nom >= IM + localité
        $idTotal = $wN + $wName + $wIm + $wLoc;
        $rowH = max(18, (int) $s['presence_row_height']) * 20;
        $left = preg_split('/\R/', $s['presence_header_left']);
        $nSeries = count($d['series']);

        foreach ($d['series'] as $si => $sr) {
            $cols = $sr['cols'];
            $dayW = max(300, intdiv($total - $idTotal, $cols));
            $sec = $w->addSection(['orientation' => 'landscape', 'marginTop' => 450, 'marginBottom' => 500, 'marginLeft' => 720, 'marginRight' => 720]);

            $this->wordLogoTop($sec, $s);                       // 1) logo tout en haut
            $head = $sec->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF']);
            $r = $head->addRow();
            $lc = $r->addCell(6200);
            foreach ($left as $ln) $lc->addText($ln, ['size' => 9], ['alignment' => 'center']);   // 2) bloc ministère à gauche
            $rc = $r->addCell(9200);
            $rc->addText($this->docs->tpl($s['presence_title'], $d), ['bold' => true, 'size' => 10], ['alignment' => 'center']);
            $rc->addText($this->docs->tpl($s['presence_line1'], $d), ['bold' => true, 'size' => 10], ['alignment' => 'center']);
            if (trim($s['presence_line2']) !== '') $rc->addText($this->docs->tpl($s['presence_line2'], $d), ['size' => 9], ['alignment' => 'center']);
            if ($nSeries > 1) $rc->addText('Feuille ' . ($si + 1) . "/$nSeries — jours {$sr['from']} à {$sr['to']}", ['italic' => true, 'size' => 9], ['alignment' => 'center']);
            $sec->addTextBreak(1);

            $t = $sec->addTable('grid');
            $h1 = $t->addRow(null, ['tblHeader' => true]);
            foreach ([[$wN, 'N°'], [$wName, 'NOM ET PRENOMS'], [$wIm, 'IM'], [$wLoc, 'LOCALITE DE SERVICE']] as [$wd, $tx])
                $h1->addCell($wd, ['vMerge' => 'restart', 'valign' => 'center'])->addText($tx, ['bold' => true, 'size' => $wd === $wN ? 7 : 9], ['alignment' => 'center']);
            // foreach ([[$wN, 'N°'], [$wName, 'NOM ET PRENOMS'], [$wIm, 'IM'], [$wLoc, 'LOCALITE DE SERVICE']] as [$wd, $tx])
            //     $h1->addCell($wd, ['vMerge' => 'restart', 'valign' => 'center'])->addText($tx, ['bold' => true, 'size' => 9], ['alignment' => 'center']);
            $h1->addCell($dayW * $cols, ['gridSpan' => $cols, 'valign' => 'center'])->addText('EMARGEMENT', ['bold' => true, 'size' => 9], ['alignment' => 'center']);
            $h2 = $t->addRow(1050, ['tblHeader' => true]);
            foreach ([$wN, $wName, $wIm, $wLoc] as $wd) $h2->addCell($wd, ['vMerge' => 'continue']);
            for ($k = 0; $k < $cols; $k++) {
                $c = $h2->addCell($dayW, ['textDirection' => CellStyle::TEXT_DIR_BTLR, 'valign' => 'center']);
                $c->addText($sr['labels'][$k], ['bold' => true, 'size' => 8], ['alignment' => 'center']);
            }
            foreach ($d['agents'] as $i => $ag) {
                //$row = $t->addRow($rowH, ['cantSplit' => true]);
                 $row = $t->addRow($rowH, ['cantSplit' => true, 'exactHeight' => false]);   // hauteur MINIMALE : la ligne grandit si le nom est long
                //$row->addCell($wN, ['valign' => 'center'])->addText((string) ($i + 1), ['size' => 9], ['alignment' => 'center']);
                                $row->addCell($wN, ['valign' => 'center'])->addText((string) ($i + 1), ['size' => 7], ['alignment' => 'center']);
                $row->addCell($wName, ['valign' => 'center'])->addText($ag->name, ['size' => 9]);
                $row->addCell($wIm, ['valign' => 'center'])->addText($this->docs->fmtIm($ag->im), ['size' => 9], ['alignment' => 'center']);
                $row->addCell($wLoc, ['valign' => 'center'])->addText((string) $ag->locality, ['size' => 9], ['alignment' => 'center']);
                for ($k = 0; $k < $cols; $k++) $row->addCell($dayW)->addText('', ['size' => 9]);
            }
            $sec->addTextBreak(1);
            
            //$sec->addText($s['place'] . ', le ' . ($s['decision_date'] ?: ''), [], ['alignment' => 'left']);
        }
    }

    private function wordEtat(PhpWord $w, array $d): void
    {
        $a = $d['a'];
        $s = $d['s'];
        $w->setDefaultParagraphStyle(['spaceAfter' => 0, 'spaceBefore' => 0]);
        $sec = $w->addSection(['marginTop' => 500, 'marginBottom' => 700, 'marginLeft' => 800, 'marginRight' => 800]);
        $this->wordLogoTop($sec, $s);
        $this->wordHeaderBlock($sec, $s, $d, 'etat_title', 'etat_line1', 'etat_line2', 15);
        $t = $sec->addTable('grid');
        $cols = [[500, 'N°'], [3200, 'NOM ET PRENOMS'], [1000, 'IM'], [1500, 'LOCALITE'], [600, 'Jours'], [1000, 'Taux/jour'], [1200, 'Net à payer (Ar)'], [1300, 'ÉMARGEMENT']];
        $ir = $t->addRow(null, ['tblHeader' => true]);   // IRSA en haut du tableau
        $ir->addCell(10300, ['gridSpan' => 8, 'valign' => 'center'])->addText(
            'Taux IRSA : ' . rtrim(rtrim(number_format($d['irsaRate'], 2, ',', ''), '0'), ',') . ' % — montant net à payer (IRSA déjà déduit)',
            ['bold' => true, 'size' => 9], ['alignment' => 'right']);
        $h = $t->addRow(null, ['tblHeader' => true]);
        foreach ($cols as [$wd, $tx]) $h->addCell($wd, ['valign' => 'center'])->addText($tx, ['bold' => true, 'size' => 9], ['alignment' => 'center']);
        foreach ($d['agents'] as $i => $ag) {
            $r = $t->addRow(380, ['cantSplit' => true]);
            $vals = [(string) ($i + 1), $ag->name, $this->docs->fmtIm($ag->im), (string) $ag->locality, (string) $a->days, $this->nf($a->rate), $this->nf($d['net']), ''];
            foreach ($cols as $k => [$wd]) {
                $al = $k === 1 ? 'left' : (in_array($k, [5, 6]) ? 'right' : 'center');
                $r->addCell($wd, ['valign' => 'center'])->addText($vals[$k], ['size' => 9], ['alignment' => $al]);
            }
        }
        $sec->addTextBreak(1);
        $sec->addText("TOTAL : {$d['n']} agent(s) — " . $this->nf($d['totalNet']) . ' Ar (net, IRSA déduit)', ['bold' => true]);
        $sec->addText($this->docs->etatFooter($d), ['italic' => true]);
        $sec->addText($this->docs->signDate($d), [], ['alignment' => 'right']);
    }

    /** État de décompte : logo en haut, bloc ministère, puis brut / IRSA / net avec total de page et report. */
    private function wordDecompte(PhpWord $w, array $d): void
    {
        $s = $d['s'];
        $a = $d['a'];
        $w->setDefaultParagraphStyle(['spaceAfter' => 0, 'spaceBefore' => 0]);
        $sec = $w->addSection(['marginTop' => 500, 'marginBottom' => 700, 'marginLeft' => 800, 'marginRight' => 800]);
        $this->wordLogoTop($sec, $s);
        $this->wordHeaderBlock($sec, $s, $d, 'decompte_title', 'decompte_line1', 'decompte_line2', 14);

        $cols = [[450, 'N°'], [3200, 'NOM ET PRENOMS'], [900, 'IM'], [1300, 'LOCALITE'], [500, 'Jours'], [850, 'Taux/jour'], [1100, 'Montant brut'], [900, 'IRSA ' . $this->docs->rate($d['irsaRate']) . ' %'], [1100, 'Net à percevoir']];
        $span6 = $cols[0][0] + $cols[1][0] + $cols[2][0] + $cols[3][0] + $cols[4][0] + $cols[5][0];
        $nChunks = count($d['dec']);
        foreach ($d['dec'] as $ci => $ch) {
            $t = $sec->addTable('grid');
            $h = $t->addRow(null, ['tblHeader' => true]);
            foreach ($cols as [$wd, $tx]) $h->addCell($wd, ['valign' => 'center'])->addText($tx, ['bold' => true, 'size' => 8], ['alignment' => 'center']);
            $sum = function (string $label, array $v, bool $bold = true) use ($t, $span6, $cols) {
                $r = $t->addRow(340);
                $r->addCell($span6, ['gridSpan' => 6, 'valign' => 'center'])->addText($label, ['bold' => $bold, 'size' => 9], ['alignment' => 'right']);
                foreach ([6 => 'g', 7 => 'i', 8 => 'n'] as $idx => $key)
                    $r->addCell($cols[$idx][0], ['valign' => 'center'])->addText($this->nf($v[$key]), ['bold' => $bold, 'size' => 9], ['alignment' => 'right']);
            };
            if (!$ch['first']) $sum('REPORT (page précédente)', $ch['report']);
            foreach ($ch['rows'] as $k => $ag) {
                $r = $t->addRow(340, ['cantSplit' => true]);
                $vals = [(string) ($ch['offset'] + $k + 1), $ag->name, $this->docs->fmtIm($ag->im), (string) $ag->locality, (string) $a->days, $this->nf($a->rate), $this->nf($d['amount']), $this->nf($d['irsaAmt']), $this->nf($d['net'])];
                foreach ($cols as $j => [$wd]) {
                    $al = $j === 1 ? 'left' : ($j >= 5 ? 'right' : 'center');
                    $r->addCell($wd, ['valign' => 'center'])->addText($vals[$j], ['size' => 8.5], ['alignment' => $al]);
                }
            }
            $sum('TOTAL DE LA PAGE', $ch['page']);
            $sum($ci === $nChunks - 1 ? 'TOTAL GÉNÉRAL' : 'TOTAL À REPORTER', $ch['cum']);
            if ($ci < $nChunks - 1) $sec->addPageBreak();
        }
        $sec->addTextBreak(1);
        $sec->addText($this->docs->tpl($s['decompte_footer'], $d), ['italic' => true]);
        $sec->addText($this->docs->signDate($d), [], ['alignment' => 'right', 'spaceBefore' => 120]);
    }

    /* ============================ EXCEL ============================ */
    private function box($sh, string $range): void
    {
        $sh->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }
    private function head($sh, string $range): void
    {
        $sh->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
    }
    private function save(Spreadsheet $ss, string $name): BinaryFileResponse
    {
        $tmp = $this->tmp();
        (new Xlsx($ss))->save($tmp);
        return $this->send($tmp, $name);
    }
    private function setup($sh, bool $landscape): void
    {
        $sh->getPageSetup()->setOrientation($landscape ? PageSetup::ORIENTATION_LANDSCAPE : PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0);
        $sh->getPageSetup()->setFitToPage(true);
    }

    /** Logo centré tout en haut de la feuille. Réserve $rows lignes et retourne leur nombre (0 si désactivé). */
    private function logoTop(Worksheet $sh, array $s, array $widths, int $rows = 3): int
    {
        if (!$this->showLogo($s)) return 0;
        $h = 54;
        $wpx = (int) round($h * Logo::ratio());
        $px = array_map(fn($w) => (int) round($w * 7 + 5), $widths);
        $center = max(0, array_sum($px) / 2 - $wpx / 2);
        $acc = 0;
        $idx = 0;
        foreach ($px as $i => $p) {
            $idx = $i;
            if ($acc + $p > $center) break;
            $acc += $p;
        }
        $dr = new Drawing();
        $dr->setName('Logo');
        $dr->setPath(Logo::file());
        $dr->setHeight($h);
        $dr->setCoordinates(Coordinate::stringFromColumnIndex($idx + 1) . '1');
        $dr->setOffsetX((int) max(0, $center - $acc));
        $dr->setOffsetY(3);
        $dr->setWorksheet($sh);
        for ($r = 1; $r <= $rows; $r++) $sh->getRowDimension($r)->setRowHeight(20);
        return $rows;
    }

    /** Logo tout en haut ; si c'est le drapeau dessiné, ajoute les lignes République + devise. Retourne le nombre de lignes utilisées. */
    private function excelTop(Worksheet $sh, array $s, array $widths, string $lastCol): int
    {
        $o = $this->logoTop($sh, $s, $widths, 3);
        if ($o > 0 && !Logo::isReal()) {
            foreach ([$s['republic'], $s['motto']] as $k => $t) {
                $o++;
                $sh->setCellValue("A$o", $t);
                $sh->mergeCells("A$o:{$lastCol}$o");
                $sh->getStyle("A$o")->getAlignment()->setHorizontal('center');
                if ($k === 0) $sh->getStyle("A$o")->getFont()->setBold(true);
            }
        }
        return $o;
    }

    /** Bloc « considérant » à gauche + titre à droite. Retourne le nombre de lignes occupées. */
    private function excelHeaderBlock(Worksheet $sh, array $s, array $d, int $r, string $titleKey, string $l1Key, string $l2Key, string $leftEnd, string $rightStart, string $lastCol): int
    {
        $lines = preg_split('/\R/', $s['doc_header']);
        $n = max(count($lines), 6);
        foreach ($lines as $i => $ln) {
            $rr = $r + $i;
            $sh->setCellValue("A$rr", $ln);
            $sh->mergeCells("A$rr:{$leftEnd}$rr");
            $sh->getStyle("A$rr")->getAlignment()->setHorizontal('center');
            $sh->getStyle("A$rr")->getFont()->setSize(9);
        }
        $title = $this->docs->tpl($s[$titleKey], $d) . "\n" . $this->docs->tpl($s[$l1Key], $d);
        if (trim($s[$l2Key]) !== '') $title .= "\n" . $this->docs->tpl($s[$l2Key], $d);
        $sh->setCellValue("{$rightStart}$r", $title);
        $sh->mergeCells("{$rightStart}$r:{$lastCol}" . ($r + $n - 1));
        $sh->getStyle("{$rightStart}$r")->getAlignment()->setWrapText(true)->setHorizontal('center')->setVertical('center');
        $sh->getStyle("{$rightStart}$r")->getFont()->setBold(true)->setSize(12);
        return $n;
    }

    public function excel(string $type, array $d): BinaryFileResponse
    {
        $ss = new Spreadsheet();
        $ss->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        if ($type === 'decision') {
            $this->excelDecision($ss, $d);
        } else {
            foreach ($d['parts'] as $pi => $p) {   // une feuille (ou série de feuilles) par sous-groupe
                match ($type) {
                    'presence' => $this->excelPresence($ss, $p, $pi),
                    'decompte' => $this->excelDecompte($ss, $p, $pi),
                    default => $this->excelEtat($ss, $p, $pi),
                };
            }
            $ss->setActiveSheetIndex(0);
        }
        return $this->save($ss, "{$type}-{$d['file']}.xlsx");
    }

    private function excelDecision(Spreadsheet $ss, array $d): void
    {
        $sh = $ss->getActiveSheet();
        $s = $d['s'];
        $sh->setTitle('Décision');
        $widths = [10, 6, 48, 12, 26];
        foreach ($widths as $i => $wd) $sh->getColumnDimension(Coordinate::stringFromColumnIndex($i + 1))->setWidth($wd);
        $row = $this->excelTop($sh, $s, $widths, 'E') + 1;
        $sh->setCellValue("A$row", $s['ministry']); $sh->mergeCells("A$row:C$row"); $row++;
        $sh->setCellValue("A$row", $this->docs->decisionTitle($d)); $sh->mergeCells("A$row:E$row");
        $sh->getStyle("A$row")->getAlignment()->setWrapText(true)->setVertical('top');
        $sh->getStyle("A$row")->getFont()->setBold(true);
        $sh->getRowDimension($row)->setRowHeight(48);
        $row++;
        $sh->setCellValue("A$row", $this->docs->decisionSession($d)); $sh->mergeCells("A$row:E$row");
        $sh->getStyle("A$row")->getAlignment()->setHorizontal('center');
        $sh->getStyle("A$row")->getFont()->setBold(true);
        $row += 2;
        $sh->setCellValue("A$row", $s['authority']); $sh->mergeCells("A$row:E$row");
        $sh->getStyle("A$row")->getFont()->setBold(true); $row++;
        foreach ($d['visas'] as $v) {
            $sh->setCellValue("A$row", $v->text); $sh->mergeCells("A$row:E$row");
            $sh->getStyle("A$row")->getAlignment()->setWrapText(true)->setVertical('top');
            $sh->getRowDimension($row)->setRowHeight(13 * max(1, ceil(mb_strlen($v->text) / 105)) + 2);
            $row++;
        }
        $row++;
        $sh->setCellValue("A$row", 'DECIDE :'); $sh->getStyle("A$row")->getFont()->setBold(true); $row++;
        $sh->setCellValue("A$row", $this->docs->article1($d)); $sh->mergeCells("A$row:E$row");
        $sh->getStyle("A$row")->getAlignment()->setWrapText(true);
        $sh->getRowDimension($row)->setRowHeight(30);
        $row += 2;
        $start = $row;
        foreach (["N° d’ordre", 'n°', 'NOM ET PRENOMS', 'IM', 'LOCALITE DE SERVICE'] as $i => $t)
            $sh->setCellValue(Coordinate::stringFromColumnIndex($i + 1) . $row, $t);
        $this->head($sh, "A$row:E$row");
        $row++;
        $blocks = $d['quarantine']
            ? array_map(fn($g) => ['head' => mb_strtoupper($g['label']), 'chunks' => $g['chunks'], 'g' => $g], $d['groups'])
            : [['head' => null, 'chunks' => $d['chunks'], 'g' => null]];
        foreach ($blocks as $bk) {
            if ($bk['head']) {
                $sh->setCellValue("A$row", $bk['head']); $sh->mergeCells("A$row:E$row");
                $sh->getStyle("A$row")->getFont()->setBold(true);
                $sh->getStyle("A$row:E$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEEEEE');
                $row++;
            }
            $lastCh = count($bk['chunks']) - 1;
            foreach ($bk['chunks'] as $ci => $ch) {
                foreach ($ch['rows'] as $k => $ag) {
                    $sh->setCellValue("A$row", $ch['offset'] + $k + 1);
                    $sh->setCellValue("B$row", ($bk['g'] ? $ch['local'] : 0) + $k + 1);
                    $sh->setCellValue("C$row", $ag->name);
                    $sh->setCellValueExplicit("D$row", (string) $ag->im, DataType::TYPE_STRING);
                    $sh->setCellValue("E$row", (string) $ag->locality);
                    $sh->getStyle("A$row:E$row")->getAlignment()->setWrapText(true)->setVertical('center');
                    $sh->getStyle("A$row:B$row")->getAlignment()->setHorizontal('center');
                    $sh->getStyle("D$row:E$row")->getAlignment()->setHorizontal('center');
                    $sh->getRowDimension($row)->setRowHeight(max(16, 13 * max($this->lines($ag->name, 46), $this->lines($ag->locality, 24)) + 4));
                    $row++;
                }
                if ($ch['rows']->count()) {
                    $tail = ($bk['g'] && $ci === $lastCh) ? $this->docs->groupTail($bk['g']) : $this->docs->pageTail($ch);
                    $sh->setCellValue("A$row", $tail); $sh->mergeCells("A$row:E$row");
                    $sh->getStyle("A$row")->getFont()->setItalic(true)->setBold(true);
                    $row++;
                }
            }
        }
        $this->box($sh, "A$start:E" . ($row - 1));
        $row++;
        $sh->setCellValue("A$row", "ARRETEE LA PRESENTE DECISION AU NOMBRE DE {$d['nWords']} ({$d['n']}) AGENTS.");
        $sh->getStyle("A$row")->getFont()->setBold(true);
        $row += 2;
        foreach ($this->docs->articles($d) as $art) {
            $sh->setCellValue("A$row", $art['label'] . ' : ' . $art['text']); $sh->mergeCells("A$row:E$row");
            $sh->getStyle("A$row")->getAlignment()->setWrapText(true)->setVertical('top');
            $sh->getRowDimension($row)->setRowHeight(max(16, 15 * $this->lines($art['label'] . ' : ' . $art['text'], 100) + 2));
            $row++;
            if ($art['budget']) {
                $sh->setCellValue("A$row", 'Cette indemnité est imputable sur la ligne budgétaire :'); $row++;
                $sh->fromArray([['Mission', 'Indicateur d’objectif', 'Section', 'Compte', 'Code ORDSEC'],
                    [$s['budget_mission'], $s['budget_indicator'], $s['budget_section'], $s['budget_account'], $s['budget_ordsec']]], null, "A$row");
                $this->box($sh, "A$row:E" . ($row + 1));
                $sh->getStyle("A$row:E$row")->getFont()->setBold(true);
                $row += 3;
            }
        }
        $row++;
        $sh->setCellValue("A$row", $this->docs->signDate($d)); $sh->mergeCells("A$row:E$row");
        $sh->getStyle("A$row")->getAlignment()->setHorizontal('right'); $row++;
        $sh->setCellValue("A$row", $s['signatory']); $sh->mergeCells("A$row:E$row");
        $sh->getStyle("A$row")->getAlignment()->setHorizontal('right');
        $sh->getStyle("A$row")->getFont()->setBold(true);
        $this->setup($sh, false);
    }

    /** Présence : une feuille Excel par série de jours (15 colonnes max), logo tout en haut. */
    private function excelPresence(Spreadsheet $ss, array $d, int $pi = 0): void
    {
        $s = $d['s'];
        $rh = max(18, (int) $s['presence_row_height']);
        $lines = preg_split('/\R/', $s['presence_header_left']);
        $nSeries = count($d['series']);

        foreach ($d['series'] as $si => $sr) {
            $sh = ($pi === 0 && $si === 0) ? $ss->getActiveSheet() : $ss->createSheet();
            $sh->setTitle($this->sheetName($nSeries > 1 ? 'Feuille ' . ($si + 1) : 'Présence', $d));
            $cols = $sr['cols'];
            $first = 5;
            $last = Coordinate::stringFromColumnIndex(4 + $cols);
            $firstCol = Coordinate::stringFromColumnIndex($first);

            //$widths = [5, 42, 12, 22];
            $widths = [3, 42, 12, 22];   // N° la plus étroite ; nom (42) >= IM (12) + localité (22)  // N° : colonne la plus étroite
            for ($k = 0; $k < $cols; $k++) $widths[] = 8.5;
            foreach ($widths as $i => $wd) $sh->getColumnDimension(Coordinate::stringFromColumnIndex($i + 1))->setWidth($wd);

            $o = $this->excelTop($sh, $s, $widths, $last);       // 1) logo tout en haut
            $r0 = $o + 1;
            foreach ($lines as $i => $ln) {                      // 2) bloc ministère à gauche
                $r = $r0 + $i;
                $sh->setCellValue("A$r", $ln);
                $sh->mergeCells("A$r:D$r");
                $sh->getStyle("A$r")->getAlignment()->setHorizontal('center');
                $sh->getStyle("A$r")->getFont()->setSize(9);
            }
            $title = $this->docs->tpl($s['presence_title'], $d) . "\n" . $this->docs->tpl($s['presence_line1'], $d);   // titre à droite
            if (trim($s['presence_line2']) !== '') $title .= "\n" . $this->docs->tpl($s['presence_line2'], $d);
            if ($nSeries > 1) $title .= "\nFeuille " . ($si + 1) . "/$nSeries — jours {$sr['from']} à {$sr['to']}";
            $sh->setCellValue($firstCol . $r0, $title);
            $sh->mergeCells($firstCol . "$r0:{$last}" . ($r0 + 6));
            $sh->getStyle($firstCol . $r0)->getAlignment()->setWrapText(true)->setHorizontal('center')->setVertical('center');
            $sh->getStyle($firstCol . $r0)->getFont()->setBold(true);

            $top = $r0 + max(count($lines), 7) + 1;
            foreach (['A' => 'N°', 'B' => 'NOM ET PRENOMS', 'C' => 'IM', 'D' => 'LOCALITE DE SERVICE'] as $c => $t) {
                $sh->setCellValue("{$c}$top", $t);
                $sh->mergeCells("{$c}$top:{$c}" . ($top + 1));
            }
            $sh->setCellValue($firstCol . $top, 'EMARGEMENT');
            $sh->mergeCells($firstCol . "$top:{$last}$top");
            for ($k = 0; $k < $cols; $k++) {
                $c = Coordinate::stringFromColumnIndex($first + $k);
                $sh->setCellValue($c . ($top + 1), $sr['labels'][$k]);
                $sh->getStyle($c . ($top + 1))->getAlignment()->setTextRotation(90)->setHorizontal('center')->setVertical('center');
            }
            $sh->getRowDimension($top + 1)->setRowHeight(64);
            $this->head($sh, "A$top:{$last}" . ($top + 1));

            $row = $top + 2;
            foreach ($d['agents'] as $i => $ag) {
                $sh->setCellValue("A$row", $i + 1);
                $sh->getStyle("A$row")->getFont()->setSize(8);
                $sh->setCellValue("B$row", $ag->name);
                $sh->setCellValueExplicit("C$row", $this->docs->fmtIm($ag->im), DataType::TYPE_STRING);
                $sh->setCellValue("D$row", (string) $ag->locality);
                $sh->getStyle("A$row:D$row")->getAlignment()->setVertical('center')->setWrapText(true);
                $sh->getStyle("A$row")->getAlignment()->setHorizontal('center');
                $sh->getStyle("C$row:D$row")->getAlignment()->setHorizontal('center');
                $h = 13 * max($this->lines($ag->name, 40), $this->lines($ag->locality, 20)) + 6;
                $sh->getRowDimension($row)->setRowHeight(max($rh, $h));
                $row++;
            }
            $this->box($sh, "A$top:{$last}" . max($row - 1, $top + 1));
            $sh->setCellValue('A' . ($row + 1), $s['place'] . ', le ' . ($s['decision_date'] ?: ''));
            $sh->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($top, $top + 1);
            $this->setup($sh, true);
        }
    }

    private function excelEtat(Spreadsheet $ss, array $d, int $pi = 0): void
    {
        $sh = $pi === 0 ? $ss->getActiveSheet() : $ss->createSheet();
        $s = $d['s'];
        $a = $d['a'];
        $sh->setTitle($this->sheetName('État de vacations', $d));
        $widths = [5, 44, 12, 20, 7, 12, 14, 22];
        foreach ($widths as $i => $wd) $sh->getColumnDimension(Coordinate::stringFromColumnIndex($i + 1))->setWidth($wd);

        $o = $this->excelTop($sh, $s, $widths, 'H');
        $n = $this->excelHeaderBlock($sh, $s, $d, $o + 1, 'etat_title', 'etat_line1', 'etat_line2', 'C', 'D', 'H');

        $ir = $o + $n + 1;   // IRSA en haut du tableau : modifiable, le net à payer se recalcule
        $sh->setCellValue("A$ir", 'Taux IRSA (%) :'); $sh->mergeCells("A$ir:B$ir");
        $sh->getStyle("A$ir")->getAlignment()->setHorizontal('right');
        $sh->setCellValue("C$ir", $d['irsaRate']);
        $sh->getStyle("C$ir")->getFont()->setBold(true)->getColor()->setRGB('0000FF');
        $sh->getStyle("C$ir")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF9DB');
        $this->box($sh, "C$ir");
        $sh->setCellValue("D$ir", 'Montant net à payer (IRSA déjà déduit)');

        $hdr = $ir + 2;
        $sh->fromArray(['N°', 'NOM ET PRENOMS', 'IM', 'LOCALITE', 'Jours', 'Taux / jour (Ar)', 'Net à payer (Ar)', 'ÉMARGEMENT'], null, "A$hdr");
        $this->head($sh, "A$hdr:H$hdr");
        $sh->getRowDimension($hdr)->setRowHeight(28);
        $row = $hdr + 1;
        $firstData = $row;
        foreach ($d['agents'] as $i => $ag) {
            $sh->setCellValue("A$row", $i + 1);
            $sh->getStyle("A$row")->getFont()->setSize(8);
            $sh->setCellValue("B$row", $ag->name);
            $sh->setCellValueExplicit("C$row", $this->docs->fmtIm($ag->im), DataType::TYPE_STRING);
            $sh->setCellValue("D$row", (string) $ag->locality);
            $sh->setCellValue("E$row", $a->days);
            $sh->setCellValue("F$row", $a->rate);
            $sh->setCellValue("G$row", "=E$row*F$row-ROUND(E$row*F$row*\$C\$$ir/100,0)");
            $sh->getStyle("A$row:H$row")->getAlignment()->setVertical('center');
            $sh->getStyle("B$row")->getAlignment()->setWrapText(true);
            $sh->getStyle("D$row")->getAlignment()->setWrapText(true);
            $sh->getStyle("A$row")->getAlignment()->setHorizontal('center');
            $sh->getStyle("C$row")->getAlignment()->setHorizontal('center');
            $sh->getRowDimension($row)->setRowHeight(max(20, 13 * max($this->lines($ag->name, 40), $this->lines($ag->locality, 18)) + 6));
            $row++;
        }
        $sh->setCellValue("A$row", 'TOTAL'); $sh->mergeCells("A$row:F$row");
        $sh->setCellValue("G$row", $d['n'] ? "=SUM(G{$firstData}:G" . ($row - 1) . ')' : 0);
        $sh->getStyle("A$row:G$row")->getFont()->setBold(true);
        $sh->getStyle("F{$firstData}:G$row")->getNumberFormat()->setFormatCode('#,##0');
        $this->box($sh, "A$hdr:H$row");
        $sh->setCellValue('A' . ($row + 2), $this->docs->etatFooter($d));
        $sh->getStyle('A' . ($row + 2))->getFont()->setItalic(true);
        $sh->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($hdr, $hdr);
        $this->setup($sh, false);
    }

    /** Décompte : logo en haut, bloc ministère, taux IRSA dans une cellule (tous les montants se recalculent si on la modifie). */
    private function excelDecompte(Spreadsheet $ss, array $d, int $pi = 0): void
    {
        $sh = $pi === 0 ? $ss->getActiveSheet() : $ss->createSheet();
        $s = $d['s'];
        $a = $d['a'];
        $sh->setTitle($this->sheetName('Décompte', $d));
        $widths = [5, 40, 12, 20, 7, 11, 14, 13, 15];
        foreach ($widths as $i => $wd) $sh->getColumnDimension(Coordinate::stringFromColumnIndex($i + 1))->setWidth($wd);

        $o = $this->excelTop($sh, $s, $widths, 'I');
        $n = $this->excelHeaderBlock($sh, $s, $d, $o + 1, 'decompte_title', 'decompte_line1', 'decompte_line2', 'C', 'D', 'I');

        $ir = $o + $n + 1;   // ligne du taux IRSA
        $sh->setCellValue("A$ir", 'Taux IRSA (%) :'); $sh->mergeCells("A$ir:B$ir");
        $sh->getStyle("A$ir")->getAlignment()->setHorizontal('right');
        $sh->setCellValue("C$ir", $d['irsaRate']);
        $sh->getStyle("C$ir")->getFont()->setBold(true)->getColor()->setRGB('0000FF');
        $sh->getStyle("C$ir")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF9DB');
        $this->box($sh, "C$ir");

        $hdr = $ir + 2;
        $sh->fromArray(['N°', 'NOM ET PRENOMS', 'IM', 'LOCALITE', 'Jours', 'Taux / jour', 'Montant brut', 'IRSA', 'Net à percevoir'], null, "A$hdr");
        $this->head($sh, "A$hdr:I$hdr");
        $sh->getRowDimension($hdr)->setRowHeight(30);
        $row = $hdr + 1;
        $nCh = count($d['dec']);
        $prevCum = null;

        foreach ($d['dec'] as $ci => $ch) {
            $reportRow = null;
            if (!$ch['first']) {
                $reportRow = $row;
                $sh->setCellValue("A$row", 'REPORT (page précédente)'); $sh->mergeCells("A$row:F$row");
                foreach (['G', 'H', 'I'] as $c) $sh->setCellValue("$c$row", "=$c$prevCum");
                $sh->getStyle("A$row:I$row")->getFont()->setBold(true);
                $sh->getStyle("A$row")->getAlignment()->setHorizontal('right');
                $row++;
            }
            $firstRow = $row;
            foreach ($ch['rows'] as $k => $ag) {
                $sh->setCellValue("A$row", $ch['offset'] + $k + 1);
                $sh->setCellValue("B$row", $ag->name);
                $sh->setCellValueExplicit("C$row", $this->docs->fmtIm($ag->im), DataType::TYPE_STRING);
                $sh->setCellValue("D$row", (string) $ag->locality);
                $sh->setCellValue("E$row", $a->days);
                $sh->setCellValue("F$row", $a->rate);
                $sh->setCellValue("G$row", "=E$row*F$row");
                $sh->setCellValue("H$row", "=ROUND(G$row*\$C\${$ir}/100,0)");
                $sh->setCellValue("I$row", "=G$row-H$row");
                $sh->getStyle("A$row:I$row")->getAlignment()->setVertical('center');
                $sh->getStyle("B$row")->getAlignment()->setWrapText(true);
                $sh->getStyle("D$row")->getAlignment()->setWrapText(true);
                $sh->getStyle("A$row")->getAlignment()->setHorizontal('center');
                $sh->getStyle("C$row")->getAlignment()->setHorizontal('center');
                $sh->getRowDimension($row)->setRowHeight(max(20, 13 * max($this->lines($ag->name, 38), $this->lines($ag->locality, 18)) + 6));
                $row++;
            }
            $lastRow = $row - 1;
            $ptRow = $row;
            $sh->setCellValue("A$row", 'TOTAL DE LA PAGE'); $sh->mergeCells("A$row:F$row");
            foreach (['G', 'H', 'I'] as $c) $sh->setCellValue("$c$row", $ch['rows']->count() ? "=SUM({$c}{$firstRow}:{$c}{$lastRow})" : 0);
            $sh->getStyle("A$row:I$row")->getFont()->setBold(true);
            $sh->getStyle("A$row")->getAlignment()->setHorizontal('right');
            $row++;
            $cumRow = $row;
            $sh->setCellValue("A$row", $ci === $nCh - 1 ? 'TOTAL GÉNÉRAL' : 'TOTAL À REPORTER'); $sh->mergeCells("A$row:F$row");
            foreach (['G', 'H', 'I'] as $c) $sh->setCellValue("$c$row", $reportRow ? "=$c$reportRow+$c$ptRow" : "=$c$ptRow");
            $sh->getStyle("A$row:I$row")->getFont()->setBold(true);
            $sh->getStyle("A$row:I$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DCE6F1');
            $sh->getStyle("A$row")->getAlignment()->setHorizontal('right');
            $row++;
            $prevCum = $cumRow;
            if ($ci < $nCh - 1) $sh->setBreak("A$cumRow", Worksheet::BREAK_ROW);
        }
        $this->box($sh, "A$hdr:I" . ($row - 1));
        $sh->getStyle("F" . ($hdr + 1) . ":I" . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');
        $sh->setCellValue('A' . ($row + 1), $this->docs->tpl($s['decompte_footer'], $d));
        $sh->getStyle('A' . ($row + 1))->getFont()->setItalic(true);
        $sh->setCellValue('A' . ($row + 3), $this->docs->signDate($d));
        $sh->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($hdr, $hdr);
        $this->setup($sh, false);
    }

    /* ======= Récapitulatif : matrice agents × activités (logo en haut) ======= */
    public function recapExcel(array $b): BinaryFileResponse
    {
        $ss = new Spreadsheet();
        $sh = $ss->getActiveSheet();
        $sh->setTitle('Récapitulatif');
        $ss->getDefaultStyle()->getFont()->setName('Arial')->setSize(9);
        $acts = $b['acts'];
        $nA = $acts->count();
        $head = ['N°', 'NOM ET PRENOMS', 'IM', 'LOCALITE', 'TYPE'];
        foreach ($acts as $a) $head[] = $a->code;
        array_push($head, 'Nb activités', 'Montant session (Ar)', 'Total retenu (Ar)', 'Statut', 'Activités reçues');
        $lastCol = Coordinate::stringFromColumnIndex(count($head));

        $sh->getColumnDimension('A')->setWidth(5);
        $sh->getColumnDimension('B')->setWidth(42);
        $sh->getColumnDimension('C')->setWidth(12);
        $sh->getColumnDimension('D')->setWidth(18);
        $sh->getColumnDimension('E')->setWidth(12);
        $o = $this->excelTop($sh, $b['settings'], [5, 42, 12, 18, 12], 'E');
        $h = $o + 1;

        $sh->fromArray($head, null, "A$h");
        $this->head($sh, "A$h:{$lastCol}$h");
        $row = $h + 1;
        $first = $row;
        foreach ($b['rows'] as $i => $r) {
            $ag = $r['agent'];
            $line = [$i + 1, $ag->name, null, $ag->locality, $ag->type];
            foreach ($acts as $a) $line[] = in_array($a->id, $r['ids']) ? 1 : '';
            array_push($line, $r['count'], $r['amount'], $r['total'], $r['status'], $r['acts']->pluck('code')->implode(', '));
            $sh->fromArray($line, null, "A$row");
            $sh->setCellValueExplicit("C$row", (string) $ag->im, DataType::TYPE_STRING);
            $row++;
        }
        $sh->setCellValue("A$row", 'TOTAL');
        for ($k = 0; $k < $nA; $k++) {
            $c = Coordinate::stringFromColumnIndex(6 + $k);
            $sh->setCellValue("$c$row", "=SUM({$c}{$first}:{$c}" . ($row - 1) . ")");
        }
        $sh->getStyle("A$row:{$lastCol}$row")->getFont()->setBold(true);
        $this->box($sh, "A$h:{$lastCol}$row");
        $sh->getStyle(Coordinate::stringFromColumnIndex(7 + $nA) . "$first:" . Coordinate::stringFromColumnIndex(8 + $nA) . "$row")->getNumberFormat()->setFormatCode('#,##0');
        $sh->freezePane('C' . $first);
        $this->setup($sh, true);
        return $this->save($ss, 'recapitulatif-agents-' . $b['settings']['session'] . '.xlsx');
    }

    public function agentsExcel(): BinaryFileResponse
    {
        $ss = new Spreadsheet();
        $sh = $ss->getActiveSheet();
        $sh->setTitle('AGENTS');
        $acts = Activity::orderBy('position')->get();
        $head = ['N°', 'NOM ET PRENOMS', 'IM', 'LOCALITE DE SERVICE', 'TYPE', 'DISPONIBLE', 'CORPS', 'GRADE', 'INDICE', 'FONCTION / OBSERVATION', 'HISTORIQUE (Ar)', 'ORDRE TIRAGE'];
        foreach ($acts as $a) $head[] = $a->code;
        $sh->fromArray($head, null, 'A1');
        $this->head($sh, 'A1:' . Coordinate::stringFromColumnIndex(count($head)) . '1');
        $row = 2;
        foreach (Agent::with('exceptions')->orderBy('name')->get() as $i => $ag) {
            $line = [$i + 1, $ag->name, null, $ag->locality, $ag->type, $ag->available ? 'OUI' : 'NON', $ag->corps, $ag->grade, $ag->indice, $ag->function, $ag->history_amount, $ag->draw_order];
            foreach ($acts as $a) $line[] = optional($ag->exceptions->firstWhere('activity_id', $a->id))->mode;
            $sh->fromArray($line, null, "A$row");
            $sh->setCellValueExplicit("C$row", (string) $ag->im, DataType::TYPE_STRING);
            $row++;
        }
        $sh->getColumnDimension('B')->setWidth(42);
        $sh->freezePane('C2');
        return $this->save($ss, 'agents-' . date('Ymd') . '.xlsx');
    }
}
