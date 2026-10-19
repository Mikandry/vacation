<?php
namespace App\Services;

use App\Models\Activity;
use App\Models\Agent;
use App\Support\Words;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Support\Flag;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class DocumentExporter
{
    public function __construct(private DocumentService $docs) {}

    public const TITLES = ['decision' => 'Décision', 'presence' => 'Feuille de présence', 'etat' => 'État de vacations'];

    private function send(string $tmp, string $name): BinaryFileResponse
    {
        return response()->download($tmp, $name)->deleteFileAfterSend(true);
    }
    private function tmp(): string { return tempnam(sys_get_temp_dir(), 'rep'); }

    /* ======================= WORD ======================= */
    public function word(string $type, array $d): BinaryFileResponse
    {
        $w = new PhpWord();
        $w->setDefaultFontName('Times New Roman');
        $w->setDefaultFontSize(11);
        $w->addTableStyle('grid', ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 50]);
        match ($type) {
            'decision' => $this->wordDecision($w, $d),
            'presence' => $this->wordPresence($w, $d),
            'etat' => $this->wordEtat($w, $d),
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
        $c = $r->addCell(3800); $c->addText($s['ministry'], [], ['alignment' => 'center']); $c->addText('--------------', [], ['alignment' => 'center']);
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
            foreach ([[900, "N° d’ordre"], [600, 'n°'], [4700, 'NOM ET PRENOMS'], [1200, 'IM'], [2400, 'LOCALITE DE SERVICE']] as [$wd, $tx])
                $h->addCell($wd)->addText($tx, ['bold' => true, 'size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
            foreach ($ch['rows'] as $k => $a) {
                $row = $t->addRow();
                $row->addCell(900)->addText((string)($ch['offset'] + $k + 1), ['size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
                $row->addCell(600)->addText((string)($k + 1), ['size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
                $row->addCell(4700)->addText($a->name, ['size' => 10], ['spaceAfter' => 0]);
                $row->addCell(1200)->addText((string)$a->im, ['size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
                $row->addCell(2400)->addText((string)$a->locality, ['size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
            }
            $sec->addText($this->docs->pageTail($ch), ['bold' => true, 'italic' => true, 'size' => 10]);
            if ($i < $last) $sec->addPageBreak();
        }
        $sec->addText("ARRETEE LA PRESENTE DECISION AU NOMBRE DE {$d['nWords']} ({$d['n']}) AGENTS.", ['bold' => true]);
        $sec->addText($this->docs->article2($d), [], ['alignment' => 'both']);
        $sec->addText('Cette indemnité est imputable sur la ligne budgétaire :');
        $b = $sec->addTable('grid');
        $r1 = $b->addRow(); $r2 = $b->addRow();
        foreach ([['Mission', 'budget_mission'], ['Indicateur d’objectif', 'budget_indicator'], ['Section', 'budget_section'], ['Compte', 'budget_account'], ['Code ORDSEC', 'budget_ordsec']] as [$lab, $key]) {
            $r1->addCell(1960)->addText($lab, ['bold' => true, 'size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
            $r2->addCell(1960)->addText($s[$key], ['size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
        }
        $sec->addTextBreak(1);
        $sec->addText('Article 3 : La présente décision sera enregistrée et communiquée partout où besoin sera.');
        $sec->addText($this->docs->signDate($d), [], ['alignment' => 'right']);
        $sec->addText($s['signatory'], ['bold' => true], ['alignment' => 'right']);
    }

        private function wordPresence(PhpWord $w, array $d): void
    {
        $a = $d['a'];
        $s = $d['s'];
        $days = max(1, (int) $a->days);
        $sec = $w->addSection(['orientation' => 'landscape', 'marginTop' => 600, 'marginBottom' => 600, 'marginLeft' => 700, 'marginRight' => 700]);
        $sec->addText($s['ministry'], ['bold' => true, 'size' => 10]);
        $sec->addText($this->docs->tpl($s['presence_title'], $d), ['bold' => true, 'size' => 16], ['alignment' => 'center']);
        $sec->addText($this->docs->tpl($s['presence_line1'], $d), ['bold' => true], ['alignment' => 'center']);
        $sec->addText($this->docs->tpl($s['presence_line2'], $d), ['size' => 10], ['alignment' => 'center']);

        $total = 15400;
        $idw = max(25, min(60, (int) ($s['presence_id_width'] ?? 40)));
        $idTotal = (int) round($total * $idw / 100);
        $wN = 420; $wIm = 900; $wLoc = 1250;
        $wName = $idTotal - $wN - $wIm - $wLoc;
        $dayW = max(300, intdiv($total - $idTotal, $days));

        $t = $sec->addTable('grid');
        $h = $t->addRow(null, ['tblHeader' => true]);
        foreach ([[$wN, 'N°'], [$wName, 'NOM ET PRENOMS'], [$wIm, 'IM'], [$wLoc, 'LOCALITE']] as [$wd, $tx])
            $h->addCell($wd)->addText($tx, ['bold' => true, 'size' => 8], ['alignment' => 'center', 'spaceAfter' => 0]);
        for ($k = 0; $k < $days; $k++) {
            $c = $h->addCell($dayW);
            $c->addText((string) ($k + 1), ['bold' => true, 'size' => 7], ['alignment' => 'center', 'spaceAfter' => 0]);
            if ($d['dates']) $c->addText($d['dates'][$k], ['size' => 5], ['alignment' => 'center', 'spaceAfter' => 0]);
        }
        foreach ($d['agents'] as $i => $ag) {
            $r = $t->addRow(420);
            $r->addCell($wN)->addText((string) ($i + 1), ['size' => 8], ['alignment' => 'center', 'spaceAfter' => 0]);
            $r->addCell($wName)->addText($ag->name, ['size' => 8], ['spaceAfter' => 0]);
            $r->addCell($wIm)->addText((string) $ag->im, ['size' => 8], ['alignment' => 'center', 'spaceAfter' => 0]);
            $r->addCell($wLoc)->addText((string) $ag->locality, ['size' => 8], ['alignment' => 'center', 'spaceAfter' => 0]);
            for ($k = 0; $k < $days; $k++) $r->addCell($dayW)->addText('', ['size' => 8], ['spaceAfter' => 0]);
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
            $vals = [(string) ($i + 1), $ag->name, (string) $ag->im, (string) $ag->locality, (string) $a->days,
                number_format($a->rate, 0, ',', ' '), number_format($d['amount'], 0, ',', ' '), ''];
            foreach ($cols as $k => [$wd]) {
                $al = $k === 1 ? 'left' : (in_array($k, [5, 6]) ? 'right' : 'center');
                $r->addCell($wd)->addText($vals[$k], ['size' => 9], ['alignment' => $al, 'spaceAfter' => 0]);
            }
        }
        $sec->addTextBreak(1);
        $sec->addText("TOTAL : {$d['n']} agent(s) — " . number_format($d['totalMoney'], 0, ',', ' ') . ' Ar', ['bold' => true]);
        $sec->addText($this->docs->tpl($s['etat_footer'], $d), ['italic' => true]);
        $sec->addText($this->docs->signDate($d), [], ['alignment' => 'right']);
    }

    /* ======================= EXCEL ======================= */
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
        $sh = $ss->getActiveSheet();
        $ss->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        $a = $d['a']; $s = $d['s'];
        if ($type === 'decision') {
            $sh->setTitle('Décision');
            foreach (['A' => 10, 'B' => 6, 'C' => 48, 'D' => 12, 'E' => 24] as $c => $wd) $sh->getColumnDimension($c)->setWidth($wd);
            $row = 1;
            $sh->setCellValue("A$row", $s['ministry']); $sh->mergeCells("A$row:C$row"); $row++;
            $sh->setCellValue("A$row", $this->docs->decisionTitle($d)); $sh->mergeCells("A$row:E$row");
            $sh->getStyle("A$row")->getAlignment()->setWrapText(true)->setVertical('top'); $sh->getStyle("A$row")->getFont()->setBold(true);
            $sh->getRowDimension($row)->setRowHeight(48); $row += 2;
            $sh->setCellValue("A$row", $s['authority']); $sh->mergeCells("A$row:E$row"); $sh->getStyle("A$row")->getFont()->setBold(true); $row++;
            foreach ($d['visas'] as $v) {
                $sh->setCellValue("A$row", $v->text); $sh->mergeCells("A$row:E$row");
                $sh->getStyle("A$row")->getAlignment()->setWrapText(true)->setVertical('top');
                $sh->getRowDimension($row)->setRowHeight(13 * max(1, ceil(mb_strlen($v->text) / 105)) + 2); $row++;
            }
            $row++; $sh->setCellValue("A$row", 'DECIDE :'); $sh->getStyle("A$row")->getFont()->setBold(true); $row++;
            $sh->setCellValue("A$row", $this->docs->article1($d)); $sh->mergeCells("A$row:E$row");
            $sh->getStyle("A$row")->getAlignment()->setWrapText(true); $sh->getRowDimension($row)->setRowHeight(30); $row += 2;
            $start = $row;
            foreach (["N° d’ordre", 'n°', 'NOM ET PRENOMS', 'IM', 'LOCALITE DE SERVICE'] as $i => $t) $sh->setCellValue(Coordinate::stringFromColumnIndex($i + 1) . $row, $t);
            $this->head($sh, "A$row:E$row"); $row++;
            foreach ($d['chunks'] as $ch) {
                foreach ($ch['rows'] as $k => $ag) {
                    $sh->fromArray([$ch['offset'] + $k + 1, $k + 1, $ag->name, (string)$ag->im, (string)$ag->locality], null, "A$row", true);
                    $sh->setCellValueExplicit("D$row", (string)$ag->im, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $row++;
                }
                if ($ch['rows']->count()) { $sh->setCellValue("A$row", $this->docs->pageTail($ch)); $sh->mergeCells("A$row:E$row"); $sh->getStyle("A$row")->getFont()->setItalic(true)->setBold(true); $row++; }
            }
            $this->box($sh, "A$start:E" . ($row - 1));
            $row++;
            $sh->setCellValue("A$row", "ARRETEE LA PRESENTE DECISION AU NOMBRE DE {$d['nWords']} ({$d['n']}) AGENTS."); $sh->getStyle("A$row")->getFont()->setBold(true); $row += 2;
            $sh->setCellValue("A$row", $this->docs->article2($d)); $sh->mergeCells("A$row:E$row"); $sh->getStyle("A$row")->getAlignment()->setWrapText(true); $sh->getRowDimension($row)->setRowHeight(30); $row++;
            $sh->setCellValue("A$row", 'Cette indemnité est imputable sur la ligne budgétaire :'); $row++;
            $sh->fromArray([['Mission', 'Indicateur d’objectif', 'Section', 'Compte', 'Code ORDSEC'], [$s['budget_mission'], $s['budget_indicator'], $s['budget_section'], $s['budget_account'], $s['budget_ordsec']]], null, "A$row");
            $this->box($sh, "A$row:E" . ($row + 1)); $sh->getStyle("A$row:E$row")->getFont()->setBold(true); $row += 3;
            $sh->setCellValue("A$row", 'Article 3 : La présente décision sera enregistrée et communiquée partout où besoin sera.'); $row += 2;
            $sh->setCellValue("A$row", $this->docs->signDate($d)); $sh->mergeCells("A$row:E$row"); $sh->getStyle("A$row")->getAlignment()->setHorizontal('right'); $row++;
            $sh->setCellValue("A$row", $s['signatory']); $sh->mergeCells("A$row:E$row"); $sh->getStyle("A$row")->getAlignment()->setHorizontal('right'); $sh->getStyle("A$row")->getFont()->setBold(true);
            $this->setup($sh, false);
        } elseif ($type === 'presence') {
            $days = max(1, (int)$a->days); $sh->setTitle('Présence');
            $sh->getColumnDimension('A')->setWidth(5); $sh->getColumnDimension('B')->setWidth(40); $sh->getColumnDimension('C')->setWidth(10); $sh->getColumnDimension('D')->setWidth(16);
            $last = Coordinate::stringFromColumnIndex(4 + $days);
            for ($k = 0; $k < $days; $k++) $sh->getColumnDimension(Coordinate::stringFromColumnIndex(5 + $k))->setWidth(4.2);
            $sh->setCellValue('A1', $s['ministry']); $sh->setCellValue('A2', 'FEUILLE DE PRÉSENCE'); $sh->mergeCells("A2:{$last}2");
            $sh->getStyle('A2')->getFont()->setBold(true)->setSize(16); $sh->getStyle('A2')->getAlignment()->setHorizontal('center');
            $sh->setCellValue('A3', "Activité : {$a->name} — Session {$s['session']} — {$days} jour(s) — {$d['n']} agents"); $sh->mergeCells("A3:{$last}3");
            $sh->getStyle('A3')->getFont()->setBold(true); $sh->getStyle('A3')->getAlignment()->setHorizontal('center');
            $r0 = 5; $sh->fromArray(['N°', 'NOM ET PRENOMS', 'IM', 'LOCALITE DE SERVICE'], null, "A$r0");
            foreach (['A', 'B', 'C', 'D'] as $c) $sh->mergeCells("{$c}$r0:{$c}" . ($r0 + 1));
            for ($k = 0; $k < $days; $k++) {
                $c = Coordinate::stringFromColumnIndex(5 + $k);
                $sh->setCellValue("{$c}$r0", $k + 1);
                if ($d['dates']) { $sh->setCellValue("{$c}" . ($r0 + 1), $d['dates'][$k]); $sh->getStyle("{$c}" . ($r0 + 1))->getAlignment()->setTextRotation(90)->setHorizontal('center'); $sh->getStyle("{$c}" . ($r0 + 1))->getFont()->setSize(7); }
            }
            $sh->getRowDimension($r0 + 1)->setRowHeight($d['dates'] ? 32 : 6);
            $this->head($sh, "A$r0:{$last}" . ($r0 + 1));
            $row = $r0 + 2;
            foreach ($d['agents'] as $i => $ag) {
                $sh->setCellValue("A$row", $i + 1); $sh->setCellValue("B$row", $ag->name);
                $sh->setCellValueExplicit("C$row", (string)$ag->im, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING); $sh->setCellValue("D$row", (string)$ag->locality);
                $sh->getRowDimension($row)->setRowHeight(20); $row++;
            }
            $this->box($sh, "A$r0:{$last}" . max($row - 1, $r0 + 1));
            $sh->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($r0, $r0 + 1);
            $this->setup($sh, true);
        } else { // etat
            $sh->setTitle('État de vacations');
            foreach (['A' => 5, 'B' => 42, 'C' => 11, 'D' => 16, 'E' => 7, 'F' => 12, 'G' => 14, 'H' => 22] as $c => $wd) $sh->getColumnDimension($c)->setWidth($wd);
            $sh->setCellValue('A1', $s['ministry']); $sh->setCellValue('A2', 'ÉTAT DE VACATIONS'); $sh->mergeCells('A2:H2');
            $sh->getStyle('A2')->getFont()->setBold(true)->setSize(16); $sh->getStyle('A2')->getAlignment()->setHorizontal('center');
            $sh->setCellValue('A3', "Activité : {$a->name} — Session {$s['session']}"); $sh->mergeCells('A3:H3'); $sh->getStyle('A3')->getAlignment()->setHorizontal('center');
            $sh->setCellValue('A4', "{$a->days} jour(s) × {$a->rate} Ar/jour — {$s['ref_arrete']}"); $sh->mergeCells('A4:H4'); $sh->getStyle('A4')->getAlignment()->setHorizontal('center');
            $sh->fromArray(['N°', 'NOM ET PRENOMS', 'IM', 'LOCALITE', 'Jours', 'Taux / jour (Ar)', 'Montant (Ar)', 'ÉMARGEMENT'], null, 'A6'); $this->head($sh, 'A6:H6');
            $row = 7;
            foreach ($d['agents'] as $i => $ag) {
                $sh->setCellValue("A$row", $i + 1); $sh->setCellValue("B$row", $ag->name);
                $sh->setCellValueExplicit("C$row", (string)$ag->im, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sh->setCellValue("D$row", (string)$ag->locality); $sh->setCellValue("E$row", $a->days); $sh->setCellValue("F$row", $a->rate);
                $sh->setCellValue("G$row", "=E$row*F$row"); $sh->getRowDimension($row)->setRowHeight(20); $row++;
            }
            $sh->setCellValue("A$row", 'TOTAL'); $sh->mergeCells("A$row:F$row"); $sh->setCellValue("G$row", $d['n'] ? "=SUM(G7:G" . ($row - 1) . ")" : 0);
            $sh->getStyle("A$row:G$row")->getFont()->setBold(true);
            $sh->getStyle("F7:G$row")->getNumberFormat()->setFormatCode('#,##0');
            $this->box($sh, "A6:H$row");
            $sh->setCellValue('A' . ($row + 2), "Arrêté le présent état à la somme de {$d['totalWords']} ARIARY."); $sh->getStyle('A' . ($row + 2))->getFont()->setItalic(true);
            $sh->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(6, 6);
            $this->setup($sh, false);
        }
        return $this->save($ss, "{$type}-{$d['file']}.xlsx");
    }

    /* ======= Récapitulatif : matrice agents × activités ======= */
    public function recapExcel(array $b): BinaryFileResponse
    {
        $ss = new Spreadsheet(); $sh = $ss->getActiveSheet(); $sh->setTitle('Récapitulatif');
        $ss->getDefaultStyle()->getFont()->setName('Arial')->setSize(9);
        $acts = $b['acts']; $nA = $acts->count();
        $head = ['N°', 'NOM ET PRENOMS', 'IM', 'LOCALITE', 'TYPE'];
        foreach ($acts as $a) $head[] = $a->code;
        array_push($head, 'Nb activités', 'Montant session (Ar)', 'Total retenu (Ar)', 'Statut', 'Activités reçues');
        $sh->fromArray($head, null, 'A1');
        $lastCol = Coordinate::stringFromColumnIndex(count($head));
        $this->head($sh, "A1:{$lastCol}1");
        $row = 2;
        foreach ($b['rows'] as $i => $r) {
            $ag = $r['agent']; $line = [$i + 1, $ag->name, null, $ag->locality, $ag->type];
            foreach ($acts as $a) $line[] = in_array($a->id, $r['ids']) ? 1 : '';
            array_push($line, $r['count'], $r['amount'], $r['total'], $r['status'], $r['acts']->pluck('code')->implode(', '));
            $sh->fromArray($line, null, "A$row");
            $sh->setCellValueExplicit("C$row", (string)$ag->im, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $row++;
        }
        $sh->setCellValue("A$row", 'TOTAL'); 
        for ($k = 0; $k < $nA; $k++) { $c = Coordinate::stringFromColumnIndex(6 + $k); $sh->setCellValue("$c$row", "=SUM({$c}2:{$c}" . ($row - 1) . ")"); }
        $sh->getStyle("A$row:{$lastCol}$row")->getFont()->setBold(true);
        $this->box($sh, "A1:{$lastCol}$row");
        $fmt = Coordinate::stringFromColumnIndex(6 + $nA + 1);
        $sh->getStyle(Coordinate::stringFromColumnIndex(6 + $nA + 1) . "2:" . Coordinate::stringFromColumnIndex(6 + $nA + 2) . "$row")->getNumberFormat()->setFormatCode('#,##0');
        $sh->getColumnDimension('B')->setWidth(42); $sh->getColumnDimension('D')->setWidth(14);
        $sh->freezePane('C2'); $this->setup($sh, true);
        return $this->save($ss, 'recapitulatif-agents-' . $b['settings']['session'] . '.xlsx');
    }

    public function agentsExcel(): BinaryFileResponse
    {
        $ss = new Spreadsheet(); $sh = $ss->getActiveSheet(); $sh->setTitle('AGENTS');
        $acts = Activity::orderBy('position')->get();
        $head = ['N°', 'NOM ET PRENOMS', 'IM', 'LOCALITE DE SERVICE', 'TYPE', 'DISPONIBLE', 'CORPS', 'GRADE', 'INDICE', 'FONCTION / OBSERVATION', 'HISTORIQUE (Ar)', 'ORDRE TIRAGE'];
        foreach ($acts as $a) $head[] = $a->code;
        $sh->fromArray($head, null, 'A1'); $this->head($sh, 'A1:' . Coordinate::stringFromColumnIndex(count($head)) . '1');
        $row = 2;
        foreach (Agent::with('exceptions')->orderBy('name')->get() as $i => $ag) {
            $line = [$i + 1, $ag->name, null, $ag->locality, $ag->type, $ag->available ? 'OUI' : 'NON', $ag->corps, $ag->grade, $ag->indice, $ag->function, $ag->history_amount, $ag->draw_order];
            foreach ($acts as $a) $line[] = optional($ag->exceptions->firstWhere('activity_id', $a->id))->mode;
            $sh->fromArray($line, null, "A$row");
            $sh->setCellValueExplicit("C$row", (string)$ag->im, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $row++;
        }
        $sh->getColumnDimension('B')->setWidth(42); $sh->freezePane('C2');
        return $this->save($ss, 'agents-' . date('Ymd') . '.xlsx');
    }
}
