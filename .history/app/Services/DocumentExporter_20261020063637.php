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

    /* ============================ WORD ============================ */
    public function word(string $type, array $d): BinaryFileResponse
    {
        $w = new PhpWord();
        $w->setDefaultFontName('Times New Roman');
        $w->setDefaultFontSize(11);
        $w->addTableStyle('grid', ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 50]);
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
                $row = $t->addRow();
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
        if (($s['note_show_logo'] ?? 'OUI') === 'OUI') {
            $right->addImage(Logo::file(), ['width' => 94, 'height' => 53, 'alignment' => 'center']);
        }
        $right->addText($s['note_title'], ['bold' => true], ['alignment' => 'center', 'spaceBefore' => 120, 'spaceAfter' => 120]);
        $right->addText($this->docs->tpl(($a->note_number ?: $s['note_number']) . ' ' . $s['note_subject'], $d), ['bold' => true], ['alignment' => 'both']);
        $right->addText('SESSION ' . $s['session'], ['bold' => true], ['alignment' => 'center', 'spaceBefore' => 60]);

        $sec->addTextBreak(1);
        $sec->addText($this->docs->tpl($s['note_intro'], $d), [], ['alignment' => 'both', 'lineHeight' => 1.5, 'indentation' => ['firstLine' => 708], 'spaceAfter' => 120]);

        $t = $sec->addTable(['borderSize' => 4, 'borderColor' => '000000', 'cellMarginLeft' => 70, 'cellMarginRight' => 70]);
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

    /** Fiche de présence (paysage, 15 colonnes max par feuille, une feuille par série de jours). */
    private function wordPresence(PhpWord $w, array $d): void
    {
        $s = $d['s'];
        $w->setDefaultParagraphStyle(['spaceAfter' => 0, 'spaceBefore' => 0]);
        $total = 15400;
        $idw = max(25, min(60, (int) $s['presence_id_width']));
        $idTotal = (int) round($total * $idw / 100);
        $wN = 450; $wIm = 1000; $wLoc = 1500; $wName = $idTotal - $wN - $wIm - $wLoc;
        $rowH = max(18, (int) $s['presence_row_height']) * 20;
        $left = preg_split('/\R/', $s['presence_header_left']);
        $nSeries = count($d['series']);

        foreach ($d['series'] as $si => $sr) {
            $cols = $sr['cols'];
            $dayW = max(300, intdiv($total - $idTotal, $cols));
            $sec = $w->addSection(['orientation' => 'landscape', 'marginTop' => 500, 'marginBottom' => 500, 'marginLeft' => 720, 'marginRight' => 720]);

            $head = $sec->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF']);
            $r = $head->addRow();
            $lc = $r->addCell(6200);
            foreach ($left as $ln) $lc->addText($ln, ['size' => 9], ['alignment' => 'center']);
            $rc = $r->addCell(9200);
            if (($s['note_show_logo'] ?? 'OUI') === 'OUI') {
                $rc->addImage(Logo::file(), ['width' => 94, 'height' => 53, 'alignment' => 'center']);
            }
            $rc->addText($this->docs->tpl($s['presence_title'], $d), ['bold' => true, 'size' => 10], ['alignment' => 'center']);
            $rc->addText($this->docs->tpl($s['presence_line1'], $d), ['bold' => true, 'size' => 10], ['alignment' => 'center']);
            if (trim($s['presence_line2']) !== '') $rc->addText($this->docs->tpl($s['presence_line2'], $d), ['size' => 9], ['alignment' => 'center']);
            if ($nSeries > 1) $rc->addText("Feuille " . ($si + 1) . "/$nSeries — jours {$sr['from']} à {$sr['to']}", ['italic' => true, 'size' => 9], ['alignment' => 'center']);
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
        $sec = $w->addSection(['marginTop' => 700, 'marginBottom' => 700, 'marginLeft' => 800, 'marginRight' => 800]);
        $sec->addText($s['ministry'], ['bold' => true, 'size' => 10]);
        $sec->addText($this->docs->tpl($s['etat_title'], $d), ['bold' => true, 'size' => 16], ['alignment' => 'center']);
        $sec->addText($this->docs->tpl($s['etat_line1'], $d), ['bold' => true], ['alignment' => 'center']);
        $sec->addText($this->docs->tpl($s['etat_line2'], $d), ['size' => 9, 'italic' => true], ['alignment' => 'center']);
        $t = $sec->addTable('grid');
        $cols = [[500, 'N°'], [3300, 'NOM ET PRENOMS'], [900, 'IM'], [1200, 'LOCALITE'], [600, 'Jours'], [1100, 'Taux/jour'], [1300, 'Montant (Ar)'], [1500, 'ÉMARGEMENT']];
        $h = $t->addRow(null, ['tblHeader' => true]);
        foreach ($cols as [$wd, $tx]) $h->addCell($wd)->addText($tx, ['bold' => true, 'size' => 9], ['alignment' => 'center', 'spaceAfter' => 0]);
        foreach ($d['agents'] as $i => $ag) {
            $r = $t->addRow(380);
            $vals = [(string) ($i + 1), $ag->name, (string) $ag->im, (string) $ag->locality, (string) $a->days, $this->nf($a->rate), $this->nf($d['amount']), ''];
            foreach ($cols as $k => [$wd]) {
                $al = $k === 1 ? 'left' : (in_array($k, [5, 6]) ? 'right' : 'center');
                $r->addCell($wd)->addText($vals[$k], ['size' => 9], ['alignment' => $al, 'spaceAfter' => 0]);
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
        $sec = $w->addSection(['marginTop' => 700, 'marginBottom' => 700, 'marginLeft' => 800, 'marginRight' => 800]);
        $sec->addText($s['ministry'], ['bold' => true, 'size' => 10]);
        $sec->addText($this->docs->tpl($s['decompte_title'], $d), ['bold' => true, 'size' => 15], ['alignment' => 'center', 'spaceBefore' => 120]);
        $sec->addText($this->docs->tpl($s['decompte_line1'], $d), ['bold' => true], ['alignment' => 'center']);
        $sec->addText($this->docs->tpl($s['decompte_line2'], $d), ['size' => 9, 'italic' => true], ['alignment' => 'center', 'spaceAfter' => 120]);

        $cols = [[450, 'N°'], [2900, 'NOM ET PRENOMS'], [850, 'IM'], [1200, 'LOCALITE'], [500, 'Jours'], [850, 'Taux/jour'], [1100, 'Montant brut'], [900, 'IRSA ' . $this->docs->rate($d['irsaRate']) . ' %'], [1100, 'Net à percevoir']];
        $span6 = 450 + 2900 + 850 + 1200 + 500 + 850;
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
                $vals = [(string) ($ch['offset'] + $k + 1), $ag->name, (string) $ag->im, (string) $ag->locality, (string) $a->days, $this->nf($a->rate), $this->nf($d['amount']), $this->nf($d['irsaAmt']), $this->nf($d['net'])];
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
        foreach (['A' => 10, 'B' => 6, 'C' => 48, 'D' => 12, 'E' => 24] as $c => $wd) $sh->getColumnDimension($c)->setWidth($wd);
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

    /** Une feuille Excel par série de jours (15 colonnes max). */
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
            foreach (['A' => 5, 'B' => 40, 'C' => 11, 'D' => 18] as $c => $wd) $sh->getColumnDimension($c)->setWidth($wd);
            for ($k = 0; $k < $cols; $k++) $sh->getColumnDimension(Coordinate::stringFromColumnIndex($first + $k))->setWidth(8.5);

            foreach ($lines as $i => $ln) {
                $r = $i + 1;
                $sh->setCellValue("A$r", $ln);
                $sh->mergeCells("A$r:D$r");
                $sh->getStyle("A$r")->getAlignment()->setHorizontal('center');
                $sh->getStyle("A$r")->getFont()->setSize(9);
            }
            if (($s['note_show_logo'] ?? 'OUI') === 'OUI') {
                $dr = new Drawing();
                $dr->setName('Logo');
                $dr->setPath(Logo::file());
                $dr->setHeight(58);
                $dr->setCoordinates($firstCol . '1');
                $dr->setOffsetX(10);
                $dr->setWorksheet($sh);
                $sh->getRowDimension(1)->setRowHeight(24);
                $sh->getRowDimension(2)->setRowHeight(24);
            }
            $title = $this->docs->tpl($s['presence_title'], $d) . "\n" . $this->docs->tpl($s['presence_line1'], $d);
            if (trim($s['presence_line2']) !== '') $title .= "\n" . $this->docs->tpl($s['presence_line2'], $d);
            if ($nSeries > 1) $title .= "\nFeuille " . ($si + 1) . "/$nSeries — jours {$sr['from']} à {$sr['to']}";
            $sh->setCellValue($firstCol . '3', $title);
            $sh->mergeCells($firstCol . "3:{$last}7");
            $sh->getStyle($firstCol . '3')->getAlignment()->setWrapText(true)->setHorizontal('center')->setVertical('center');
            $sh->getStyle($firstCol . '3')->getFont()->setBold(true);

            $top = max(count($lines), 7) + 2;
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
                $sh->getStyle("A$row:D$row")->getAlignment()->setVertical('center')->setWrapText(true);
                $sh->getStyle("A$row")->getAlignment()->setHorizontal('center');
                $sh->getStyle("C$row:D$row")->getAlignment()->setHorizontal('center');
                $sh->getRowDimension($row)->setRowHeight($rh);
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
        $sh->setTitle('État