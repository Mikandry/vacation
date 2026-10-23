<?php
namespace App\Services;

use App\Models\Activity;
use App\Models\Agent;
use App\Support\Flag;
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
    private function showLogo(array $s): bool { return ($s['note_show_logo'] ?? 'OUI') === 'OUI'; }

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
        // largeurs de colonnes respectées : le texte long passe à la ligne dans la cellule
        $w->addTableStyle('grid', ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 50, 'layout' => TableStyle::LAYOUT_FIXED]);
        match ($type) {
            'decision' => $this->wordDecision($w, $d),
            'note' => $this->wordNote($w, $d),
            'presence' => $this->wordPresence($w, $d),
            'etat' => $this->wordEtat($w, $d),
            'decompte' => $this->wordDecompte($w, $d),
        };
        $tmp = $this->tmp();
        IOFactory::createWriter($w, 'Word2007')->save($tmp);
        return $this->send($tmp, "{$type}-{$d['file']}.docx");
    }

    private function wordDecision(PhpWord $w, array $d): void
    {
        $s = $d['s'];
        $sec = $w->addSection(['marginTop' => 700, 'marginBottom' => 700, 'marginLeft' => 900, 'marginRight' => 900]);
        if (($s['show_flag'] ?? 'OUI') === 'OUI') {
            $sec->addImage(Flag::file(), ['width' => 62, 'height' => 41, 'alignment' => 'center']);
            $sec->addText($s['republic'], ['bold' => true, 'size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
            $sec->addText($s['motto'], ['italic' => true, 'size' => 8], ['alignment' => 'center']);
        }
        $head = $sec->addTable();
        $r = $head->addRow();
        $c = $r->addCell(3800);
        $c->addText($s['ministry'], [], ['alignment' => 'center']);
        $c->addText('--------------', [], ['alignment' => 'center']);
        $r->addCell(6000)->addText($this->docs->decisionTitle($d), ['bold' => true], ['alignment' => 'both']);
        $sec->addTextBreak(1);
        $sec->addText($s['authority'], ['bold' => true], ['alignment' => 'center']);
        foreach ($d['visas'] as $v) $sec->addText($v->text, ['size' => 10], ['alignment' => 'both', 'spaceAfter' => 0]);
        $sec->addPageBreak();
        $sec->addText('DECIDE :', ['bold' => true]);
        $sec->addText($this->docs->article1($d), [], ['alignment' => 'both']);
        $last = count($d['chunks']) - 1;
        foreach ($d['chunks'] as $i => $ch) {
            $t = $sec->addTable('grid');
            $h = $t->addRow(null, ['tblHeader' => true]);
            foreach ([[900, 'N° d’ordre'], [600, 'n°'], [4700, 'NOM ET PRENOMS'], [1200, 'IM'], [2400, 'LOCALITE DE SERVICE']] as [$wd, $tx])
                $h->addCell($wd)->addText($tx, ['bold' => true, 'size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
            foreach ($ch['rows'] as $k => $a) {
                $row = $t->addRow(null, ['cantSplit' => true]);
                $row->addCell(900)->addText((string) ($ch['offset'] + $k + 1), ['size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
                $row->addCell(600)->addText((string) ($k + 1), ['size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
                $row->addCell(4700)->addText($a->name, ['size' => 10], ['spaceAfter' => 0]);
                $row->addCell(1200)->addText((string) $a->im, ['size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
                $row->addCell(2400)->addText((string) $a->locality, ['size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
            }
            $sec->addText($this->docs->pageTail($ch), ['bold' => true, 'italic' => true, 'size' => 10]);
            if ($i < $last) $sec->addPageBreak();
        }
        $sec->addText("ARRETEE LA PRESENTE DECISION AU NOMBRE DE {$d['nWords']} ({$d['n']}) AGENTS.", ['bold' => true]);
        $sec->addText($this->docs->article2($d), [], ['alignment' => 'both']);
        $sec->addText('Cette indemnité est imputable sur la ligne budgétaire :');
        $b = $sec->addTable('grid');
        $r1 = $b->addRow();
        $r2 = $b->addRow();
        foreach ([['Mission', 'budget_mission'], ['Indicateur d’objectif', 'budget_indicator'], ['Section', 'budget_section'], ['Compte', 'budget_account'], ['Code ORDSEC', 'budget_ordsec']] as [$lab, $key]) {
            $r1->addCell(1960)->addText($lab, ['bold' => true, 'size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
            $r2->addCell(1960)->addText($s[$key], ['size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
        }
        $sec->addTextBreak(1);
        $sec->addText('Article 3 : La présente décision sera enregistrée et communiquée partout où besoin sera.');
        $sec->addText($this->docs->signDate($d), [], ['alignment' => 'right']);
        $sec->addText($s['signatory'], ['bold' => true], ['alignment' => 'right']);
    }

    /** Note de service — mise en page du modèle note_vacation_CAP_2026.docx. */
    private function wordNote(PhpWord $w, array $d): void
    {
        $s = $d['s'];
        $a = $d['a'];
        $font = trim($s['note_font']) ?: 'Book Antiqua';
        $w->setDefaultFontName($font);
        $w->setDefaultFontSize(12);
        $w->setDefaultParagraphStyle(['spaceAfter' => 0, 'spaceBefore' => 0]);

        $sec = $w->addSection(['marginTop' => 586, 'marginBottom' => 1440, 'marginLeft' => 1080, 'marginRight' => 1080]);
        $head = $sec->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF', 'alignment' => 'center']);
        $row = $head->addRow();
        $left = $row->addCell(4815);
        foreach (preg_split('/\R/', $s['note_header_left']) as $line) $left->addText($line, [], ['alignment' => 'center']);
        $right = $row->addCell(4921);
        if ($this->showLogo($s)) {
            $right->addImage(Logo::file(), ['width' => 94, 'height' => 53, 'alignment' => 'center']);
        }
        $right->addText($s['note_title'], ['bold' => true], ['alignment' => 'center', 'spaceBefore' => 120, 'spaceAfter' => 120]);
        $right->addText($this->docs->tpl(($a->note_number ?: $s['note_number']) . ' ' . $s['note_subject'], $d), ['bold' => true], ['alignment' => 'both']);
        $right->addText('SESSION ' . $s['session'], ['bold' => true], ['alignment' => 'center', 'spaceBefore' => 60]);

        $sec->addTextBreak(1);
        $sec->addText($this->docs->tpl($s['note_intro'], $d), [], ['alignment' => 'both', 'lineHeight' => 1.5, 'indentation' => ['firstLine' => 708], 'spaceAfter' => 120]);

        $t = $sec->addTable(['borderSize' => 4, 'borderColor' => '000000', 'cellMarginLeft' => 70, 'cellMarginRight' => 70, 'layout' => TableStyle::LAYOUT_FIXED]);
        $cols = [[610, 'N°'], [5091, 'NOM ET PRENOMS'], [1394, 'IM'], [2823, 'LOCALITE']];
        $h = $t->addRow(500, ['tblHeader' => true, 'cantSplit' => true]);
        foreach ($cols as [$wd, $tx]) $h->addCell($wd, ['valign' => 'center'])->addText($tx, ['bold' => true], ['alignment' => 'center']);
        foreach ($d['agents'] as $i => $ag) {
            $r = $t->addRow(500, ['cantSplit' => true]);
            $r->addCell(610, ['valign' => 'center'])->addText((string) ($i + 1), [], ['alignment' => 'right']);
            $r->addCell(5091, ['valign' => 'center'])->addText($ag->name);
            $r->addCell(1394, ['valign' => 'center'])->addText((string) $ag->im, [], ['alignment' => 'center']);
            $r->addCell(2823, ['valign' => 'center'])->addText((string) $ag->locality, [], ['alignment' => 'center']);
        }
        $sec->addTextBreak(1);
        $sec->addText($this->docs->tpl($s['note_closing'], $d), [], ['alignment' => 'both', 'lineHeight' => 1.5]);
        $sec->addText($this->docs->tpl($s['note_place_date'], $d), [], ['alignment' => 'both', 'lineHeight' => 1.5, 'indentation' => ['left' => 4956, 'firstLine' => 708]]);
        foreach (preg_split('/\R/', $s['note_signature']) as $line) {
            $sec->addText($line, [], ['alignment' => 'center', 'lineHeight' => 1.5, 'indentation' => ['left' => 4956, 'firstLine' => 708]]);
        }
    }

    /** Fiche de présence : paysage, logo tout en haut, 15 colonnes max par feuille. */
    private function wordPresence(PhpWord $w, array $d): void
    {
        $s = $d['s'];
        $w->setDefaultParagraphStyle(['spaceAfter' => 0, 'spaceBefore' => 0]);
        $total = 15400;
        $idw = max(25, min(60, (int) $s['presence_id_width']));
        $wN = 450; $wIm = 1100; $wLoc = 1800;
        $idTotal = max((int) round($total * $idw / 100), $wN + $wIm + $wLoc + 3000);   // nom : 3000 twips minimum
        $wName = $idTotal - $wN - $wIm - $wLoc;
        $rowH = max(18, (int) $s['presence_row_height']) * 20;
        $left = preg_split('/\R/', $s['presence_header_left']);
        $nSeries = count($d['series']);

        foreach ($d['series'] as $si => $sr) {
            $cols = $sr['cols'];
            $dayW = max(300, intdiv($total - $idTotal, $cols));
            $sec = $w->addSection(['orientation' => 'landscape', 'marginTop' => 500, 'marginBottom' => 500, 'marginLeft' => 720, 'marginRight' => 720]);

            // 1) logo tout en haut, centré
            if ($this->showLogo($s)) {
                $sec->addImage(Logo::file(), ['width' => 94, 'height' => 53, 'alignment' => 'center']);
            }
            // 2) bloc ministère à gauche, titre à droite
            $head = $sec->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF']);
            $r = $head->addRow();
            $lc = $r->addCell(6200);
            foreach ($left as $ln) $lc->addText($ln, ['size' => 9], ['alignment' => 'center']);
            $rc = $r->addCell(9200);
            $rc->addText($this->docs->tpl($s['presence_title'], $d), ['bold' => true, 'size' => 10], ['alignment' => 'center']);
            $rc->addText($this->docs->tpl($s['presence_line1'], $d), ['bold' => true, 'size' => 10], ['alignment' => 'center']);
            if (trim($s['presence_line2']) !== '') $rc->addText($this->docs->tpl($s['presence_line2'], $d), ['size' => 9], ['alignment' => 'center']);
            if ($nSeries > 1) $rc->addText('Feuille ' . ($si + 1) . "/$nSeries — jours {$sr['from']} à {$sr['to']}", ['italic' => true, 'size' => 9], ['alignment' => 'center']);
            $sec->addTextBreak(1);

            $t = $sec->addTable('grid');
            $h1 = $t->addRow(null, ['tblHeader' => true]);
            foreach ([[$wN, 'N°'], [$wName, 'NOM ET PRENOMS'], [$wIm, 'IM'], [$wLoc, 'LOCALITE DE SERVICE']] as [$wd, $tx])
                $h1->addCell($wd, ['vMerge' => 'restart', 'valign' => 'center'])->addText($tx, ['bold' => true, 'size' => 9], ['alignment' => 'center']);
            $h1->addCell($dayW * $cols, ['gridSpan' => $cols, 'valign' => 'center'])->addText('EMARGEMENT', ['bold' => true, 'size' => 9], ['alignment' => 'center']);
            $h2 = $t->addRow(1050, ['tblHeader' => true]);
            foreach ([$wN, $wName, $wIm, $wLoc] as $wd) $h2->addCell($wd, ['vMerge' => 'continue']);
            for ($k = 0; $k < $cols; $k++) {
                $c = $h2->addCell($dayW, ['textDirection' => CellStyle::TEXT_DIR_BTLR, 'valign' => 'center']);
                $c->addText($sr['labels'][$k], ['bold' => true, 'size' => 8], ['alignment' => 'center']);
            }
            foreach ($d['agents'] as $i => $ag) {
                $row = $t->addRow($rowH, ['cantSplit' => true]);
                $row->addCell($wN, ['valign' => 'center'])->addText((string) ($i + 1), ['size' => 9], ['alignment' => 'center']);
                $row->addCell($wName, ['valign' => 'center'])->addText($ag->name, ['size' => 9]);
                $row->addCell($wIm, ['valign' => 'center'])->addText($this->docs->fmtIm($ag->im), ['size' => 9], ['alignment' => 'center']);
                $row->addCell($wLoc, ['valign' => 'center'])->addText((string) $ag->locality, ['size' => 9], ['alignment' => 'center']);
                for ($k = 0; $k < $cols; $k++) $row->addCell($dayW)->addText('', ['size' => 9]);
            }
            $sec->addTextBreak(1);
            $sec->addText($s['place'] . ', le ' . ($s['decision_date'] ?: ''), [], ['alignment' => 'left']);
        }
    }

    private function wordEtat(PhpWord $w, array $d): void
    {
        $a = $d['a'];
        $s = $d['s'];
        $w->setDefaultParagraphStyle(['spaceAfter' => 0, 'spaceBefore' => 0]);
        $sec = $w->addSection(['marginTop' => 600, 'marginBottom' => 700, 'marginLeft' => 800, 'marginRight' => 800]);
        if ($this->showLogo($s)) $sec->addImage(Logo::file(), ['width' => 94, 'height' => 53, 'alignment' => 'center']);
        $sec->addText($s['ministry'], ['bold' => true, 'size' => 10], ['alignment' => 'center']);
        $sec->addText($this->docs->tpl($s['etat_title'], $d), ['bold' => true, 'size' => 16], ['alignment' => 'center', 'spaceBefore' => 120]);
        $sec->addText($this->docs->tpl($s['etat_line1'], $d), ['bold' => true], ['alignment' => 'center']);
        $sec->addText($this->docs->tpl($s['etat_line2'], $d), ['size' => 9, 'italic' => true], ['alignment' => 'center', 'spaceAfter' => 120]);
        $t = $sec->addTable('grid');
        $cols = [[500, 'N°'], [3200, 'NOM ET PRENOMS'], [1000, 'IM'], [1500, 'LOCALITE'], [600, 'Jours'], [1000, 'Taux/jour'], [1200, 'Montant (Ar)'], [1300, 'ÉMARGEMENT']];
        $h = $t->addRow(null, ['tblHeader' => true]);
        foreach ($cols as [$wd, $tx]) $h->addCell($wd, ['valign' => 'center'])->addText($tx, ['bold' => true, 'size' => 9], ['alignment' => 'center']);
        foreach ($d['agents'] as $i => $ag) {
            $r = $t->addRow(380, ['cantSplit' => true]);
            $vals = [(string) ($i + 1), $ag->name, $this->docs->fmtIm($ag->im), (string) $ag->locality, (string) $a->days, $this->nf($a->rate), $this->nf($d['amount']), ''];
            foreach ($cols as $k => [$wd]) {
                $al = $k === 1 ? 'left' : (in_array($k, [5, 6]) ? 'right' : 'center');
                $r->addCell($wd, ['valign' => 'center'])->addText($vals[$k], ['size' => 9], ['alignment' => $al]);
            }
        }
        $sec->addTextBreak(1);
        $sec->addText("TOTAL : {$d['n']} agent(s) — " . $this->nf($d['totalMoney']) . ' Ar', ['bold' => true]);
        $sec->addText($this->docs->tpl($s['etat_footer'], $d), ['italic' => true]);
        $sec->addText($this->docs->signDate($d), [], ['alignment' => 'right']);
    }

    /** État de décompte : brut / IRSA / net, avec total de page et report. */
    private function wordDecompte(PhpWord $w, array $d): void
    {
        $s = $d['s'];
        $a = $d['a'];
        $w->setDefaultParagraphStyle(['spaceAfter' => 0, 'spaceBefore' => 0]);
        $sec = $w->addSection(['marginTop' => 600, 'marginBottom' => 700, 'marginLeft' => 800, 'marginRight' => 800]);
        if ($this->showLogo($s)) $sec->addImage(Logo::file(), ['width' => 94, 'height' => 53, 'alignment' => 'center']);
        $sec->addText($s['ministry'], ['bold' => true, 'size' => 10], ['alignment' => 'center']);
        $sec->addText($this->docs->tpl($s['decompte_title'], $d), ['bold' => true, 'size' => 15], ['alignment' => 'center', 'spaceBefore' => 120]);
        $sec->addText($this->docs->tpl($s['decompte_line1'], $d), ['bold' => true], ['alignment' => 'center']);
        $sec->addText($this->docs->tpl($s['decompte_line2'], $d), ['size' => 9, 'italic' => true], ['alignment' => 'center', 'spaceAfter' => 120]);

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

    /**
     * Logo centré tout en haut de la feuille, au-dessus de tout le reste.
     * Réserve $rows lignes et retourne leur nombre (0 si le logo est désactivé).
     */
    private function logoTop(Worksheet $sh, array $s, array $widths, int $rows = 3): int
    {
        if (!$this->showLogo($s)) return 0;
        $h = 54;
        $wpx = (int) round($h * 268 / 155);
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

    public function excel(string $type, array $d): BinaryFileResponse
    {
        $ss = new Spreadsheet();
        $ss->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        match ($type) {
            'decision' => $this->excelDecision($ss, $d),
            'presence' => $this->excelPresence($ss, $d),
            'decompte' => $this->excelDecompte($ss, $d),
            default => $this->excelEtat($ss, $d),
        };
        return $this->save($ss, "{$type}-{$d['file']}.xlsx");
    }

    private function excelDecision(Spreadsheet $ss, array $d): void
    {
        $sh = $ss->getActiveSheet();
        $s = $d['s'];
        $sh->setTitle('Décision');
        foreach (['A' => 10, 'B' => 6, 'C' => 48, 'D' => 12, 'E' => 26] as $c => $wd) $sh->getColumnDimension($c)->setWidth($wd);
        $row = 1;
        if (($s['show_flag'] ?? 'OUI') === 'OUI') {
            $dr = new Drawing();
            $dr->setName('Drapeau');
            $dr->setPath(Flag::file());
            $dr->setHeight(42);
            $dr->setCoordinates('C1');
            $dr->setOffsetX(110);
            $dr->setOffsetY(3);
            $dr->setWorksheet($sh);
            $sh->getRowDimension(1)->setRowHeight(48);
            $row = 2;
            foreach ([$s['republic'], $s['motto']] as $k => $t) {
                $sh->setCellValue("A$row", $t);
                $sh->mergeCells("A$row:E$row");
                $sh->getStyle("A$row")->getAlignment()->setHorizontal('center');
                if ($k === 0) $sh->getStyle("A$row")->getFont()->setBold(true);
                $row++;
            }
            $row++;
        }
        $sh->setCellValue("A$row", $s['ministry']); $sh->mergeCells("A$row:C$row"); $row++;
        $sh->setCellValue("A$row", $this->docs->decisionTitle($d)); $sh->mergeCells("A$row:E$row");
        $sh->getStyle("A$row")->getAlignment()->setWrapText(true)->setVertical('top');
        $sh->getStyle("A$row")->getFont()->setBold(true);
        $sh->getRowDimension($row)->setRowHeight(48);
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
        foreach ($d['chunks'] as $ch) {
            foreach ($ch['rows'] as $k => $ag) {
                $sh->setCellValue("A$row", $ch['offset'] + $k + 1);
                $sh->setCellValue("B$row", $k + 1);
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
                $sh->setCellValue("A$row", $this->docs->pageTail($ch)); $sh->mergeCells("A$row:E$row");
                $sh->getStyle("A$row")->getFont()->setItalic(true)->setBold(true);
                $row++;
            }
        }
        $this->box($sh, "A$start:E" . ($row - 1));
        $row++;
        $sh->setCellValue("A$row", "ARRETEE LA PRESENTE DECISION AU NOMBRE DE {$d['nWords']} ({$d['n']}) AGENTS.");
        $sh->getStyle("A$row")->getFont()->setBold(true);
        $row += 2;
        $sh->setCellValue("A$row", $this->docs->article2($d)); $sh->mergeCells("A$row:E$row");
        $sh->getStyle("A$row")->getAlignment()->setWrapText(true);
        $sh->getRowDimension($row)->setRowHeight(30);
        $row++;
        $sh->setCellValue("A$row", 'Cette indemnité est imputable sur la ligne budgétaire :'); $row++;
        $sh->fromArray([['Mission', 'Indicateur d’objectif', 'Section', 'Compte', 'Code ORDSEC'],
            [$s['budget_mission'], $s['budget_indicator'], $s['budget_section'], $s['budget_account'], $s['budget_ordsec']]], null, "A$row");
        $this->box($sh, "A$row:E" . ($row + 1));
        $sh->getStyle("A$row:E$row")->getFont()->setBold(true);
        $row += 3;
        $sh->setCellValue("A$row", 'Article 3 : La présente décision sera enregistrée et communiquée partout où besoin sera.');
        $row += 2;
        $sh->setCellValue("A$row", $this->docs->signDate($d)); $sh->mergeCells("A$row:E$row");
        $sh->getStyle("A$row")->getAlignment()->setHorizontal('right'); $row++;
        $sh->setCellValue("A$row", $s['signatory']); $sh->mergeCells("A$row:E$row");
        $sh->getStyle("A$row")->getAlignment()->setHorizontal('right');
        $sh->getStyle("A$row")->getFont()->setBold(true);
        $this->setup($sh, false);
    }

    /** Présence : une feuille Excel par série de jours (15 colonnes max), logo tout en haut. */
    private function excelPresence(Spreadsheet $ss, array $d): void
    {
        $s = $d['s'];
        $rh = max(18, (int) $s['presence_row_height']);
        $lines = preg_split('/\R/', $s['presence_header_left']);
        $nSeries = count($d['series']);

        foreach ($d['series'] as $si => $sr) {
            $sh = $si === 0 ? $ss->getActiveSheet() : $ss->createSheet();
            $sh->setTitle($nSeries > 1 ? 'Feuille ' . ($si + 1) : 'Présence');
            $cols = $sr['cols'];
            $first = 5;
            $last = Coordinate::stringFromColumnIndex(4 + $cols);
            $firstCol = Coordinate::stringFromColumnIndex($first);

            $widths = [5, 42, 12, 22];
            for ($k = 0; $k < $cols; $k++) $widths[] = 8.5;
            foreach ($widths as $i => $wd) $sh->getColumnDimension(Coordinate::stringFromColumnIndex($i + 1))->setWidth($wd);

            $o = $this->logoTop($sh, $s, $widths, 3);      // 1) logo tout en haut
            $r0 = $o + 1;
            foreach ($lines as $i => $ln) {                // 2) bloc ministère à gauche
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
                $sh->setCellValue("B$row", $ag->name);
                $sh->setCellValueExplicit("C$row", $this->docs->fmtIm($ag->im), DataType::TYPE_STRING);
                $sh->setCellValue("D$row", (string) $ag->locality);
                // retour à la ligne : le nom et la localité ne sont jamais coupés
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
        $ss->setActiveSheetIndex(0);
    }

    private function excelEtat(Spreadsheet $ss, array $d): void
    {
        $sh = $ss->getActiveSheet();
        $s = $d['s'];
        $a = $d['a'];
        $sh->setTitle('État de vacations');
        $widths = [5, 44, 12, 20, 7, 12, 14, 22];
        foreach ($widths as $i => $wd) $sh->getColumnDimension(Coordinate::stringFromColumnIndex($i + 1))->setWidth($wd);
        $o = $this->logoTop($sh, $s, $widths, 3);

        $sh->setCellValue('A' . ($o + 1), $s['ministry']); $sh->mergeCells('A' . ($o + 1) . ':H' . ($o + 1));
        $sh->getStyle('A' . ($o + 1))->getFont()->setBold(true); $sh->getStyle('A' . ($o + 1))->getAlignment()->setHorizontal('center');
        $sh->setCellValue('A' . ($o + 2), $this->docs->tpl($s['etat_title'], $d)); $sh->mergeCells('A' . ($o + 2) . ':H' . ($o + 2));
        $sh->getStyle('A' . ($o + 2))->getFont()->setBold(true)->setSize(16);
        $sh->getStyle('A' . ($o + 2))->getAlignment()->setHorizontal('center');
        $sh->setCellValue('A' . ($o + 3), $this->docs->tpl($s['etat_line1'], $d)); $sh->mergeCells('A' . ($o + 3) . ':H' . ($o + 3));
        $sh->getStyle('A' . ($o + 3))->getFont()->setBold(true); $sh->getStyle('A' . ($o + 3))->getAlignment()->setHorizontal('center');
        $sh->setCellValue('A' . ($o + 4), $this->docs->tpl($s['etat_line2'], $d)); $sh->mergeCells('A' . ($o + 4) . ':H' . ($o + 4));
        $sh->getStyle('A' . ($o + 4))->getAlignment()->setHorizontal('center');

        $hdr = $o + 6;
        $sh->fromArray(['N°', 'NOM ET PRENOMS', 'IM', 'LOCALITE', 'Jours', 'Taux / jour (Ar)', 'Montant (Ar)', 'ÉMARGEMENT'], null, "A$hdr");
        $this->head($sh, "A$hdr:H$hdr");
        $sh->getRowDimension($hdr)->setRowHeight(28);
        $row = $hdr + 1;
        $firstData = $row;
        foreach ($d['agents'] as $i => $ag) {
            $sh->setCellValue("A$row", $i + 1);
            $sh->setCellValue("B$row", $ag->name);
            $sh->setCellValueExplicit("C$row", $this->docs->fmtIm($ag->im), DataType::TYPE_STRING);
            $sh->setCellValue("D$row", (string) $ag->locality);
            $sh->setCellValue("E$row", $a->days);
            $sh->setCellValue("F$row", $a->rate);
            $sh->setCellValue("G$row", "=E$row*F$row");
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
        $sh->setCellValue('A' . ($row + 2), $this->docs->tpl($s['etat_footer'], $d));
        $sh->getStyle('A' . ($row + 2))->getFont()->setItalic(true);
        $sh->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($hdr, $hdr);
        $this->setup($sh, false);
    }

    /** Décompte : le taux IRSA est une cellule ; tous les montants se recalculent si vous la modifiez. */
    private function excelDecompte(Spreadsheet $ss, array $d): void
    {
        $sh = $ss->getActiveSheet();
        $s = $d['s'];
        $a = $d['a'];
        $sh->setTitle('Décompte');
        $widths = [5, 40, 12, 20, 7, 11, 14, 13, 15];
        foreach ($widths as $i => $wd) $sh->getColumnDimension(Coordinate::stringFromColumnIndex($i + 1))->setWidth($wd);
        $o = $this->logoTop($sh, $s, $widths, 3);

        $sh->setCellValue('A' . ($o + 1), $s['ministry']); $sh->mergeCells('A' . ($o + 1) . ':I' . ($o + 1));
        $sh->getStyle('A' . ($o + 1))->getFont()->setBold(true); $sh->getStyle('A' . ($o + 1))->getAlignment()->setHorizontal('center');
        $sh->setCellValue('A' . ($o + 2), $this->docs->tpl($s['decompte_title'], $d)); $sh->mergeCells('A' . ($o + 2) . ':I' . ($o + 2));
        $sh->getStyle('A' . ($o + 2))->getFont()->setBold(true)->setSize(15);
        $sh->getStyle('A' . ($o + 2))->getAlignment()->setHorizontal('center');
        $sh->setCellValue('A' . ($o + 3), $this->docs->tpl($s['decompte_line1'], $d)); $sh->mergeCells('A' . ($o + 3) . ':I' . ($o + 3));
        $sh->getStyle('A' . ($o + 3))->getFont()->setBold(true); $sh->getStyle('A' . ($o + 3))->getAlignment()->setHorizontal('center');
        $sh->setCellValue('A' . ($o + 4), $this->docs->tpl($s['decompte_line2'], $d)); $sh->mergeCells('A' . ($o + 4) . ':I' . ($o + 4));
        $sh->getStyle('A' . ($o + 4))->getAlignment()->setHorizontal('center');

        $ir = $o + 5;   // ligne du taux IRSA
        $sh->setCellValue("A$ir", 'Taux IRSA (%) :'); $sh->mergeCells("A$ir:B$ir");
        $sh->getStyle("A$ir")->getAlignment()->setHorizontal('right');
        $sh->setCellValue("C$ir", $d['irsaRate']);
        $sh->getStyle("C$ir")->getFont()->setBold(true)->getColor()->setRGB('0000FF');
        $sh->getStyle("C$ir")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF9DB');
        $this->box($sh, "C$ir");

        $hdr = $o + 7;
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

    /* ======= Récapitulatif : matrice agents × activités ======= */
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
        $sh->fromArray($head, null, 'A1');
        $lastCol = Coordinate::stringFromColumnIndex(count($head));
        $this->head($sh, "A1:{$lastCol}1");
        $row = 2;
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
            $sh->setCellValue("$c$row", "=SUM({$c}2:{$c}" . ($row - 1) . ")");
        }
        $sh->getStyle("A$row:{$lastCol}$row")->getFont()->setBold(true);
        $this->box($sh, "A1:{$lastCol}$row");
        $sh->getStyle(Coordinate::stringFromColumnIndex(7 + $nA) . "2:" . Coordinate::stringFromColumnIndex(8 + $nA) . "$row")->getNumberFormat()->setFormatCode('#,##0');
        $sh->getColumnDimension('B')->setWidth(42);
        $sh->getColumnDimension('D')->setWidth(18);
        $sh->freezePane('C2');
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