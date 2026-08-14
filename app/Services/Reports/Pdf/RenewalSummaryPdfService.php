<?php

namespace App\Services\Reports\Pdf;

use Illuminate\Support\Collection;

class RenewalSummaryPdfService extends BasePdfReport
{
    public function buildSummary(Collection $summaryRows, array $totals, array $selectedColumns, string $title, string $generatedAt, int $year): string
    {
        $this->bootPdf('Renewal Summary Report', 'P');
        $this->pdf->SetAutoPageBreak(true, 14);
        $this->pdf->SetMargins(10, 10, 10);
        $this->pdf->SetLineWidth(0.25);
        $this->pdf->SetXY(10, 10);

        $columnDefinitions = [
            'barangay' => ['label' => 'BARANGAY', 'weight' => 45, 'align' => 'L'],
            'farmer_count' => ['label' => "NO. OF\nFARMERS", 'weight' => 19, 'align' => 'C'],
            'annual_due' => ['label' => "ANNUAL\nDUES", 'weight' => 21, 'align' => 'R'],
            'mortuary_fee' => ['label' => 'MORTUARY', 'weight' => 23, 'align' => 'R'],
            'membership_fee' => ['label' => "MEMBERSHIP\n(NEW)", 'weight' => 25, 'align' => 'R'],
            'total_amount' => ['label' => "TOTAL\nAMOUNT", 'weight' => 23, 'align' => 'R'],
            'membership_count' => ['label' => 'NM', 'weight' => 17, 'align' => 'C'],
            'without_mortuary_count' => ['label' => 'W/O M', 'weight' => 17, 'align' => 'C'],
            'female_count' => ['label' => 'FEMALE', 'weight' => 17, 'align' => 'C'],
            'male_count' => ['label' => 'MALE', 'weight' => 17, 'align' => 'C'],
        ];
        $visibleColumns = collect(array_keys($columnDefinitions))
            ->filter(fn (string $column): bool => in_array($column, $selectedColumns, true))
            ->values()
            ->all();

        if ($visibleColumns === []) {
            $visibleColumns = array_keys($columnDefinitions);
        }

        $columnWidths = $this->summaryColumnWidths($visibleColumns, $columnDefinitions);
        $fontSize = count($visibleColumns) > 8 ? 5.5 : 6.5;

        $this->drawSummaryTitleAndHeader($visibleColumns, $columnDefinitions, $columnWidths, $year, $fontSize);
        $rowHeight = 5;
        $this->pdf->SetFont('Arial', '', $fontSize);

        foreach ($summaryRows as $row) {
            if ($this->pdf->GetY() + $rowHeight > $this->pdf->GetPageHeight() - 14) {
                $this->pdf->AddPage('P');
                $this->pdf->SetXY(10, 10);
                $this->drawSummaryTitleAndHeader($visibleColumns, $columnDefinitions, $columnWidths, $year, $fontSize);
                $this->pdf->SetFont('Arial', '', $fontSize);
            }

            foreach ($visibleColumns as $index => $column) {
                $this->pdf->Cell(
                    $columnWidths[$column],
                    $rowHeight,
                    $this->text($this->summaryCellValue($column, $row)),
                    1,
                    $index === array_key_last($visibleColumns) ? 1 : 0,
                    $columnDefinitions[$column]['align']
                );
            }
        }

        if ($this->pdf->GetY() + $rowHeight > $this->pdf->GetPageHeight() - 14) {
            $this->pdf->AddPage('P');
            $this->pdf->SetXY(10, 10);
            $this->drawSummaryTitleAndHeader($visibleColumns, $columnDefinitions, $columnWidths, $year, $fontSize);
        }

        $this->pdf->SetFont('Arial', 'B', $fontSize);

        foreach ($visibleColumns as $index => $column) {
            $this->pdf->Cell(
                $columnWidths[$column],
                $rowHeight,
                $this->text($this->summaryTotalValue($column, $totals)),
                1,
                $index === array_key_last($visibleColumns) ? 1 : 0,
                $column === 'barangay' ? 'C' : $columnDefinitions[$column]['align']
            );
        }

        return $this->output();
    }

    private function summaryColumnWidths(array $visibleColumns, array $columnDefinitions): array
    {
        $totalWeight = collect($visibleColumns)->sum(
            fn (string $column): int => $columnDefinitions[$column]['weight']
        );
        $remainingWidth = 190.0;
        $columnWidths = [];

        foreach ($visibleColumns as $index => $column) {
            $columnWidths[$column] = $index === array_key_last($visibleColumns)
                ? $remainingWidth
                : round(190 * ($columnDefinitions[$column]['weight'] / $totalWeight), 2);
            $remainingWidth -= $columnWidths[$column];
        }

        return $columnWidths;
    }

    private function summaryCellValue(string $column, array $row): string
    {
        if ($column === 'barangay') {
            return strtoupper((string) ($row[$column] ?? ''));
        }

        if (in_array($column, ['annual_due', 'mortuary_fee', 'membership_fee', 'total_amount'], true)) {
            return number_format((float) ($row[$column] ?? 0), 0);
        }

        return number_format((int) ($row[$column] ?? 0));
    }

    private function summaryTotalValue(string $column, array $totals): string
    {
        $totalKey = $column === 'farmer_count' ? 'farmers' : $column;

        if ($column === 'barangay') {
            return 'TOTAL';
        }

        if (in_array($column, ['annual_due', 'mortuary_fee', 'membership_fee', 'total_amount'], true)) {
            return number_format((float) ($totals[$totalKey] ?? 0), 0);
        }

        return number_format((int) ($totals[$totalKey] ?? 0));
    }

    private function drawSummaryTitleAndHeader(
        array $visibleColumns,
        array $columnDefinitions,
        array $columnWidths,
        int $year,
        float $fontSize
    ): void
    {
        $this->pdf->SetFont('Arial', 'B', 11);
        $this->pdf->Cell(0, 6, $this->text('SUMMARY OF BASACAFEFA RENEWAL CY ' . $year), 0, 1, 'C');
        $this->pdf->Ln(2);

        $left = $this->pdf->GetX();
        $top = $this->pdf->GetY();
        $headerHeight = 12;
        $this->pdf->SetFont('Arial', 'B', $fontSize);
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->SetDrawColor(0, 0, 0);
        $currentX = $left;
        $hasRemarksGroup = in_array('membership_count', $visibleColumns, true)
            && in_array('without_mortuary_count', $visibleColumns, true);

        foreach ($visibleColumns as $column) {
            if ($hasRemarksGroup && $column === 'without_mortuary_count') {
                continue;
            }

            if ($hasRemarksGroup && $column === 'membership_count') {
                $remarksWidth = $columnWidths['membership_count'] + $columnWidths['without_mortuary_count'];
                $this->pdf->Rect($currentX, $top, $remarksWidth, 6, 'DF');
                $this->pdf->SetXY($currentX, $top + 1);
                $this->pdf->Cell($remarksWidth, 4, $this->text('REMARKS'), 0, 0, 'C');

                $this->pdf->Rect($currentX, $top + 6, $columnWidths['membership_count'], 6, 'DF');
                $this->pdf->SetXY($currentX, $top + 7);
                $this->pdf->Cell($columnWidths['membership_count'], 4, $this->text('NM'), 0, 0, 'C');

                $currentX += $columnWidths['membership_count'];
                $this->pdf->Rect($currentX, $top + 6, $columnWidths['without_mortuary_count'], 6, 'DF');
                $this->pdf->SetXY($currentX, $top + 7);
                $this->pdf->Cell($columnWidths['without_mortuary_count'], 4, $this->text('W/O M'), 0, 0, 'C');
                $currentX += $columnWidths['without_mortuary_count'];

                continue;
            }

            $width = $columnWidths[$column];
            $label = $columnDefinitions[$column]['label'];
            $lineCount = substr_count($label, "\n") + 1;
            $lineHeight = 4;
            $offset = ($headerHeight - ($lineCount * $lineHeight)) / 2;
            $this->pdf->Rect($currentX, $top, $width, $headerHeight, 'DF');
            $this->pdf->SetXY($currentX, $top + $offset);
            $this->pdf->MultiCell($width, $lineHeight, $this->text($label), 0, 'C');
            $currentX += $width;
        }

        $this->pdf->SetXY($left, $top + $headerHeight);
    }

    public function buildMasterlist(
        Collection $rows,
        array $totals,
        string $barangay,
        string $association,
        string $president,
        string $generatedAt,
        int $year,
        array $selectedColumns,
    ): string
    {
        $this->bootPdf('Barangay Masterlist Report', 'P');
        $this->pdf->SetAutoPageBreak(true, 16);
        $this->pdf->SetMargins(10, 10, 10);
        $this->pdf->SetLineWidth(0.25);

        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(0, 6, $this->text('BASACAFEFA MASTERLIST OF MEMBERS RENEWAL AND NEW MEMBERSHIP'), 0, 1, 'C');
        $this->pdf->Ln(1);

        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(52, 6, $this->text('Barangay:'), 0, 0, 'L');
        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->Cell(0, 6, $this->text($barangay), 0, 1, 'L');

        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(52, 6, $this->text('Name of Farmer Association:'), 0, 0, 'L');
        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->Cell(0, 6, $this->text($association), 0, 1, 'L');

        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(52, 6, $this->text('Name of President:'), 0, 0, 'L');
        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->Cell(0, 6, $this->text($president), 0, 1, 'L');

        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(52, 6, $this->text('For the Year:'), 0, 0, 'L');
        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->Cell(0, 6, $this->text((string) $year), 0, 1, 'L');

        $this->pdf->Ln(2);
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->SetDrawColor(0, 0, 0);

        $columnDefinitions = [
            'name' => ['label' => 'NAME', 'weight' => 74, 'align' => 'L'],
            'annual_due' => ['label' => 'ANNUAL DUES', 'weight' => 23, 'align' => 'R'],
            'mortuary_fee' => ['label' => 'MORTUARY', 'weight' => 23, 'align' => 'R'],
            'membership_fee' => ['label' => 'MEMBERSHIP', 'weight' => 23, 'align' => 'R'],
            'total_amount' => ['label' => 'TOTAL', 'weight' => 20, 'align' => 'R'],
            'remarks' => ['label' => 'REMARKS', 'weight' => 19, 'align' => 'C'],
        ];
        $visibleColumns = $this->visibleMasterlistColumns($selectedColumns, $columnDefinitions);
        $columnWidths = $this->masterlistColumnWidths($visibleColumns, $columnDefinitions);

        $this->pdf->Cell(8, 8, $this->text('NO.'), 1, 0, 'C', true);

        foreach ($visibleColumns as $column) {
            $this->pdf->Cell(
                $columnWidths[$column],
                8,
                $this->text($columnDefinitions[$column]['label']),
                1,
                0,
                $columnDefinitions[$column]['align'],
                true
            );
        }

        $this->pdf->Ln();
        $this->pdf->SetFont('Arial', '', 8);
        $rowNumber = 1;

        foreach ($rows as $row) {
            $this->pdf->Cell(8, 7, $this->text((string) $rowNumber), 1, 0, 'C', false);

            foreach ($visibleColumns as $column) {
                $this->pdf->Cell(
                    $columnWidths[$column],
                    7,
                    $this->text($this->masterlistCellValue($column, $row)),
                    1,
                    0,
                    $columnDefinitions[$column]['align'],
                    false
                );
            }

            $this->pdf->Ln();
            $rowNumber++;
        }

        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(8, 7, '', 1, 0, 'C', false);

        foreach ($visibleColumns as $column) {
            $this->pdf->Cell(
                $columnWidths[$column],
                7,
                $this->text($this->masterlistTotalValue($column, $totals)),
                1,
                0,
                $column === 'name' ? 'L' : $columnDefinitions[$column]['align'],
                false
            );
        }

        $this->pdf->Ln();

        if (in_array('remarks', $visibleColumns, true)) {
            $memberTypeCounts = $totals['member_type_counts'] ?? [];
            $memberTypeBreakdown = collect(['OSC', 'NM', 'OM', 'NSC'])
                ->map(fn (string $code): string => $code . ': ' . number_format((int) ($memberTypeCounts[$code] ?? 0)))
                ->implode('   |   ');
            $this->pdf->SetFont('Arial', 'B', 7);
            $this->pdf->Cell(190, 7, $this->text('MEMBER TYPE TOTALS   ' . $memberTypeBreakdown), 1, 1, 'C', false);
        }

        return $this->output();
    }

    private function visibleMasterlistColumns(array $selectedColumns, array $columnDefinitions): array
    {
        $visibleColumns = collect(array_keys($columnDefinitions))
            ->filter(fn (string $column): bool => in_array($column, $selectedColumns, true))
            ->values()
            ->all();

        return $visibleColumns !== [] ? $visibleColumns : array_keys($columnDefinitions);
    }

    private function masterlistColumnWidths(array $visibleColumns, array $columnDefinitions): array
    {
        $totalWeight = collect($visibleColumns)->sum(
            fn (string $column): int => $columnDefinitions[$column]['weight']
        );
        $remainingWidth = 182.0;
        $columnWidths = [];

        foreach ($visibleColumns as $index => $column) {
            $columnWidths[$column] = $index === array_key_last($visibleColumns)
                ? $remainingWidth
                : round(182 * ($columnDefinitions[$column]['weight'] / $totalWeight), 2);
            $remainingWidth -= $columnWidths[$column];
        }

        return $columnWidths;
    }

    private function masterlistCellValue(string $column, array $row): string
    {
        if (in_array($column, ['annual_due', 'mortuary_fee', 'membership_fee', 'total_amount'], true)) {
            return number_format((float) ($row[$column] ?? 0), 2);
        }

        return (string) ($row[$column] ?? '');
    }

    private function masterlistTotalValue(string $column, array $totals): string
    {
        if ($column === 'name') {
            return 'TOTAL';
        }

        if ($column === 'remarks') {
            return (string) ($totals['total_member_count'] ?? 0) . ' members';
        }

        return number_format((float) ($totals[$column] ?? 0), 2);
    }
}
