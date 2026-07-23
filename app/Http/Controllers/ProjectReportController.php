<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Models\Project;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpWord\PhpWord;

class ProjectReportController extends Controller
{
    private function reportData(Project $project): array
    {
        $transactions = $project->transactions()->orderBy('transaction_date')->get();
        $budgetAdditions = $transactions->where('type', 'budget_addition');
        $expenses = $transactions->where('type', 'expense');

        $categorySummary = $expenses
            ->groupBy(fn($t) => $t->expenseCategory?->name ?? $t->category ?? 'Uncategorized')
            ->map(fn($g) => $g->sum('amount'))
            ->sortByDesc(fn($v) => $v);

        $reservedProcurement = $project->reserved_procurement;
        $currentBudget = $project->budget
            + $budgetAdditions->sum('amount')
            - $expenses->sum('amount')
            - $reservedProcurement;
        $totalBudget = $project->budget + $budgetAdditions->sum('amount');
        $budgetUtilization = $totalBudget > 0
            ? round((($expenses->sum('amount') + $reservedProcurement) / $totalBudget) * 100, 1)
            : 0;

        return compact(
            'project',
            'transactions',
            'budgetAdditions',
            'expenses',
            'categorySummary',
            'reservedProcurement',
            'currentBudget',
            'totalBudget',
            'budgetUtilization'
        );
    }

    private function getLogoBase64(): string
    {
        $logoPath = SystemSetting::current()->logo_file_path;

        if ($logoPath && file_exists($logoPath)) {
            $mimeType = mime_content_type($logoPath) ?: 'image/png';
            $imageData = base64_encode(file_get_contents($logoPath));

            return "data:{$mimeType};base64,{$imageData}";
        }

        return '';
    }

    // ─────────────────────────────────────────────
    //  EXCEL - Formal Black & White
    // ─────────────────────────────────────────────
    public function downloadExcel(Project $project): Response
    {
        $d = $this->reportData($project);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Financial Report');

        // Black and white only - professional
        $BLACK = '#000000';
        $DARK_GRAY = '#333333';
        $MEDIUM_GRAY = '#666666';
        $LIGHT_GRAY = '#F5F5F5';
        $WHITE = '#FFFFFF';
        $BORDER = '#CCCCCC';

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(35);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);

        $centerAlign = ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER];
        $leftAlign = ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER];
        $rightAlign = ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER];

        // Row 1-2: Company Header
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'FINANCIAL STATEMENT REPORT');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($BLACK));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', $project->name);
        $sheet->getStyle('A2')->getFont()->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($MEDIUM_GRAY));
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Row 3: Report Date
        $sheet->mergeCells('A3:G3');
        $sheet->setCellValue('A3', 'Report Date: ' . now()->format('F d, Y'));
        $sheet->getStyle('A3')->getFont()->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($MEDIUM_GRAY));
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Row 5: Summary Section
        $row = 5;
        
        // Summary Header
        $sheet->mergeCells("A{$row}:G{$row}");
        $sheet->setCellValue("A{$row}", 'EXECUTIVE SUMMARY');
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $WHITE]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $DARK_GRAY]],
            'alignment' => $centerAlign,
        ]);
        $row++;

        // Summary Data - 3 columns x 2 rows
        $summaryItems = [
            ['Initial Budget', '₱' . number_format($project->budget, 2)],
            ['Budget Additions', '₱' . number_format($d['budgetAdditions']->sum('amount'), 2)],
            ['Total Budget', '₱' . number_format($d['totalBudget'], 2)],
            ['Total Expenses', '₱' . number_format($d['expenses']->sum('amount'), 2)],
            ['Current Balance', '₱' . number_format($d['currentBudget'], 2)],
            ['Budget Utilization', $d['budgetUtilization'] . '%'],
        ];

        for ($i = 0; $i < count($summaryItems); $i++) {
            $col = chr(65 + ($i % 3) * 2);
            $colEnd = chr(65 + ($i % 3) * 2 + 1);
            $currentRow = $row + floor($i / 3);
            
            $sheet->mergeCells("{$col}{$currentRow}:{$colEnd}{$currentRow}");
            $sheet->setCellValue("{$col}{$currentRow}", $summaryItems[$i][0]);
            $sheet->getStyle("{$col}{$currentRow}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => $MEDIUM_GRAY]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $LIGHT_GRAY]],
                'alignment' => $centerAlign,
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $BORDER]]]
            ]);
            
            $sheet->setCellValue("{$col}" . ($currentRow + 1), $summaryItems[$i][1]);
            $sheet->getStyle("{$col}" . ($currentRow + 1))->applyFromArray([
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $BLACK]],
                'alignment' => $centerAlign,
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $BORDER]]]
            ]);
        }

        $row += 3;

        // Transactions Section
        $sheet->mergeCells("A{$row}:G{$row}");
        $sheet->setCellValue("A{$row}", 'TRANSACTION LEDGER');
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $WHITE]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $DARK_GRAY]],
            'alignment' => $centerAlign,
        ]);
        $row++;

        // Table Headers
        $headers = ['DATE', 'DESCRIPTION', 'TYPE', 'CATEGORY', 'CLIENT/REFERENCE', 'AMOUNT (₱)', 'RUNNING BALANCE (₱)'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->applyFromArray([
                'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => $WHITE]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $DARK_GRAY]],
                'alignment' => in_array($header, ['AMOUNT (₱)', 'RUNNING BALANCE (₱)']) ? $rightAlign : $centerAlign,
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $BORDER]]]
            ]);
            $col++;
        }
        $row++;

        // Transaction Rows
        $running = $project->budget;
        $rowNum = $row;
        foreach ($d['transactions'] as $idx => $tran) {
            $running += $tran->type === 'budget_addition' ? $tran->amount : -$tran->amount;
            $bgColor = $idx % 2 === 0 ? $WHITE : $LIGHT_GRAY;

            $sheet->setCellValue('A' . $rowNum, $tran->transaction_date->format('Y-m-d'));
            $sheet->setCellValue('B' . $rowNum, $tran->expense_name ?? ($tran->description ?? '—'));
            $sheet->setCellValue('C' . $rowNum, $tran->type === 'budget_addition' ? 'ADDITION' : 'EXPENSE');
            $sheet->setCellValue('D' . $rowNum, $tran->category ?? '—');
            $sheet->setCellValue('E' . $rowNum, $tran->client_name ?? ($tran->invoice_ref ?? '—'));
            $sheet->setCellValue('F' . $rowNum, $tran->amount);
            $sheet->setCellValue('G' . $rowNum, $running);

            $sheet->getStyle('A' . $rowNum . ':G' . $rowNum)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $BORDER]]]
            ]);
            
            // Fix currency formatting - Use PHP currency code instead of symbol
            $sheet->getStyle('F' . $rowNum)->getNumberFormat()->setFormatCode('"PHP"#,##0.00');
            $sheet->getStyle('G' . $rowNum)->getNumberFormat()->setFormatCode('"PHP"#,##0.00');
            $sheet->getStyle('F' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('G' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('C' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $rowNum++;
        }

        $row = $rowNum + 2;

        // Category Summary Section
        $sheet->mergeCells("A{$row}:G{$row}");
        $sheet->setCellValue("A{$row}", 'EXPENSE BREAKDOWN BY CATEGORY');
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $WHITE]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $DARK_GRAY]],
            'alignment' => $centerAlign,
        ]);
        $row++;

        $sheet->setCellValue('A' . $row, 'CATEGORY');
        $sheet->setCellValue('B' . $row, 'AMOUNT (PHP)');
        $sheet->setCellValue('C' . $row, 'PERCENTAGE');
        $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => $WHITE]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $DARK_GRAY]],
            'alignment' => $centerAlign,
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $BORDER]]]
        ]);
        $row++;

        $expenseTotal = $d['expenses']->sum('amount');
        $idx = 0;
        foreach ($d['categorySummary'] as $cat => $amt) {
            $pct = $expenseTotal > 0 ? round($amt / $expenseTotal * 100, 2) : 0;
            $bgColor = $idx % 2 === 0 ? $WHITE : $LIGHT_GRAY;

            $sheet->setCellValue('A' . $row, $cat);
            $sheet->setCellValue('B' . $row, $amt);
            $sheet->setCellValue('C' . $row, $pct . '%');

            $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $BORDER]]]
            ]);
            $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('"PHP"#,##0.00');
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $row++;
            $idx++;
        }

        // Total Row
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue('B' . $row, $expenseTotal);
        $sheet->setCellValue('C' . $row, '100%');
        $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $LIGHT_GRAY]],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $BORDER]]]
        ]);
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('"PHP"#,##0.00');
        $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $row += 2;

        // Footer
        $sheet->mergeCells("A{$row}:G{$row}");
        $sheet->setCellValue("A{$row}", 'This is a computer-generated document. No signature required.');
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font' => ['italic' => true, 'size' => 8, 'color' => ['rgb' => $MEDIUM_GRAY]],
            'alignment' => $centerAlign,
        ]);

        $filename = 'Financial_Report_' . str($project->name)->slug() . '_' . date('Y-m-d') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ─────────────────────────────────────────────
    //  PDF - Formal with Logo (Fixed Currency)
    // ─────────────────────────────────────────────
    public function downloadPdf(Project $project): Response
    {
        $d = $this->reportData($project);
        $html = $this->buildFormalReportHtml($d);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans'); // Changed from Helvetica to support ₱
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('chroot', realpath(base_path()));

        $pdf = new Dompdf($options);
        $pdf->loadHtml($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();

        $filename = 'Financial_Report_' . str($project->name)->slug() . '_' . date('Y-m-d') . '.pdf';
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ─────────────────────────────────────────────
    //  WORD - Formal with Logo
    // ─────────────────────────────────────────────
    public function downloadWord(Project $project): Response
    {
        $d = $this->reportData($project);
        $phpWord = new PhpWord();
        
        $phpWord->setDefaultFontName('Helvetica');
        $phpWord->setDefaultFontSize(10);

        $section = $phpWord->addSection([
            'marginTop' => 1000,
            'marginBottom' => 1000,
            'marginLeft' => 1200,
            'marginRight' => 1200,
        ]);

        // Add logo (if exists)
        $logoPath = SystemSetting::current()->logo_file_path;
        if ($logoPath && file_exists($logoPath)) {
            $section->addImage($logoPath, ['width' => 80, 'height' => 80, 'alignment' => 'center']);
        }

        // Title
        $section->addText('FINANCIAL STATEMENT REPORT', ['bold' => true, 'size' => 18, 'color' => '000000'], ['alignment' => 'center']);
        $section->addText($project->name, ['size' => 12, 'color' => '666666'], ['alignment' => 'center']);
        $section->addText('Report Date: ' . now()->format('F d, Y'), ['size' => 10, 'color' => '666666'], ['alignment' => 'center']);
        $section->addTextBreak(1);

        // Summary Table - Compact 2x3 grid
        $section->addText('SUMMARY', ['bold' => true, 'size' => 12], ['alignment' => 'center']);
        $section->addTextBreak(0.5);

        $summaryTable = $section->addTable(['borderSize' => 1, 'borderColor' => 'CCCCCC', 'cellMargin' => 80]);
        
        $summaryData = [
            ['Initial Budget', '₱' . number_format($project->budget, 2)],
            ['Budget Additions', '₱' . number_format($d['budgetAdditions']->sum('amount'), 2)],
            ['Total Budget', '₱' . number_format($d['totalBudget'], 2)],
            ['Total Expenses', '₱' . number_format($d['expenses']->sum('amount'), 2)],
            ['Current Balance', '₱' . number_format($d['currentBudget'], 2)],
            ['Budget Utilization', $d['budgetUtilization'] . '%'],
        ];

        for ($i = 0; $i < 2; $i++) {
            $row = $summaryTable->addRow();
            for ($j = 0; $j < 3; $j++) {
                $idx = $i * 3 + $j;
                if ($idx < count($summaryData)) {
                    $cell = $row->addCell(2500);
                    $cell->addText($summaryData[$idx][0], ['bold' => true, 'size' => 8, 'color' => '666666'], ['alignment' => 'center']);
                    $cell->addText($summaryData[$idx][1], ['bold' => true, 'size' => 12], ['alignment' => 'center']);
                }
            }
        }

        $section->addTextBreak(1);

        // Transactions Table
        $section->addText('TRANSACTION LEDGER', ['bold' => true, 'size' => 12], ['alignment' => 'center']);
        $section->addTextBreak(0.5);

        $ledgerTable = $section->addTable(['borderSize' => 1, 'borderColor' => 'CCCCCC', 'cellMargin' => 60]);
        
        // Headers
        $headerRow = $ledgerTable->addRow();
        $headers = ['DATE', 'DESCRIPTION', 'TYPE', 'CATEGORY', 'CLIENT', 'AMOUNT', 'BALANCE'];
        foreach ($headers as $header) {
            $headerRow->addCell(null, ['bgColor' => '333333'])->addText($header, ['bold' => true, 'color' => 'FFFFFF', 'size' => 8], ['alignment' => 'center']);
        }

        $running = $project->budget;
        foreach ($d['transactions'] as $idx => $tran) {
            $running += $tran->type === 'budget_addition' ? $tran->amount : -$tran->amount;
            $bgColor = $idx % 2 == 0 ? 'FFFFFF' : 'F5F5F5';
            $row = $ledgerTable->addRow();
            $row->addCell(null, ['bgColor' => $bgColor])->addText($tran->transaction_date->format('Y-m-d'), ['size' => 8]);
            $row->addCell(null, ['bgColor' => $bgColor])->addText($tran->expense_name ?? ($tran->description ?? '—'), ['size' => 8]);
            $row->addCell(null, ['bgColor' => $bgColor])->addText($tran->type === 'budget_addition' ? 'ADD' : 'EXP', ['size' => 8], ['alignment' => 'center']);
            $row->addCell(null, ['bgColor' => $bgColor])->addText($tran->category ?? '—', ['size' => 8]);
            $row->addCell(null, ['bgColor' => $bgColor])->addText($tran->client_name ?? '—', ['size' => 8]);
            $row->addCell(null, ['bgColor' => $bgColor])->addText('₱' . number_format($tran->amount, 2), ['size' => 8], ['alignment' => 'right']);
            $row->addCell(null, ['bgColor' => $bgColor])->addText('₱' . number_format($running, 2), ['size' => 8], ['alignment' => 'right']);
        }

        $section->addTextBreak(1);

        // Category Summary
        $section->addText('EXPENSE BREAKDOWN BY CATEGORY', ['bold' => true, 'size' => 12], ['alignment' => 'center']);
        $section->addTextBreak(0.5);

        $catTable = $section->addTable(['borderSize' => 1, 'borderColor' => 'CCCCCC']);
        $catHeaderRow = $catTable->addRow();
        $catHeaderRow->addCell(null, ['bgColor' => '333333'])->addText('CATEGORY', ['bold' => true, 'color' => 'FFFFFF']);
        $catHeaderRow->addCell(null, ['bgColor' => '333333'])->addText('AMOUNT (₱)', ['bold' => true, 'color' => 'FFFFFF'], ['alignment' => 'right']);
        $catHeaderRow->addCell(null, ['bgColor' => '333333'])->addText('PERCENTAGE', ['bold' => true, 'color' => 'FFFFFF'], ['alignment' => 'right']);

        $expenseTotal = $d['expenses']->sum('amount');
        $idx = 0;
        foreach ($d['categorySummary'] as $cat => $amt) {
            $pct = $expenseTotal > 0 ? round($amt / $expenseTotal * 100, 2) : 0;
            $bgColor = $idx % 2 == 0 ? 'FFFFFF' : 'F5F5F5';
            $row = $catTable->addRow();
            $row->addCell(null, ['bgColor' => $bgColor])->addText($cat);
            $row->addCell(null, ['bgColor' => $bgColor])->addText('₱' . number_format($amt, 2), [], ['alignment' => 'right']);
            $row->addCell(null, ['bgColor' => $bgColor])->addText($pct . '%', [], ['alignment' => 'right']);
            $idx++;
        }
        
        // Total row
        $totalRow = $catTable->addRow();
        $totalRow->addCell(null, ['bgColor' => 'F0F0F0'])->addText('TOTAL', ['bold' => true]);
        $totalRow->addCell(null, ['bgColor' => 'F0F0F0'])->addText('₱' . number_format($expenseTotal, 2), ['bold' => true], ['alignment' => 'right']);
        $totalRow->addCell(null, ['bgColor' => 'F0F0F0'])->addText('100%', ['bold' => true], ['alignment' => 'right']);

        // Footer
        $section->addTextBreak(1);
        $section->addText('This is a computer-generated document. No signature required.', ['italic' => true, 'size' => 8, 'color' => '999999'], ['alignment' => 'center']);

        $filename = 'Financial_Report_' . str($project->name)->slug() . '_' . date('Y-m-d') . '.docx';
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ─────────────────────────────────────────────
    //  Formal HTML Template for PDF (Fixed Currency)
    // ─────────────────────────────────────────────
    private function buildFormalReportHtml(array $d): string
    {
        $project = $d['project'];
        $transactions = $d['transactions'];
        $categorySummary = $d['categorySummary'];
        $expenseTotal = $d['expenses']->sum('amount');
        $logoBase64 = $this->getLogoBase64();

        $running = $project->budget;
        $rows = '';
        foreach ($transactions as $tran) {
            $running += $tran->type === 'budget_addition' ? $tran->amount : -$tran->amount;
            $rows .= "
            <tr>
                <td>" . $tran->transaction_date->format('Y-m-d') . "</td>
                <td>" . htmlspecialchars($tran->expense_name ?? $tran->description ?? '—') . "</td>
                <td>" . ($tran->type === 'budget_addition' ? 'ADDITION' : 'EXPENSE') . "</td>
                <td>" . htmlspecialchars($tran->category ?? '—') . "</td>
                <td>" . htmlspecialchars($tran->client_name ?? '—') . "</td>
                <td class='number'>₱" . number_format($tran->amount, 2) . "</td>
                <td class='number'>₱" . number_format($running, 2) . "</td>
            </tr>";
        }

        $catRows = '';
        foreach ($categorySummary as $cat => $amt) {
            $pct = $expenseTotal > 0 ? round($amt / $expenseTotal * 100, 2) : 0;
            $catRows .= "
            <tr>
                <td>" . htmlspecialchars($cat) . "</td>
                <td class='number'>₱" . number_format($amt, 2) . "</td>
                <td class='number'>{$pct}%</td>
            </tr>";
        }

        $logoHtml = '';
        if ($logoBase64) {
            $logoHtml = "<div class='logo'><img src='{$logoBase64}' alt='Logo'></div>";
        }

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Financial Report - {$project->name}</title>
            <style>
                @page {
                    margin: 1.5cm;
                    size: A4;
                }
                body {
                    font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
                    font-size: 10pt;
                    line-height: 1.4;
                    color: #000000;
                    margin: 0;
                    padding: 0;
                }
                .header {
                    text-align: center;
                    margin-bottom: 25px;
                    padding-bottom: 15px;
                    border-bottom: 2px solid #000000;
                }
                .logo {
                    margin-bottom: 8px;
                }
                .logo img {
                    max-width: 60px;
                    max-height: 60px;
                }
                .company-name {
                    font-size: 16pt;
                    font-weight: bold;
                    letter-spacing: 2px;
                    margin-bottom: 5px;
                }
                .project-name {
                    font-size: 13pt;
                    font-weight: bold;
                    margin-top: 8px;
                    color: #000000;
                }
                .report-date {
                    font-size: 9pt;
                    color: #555555;
                    margin-top: 5px;
                }
                
                /* Summary section - compact */
                .summary-section {
                    margin: 20px 0;
                }
                .summary-title {
                    font-size: 11pt;
                    font-weight: bold;
                    margin-bottom: 10px;
                    padding-bottom: 3px;
                    border-bottom: 1px solid #000000;
                }
                .summary-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 10px;
                }
                .summary-table td {
                    border: none;
                    padding: 6px 8px;
                    vertical-align: top;
                }
                .summary-label {
                    font-weight: bold;
                    width: 25%;
                    color: #333333;
                }
                .summary-value {
                    width: 25%;
                    font-weight: normal;
                }
                
                /* Section titles */
                .section-title {
                    font-size: 11pt;
                    font-weight: bold;
                    margin: 20px 0 10px 0;
                    padding-bottom: 5px;
                    border-bottom: 1.5px solid #000000;
                }
                
                /* Tables */
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                th {
                    background: #333333;
                    color: #ffffff;
                    padding: 8px 6px;
                    font-size: 9pt;
                    font-weight: bold;
                    text-align: center;
                    border: 1px solid #555555;
                }
                td {
                    padding: 6px;
                    border: 1px solid #cccccc;
                    font-size: 9pt;
                    vertical-align: top;
                }
                .number {
                    text-align: right;
                    font-family: 'DejaVu Sans', monospace;
                }
                tr:nth-child(even) {
                    background: #f9f9f9;
                }
                
                /* Footer */
                .footer {
                    margin-top: 30px;
                    padding-top: 10px;
                    border-top: 1px solid #cccccc;
                    text-align: center;
                    font-size: 8pt;
                    color: #777777;
                    font-style: italic;
                }
            </style>
        </head>
        <body>
            <div class='header'>
                {$logoHtml}
                <div class='company-name'>FINANCIAL STATEMENT REPORT</div>
                <div class='project-name'>" . htmlspecialchars($project->name) . "</div>
                <div class='report-date'>Generated: " . now()->format('F d, Y') . "</div>
            </div>

            <!-- Summary Section - Compact table format -->
            <div class='summary-section'>
                <div class='summary-title'>SUMMARY</div>
                <table class='summary-table'>
                    <tr>
                        <td class='summary-label'>Initial Budget::</td>
                        <td class='summary-value'>₱" . number_format($project->budget, 2) . "</td>
                        <td class='summary-label'>Budget Additions:</td>
                        <td class='summary-value'>₱" . number_format($d['budgetAdditions']->sum('amount'), 2) . "</td>
                    </tr>
                    <tr>
                        <td class='summary-label'>Total Budget:</td>
                        <td class='summary-value'>₱" . number_format($d['totalBudget'], 2) . "</td>
                        <td class='summary-label'>Total Expenses:</td>
                        <td class='summary-value'>₱" . number_format($d['expenses']->sum('amount'), 2) . "</td>
                    </tr>
                    <tr>
                        <td class='summary-label'>Current Balance:</td>
                        <td class='summary-value'>₱" . number_format($d['currentBudget'], 2) . "</td>
                        <td class='summary-label'>Budget Utilization:</td>
                        <td class='summary-value'>{$d['budgetUtilization']}%</td>
                    </tr>
                </table>
            </div>

            <!-- Transaction Ledger -->
            <div class='section-title'>TRANSACTION LEDGER</div>
            <table>
                <thead>
                    <tr>
                        <th>DATE</th>
                        <th>DESCRIPTION</th>
                        <th>TYPE</th>
                        <th>CATEGORY</th>
                        <th>CLIENT/REF</th>
                        <th>AMOUNT</th>
                        <th>BALANCE</th>
                    </tr>
                </thead>
                <tbody>{$rows}</tbody>
            </table>

            <!-- Expense Breakdown -->
            <div class='section-title'>EXPENSE BREAKDOWN BY CATEGORY</div>
            <table>
                <thead>
                    <tr>
                        <th>CATEGORY</th>
                        <th>AMOUNT</th>
                        <th>PERCENTAGE</th>
                    </tr>
                </thead>
                <tbody>
                    {$catRows}
                    <tr style='font-weight:bold; background:#f0f0f0;'>
                        <td><strong>TOTAL</strong></td>
                        <td class='number'><strong>₱" . number_format($expenseTotal, 2) . "</strong></td>
                        <td class='number'><strong>100%</strong></td>
                    </tr>
                </tbody>
            </table>

            <div class='footer'>
                This is a computer-generated document. No signature required.<br>
                For inquiries, contact the finance department.
            </div>
        </body>
        </html>";
    }
}
