<?php

namespace App\Services\Reports\Pdf;

use Illuminate\Support\Collection;

class RenewalSummaryPdfService extends BasePdfReport
{
    public function buildSummary(Collection $summaryRows, array $totals, array $selectedColumns, string $title, string $generatedAt, int $year): string
    {
        $this->bootPdf('Renewal Summary Report');
        $this->pageTitle('Renewal Summary Report', $title);
        $this->sectionTitle('Report Details');
        $this->metadataRow('Generated At:', $generatedAt);
        $this->metadataRow('Report Year:', (string) $year);
        $this->metadataRow('Barangays Covered:', (string) $summaryRows->count());
        $this->metadataRow('Total Farmers:', number_format((int) $totals['farmers']));
        $this->sectionTitle('Renewal Summary Table');

        $summaryColumnDefinitions = [
            'barangay' => ['label' => 'Barangay', 'width' => 54, 'align' => 'L'],
            'farmer_count' => ['label' => 'Farmers', 'width' => 22, 'align' => 'C'],
            'annual_due' => ['label' => 'Annual Due', 'width' => 30, 'align' => 'R'],
            'mortuary_fee' => ['label' => 'Mortuary Fee', 'width' => 30, 'align' => 'R'],
            'membership_fee' => ['label' => 'Membership Fee', 'width' => 34, 'align' => 'R'],
            'total_amount' => ['label' => 'Total Amount', 'width' => 32, 'align' => 'R'],
            'membership_count' => ['label' => 'Membership', 'width' => 24, 'align' => 'C'],
            'without_mortuary_count' => ['label' => 'No Mortuary', 'width' => 24, 'align' => 'C'],
            'female_count' => ['label' => 'Female', 'width' => 18, 'align' => 'C'],
            'male_count' => ['label' => 'Male', 'width' => 18, 'align' => 'C'],
        ];
        $selectedSummaryColumns = collect($selectedColumns)
            ->filter(fn (string $column): bool => array_key_exists($column, $summaryColumnDefinitions))
            ->values()
            ->all();

        $this->tableHeader(array_map(
            fn (string $column): array => $summaryColumnDefinitions[$column],
            $selectedSummaryColumns
        ));

        foreach ($summaryRows as $row) {
            $this->tableRow(array_map(
                fn (string $column): array => [
                    'width' => $summaryColumnDefinitions[$column]['width'],
                    'value' => $column === 'barangay'
                        ? (string) ($row[$column] ?? '')
                        : (
                            in_array($column, ['farmer_count', 'membership_count', 'without_mortuary_count', 'female_count', 'male_count'], true)
                                ? (string) ($row[$column] ?? 0)
                                : number_format((float) ($row[$column] ?? 0), 2)
                        ),
                    'align' => $summaryColumnDefinitions[$column]['align'],
                ],
                $selectedSummaryColumns
            ));
        }

        $this->pdf->SetFont('Arial', 'B', 8);
        $this->tableRow(array_map(
            fn (string $column): array => [
                'width' => $summaryColumnDefinitions[$column]['width'],
                'value' => match ($column) {
                    'barangay' => 'TOTAL',
                    'farmer_count' => number_format((int) ($totals['farmers'] ?? 0)),
                    'annual_due' => number_format((float) ($totals['annual_due'] ?? 0), 2),
                    'mortuary_fee' => number_format((float) ($totals['mortuary_fee'] ?? 0), 2),
                    'membership_fee' => number_format((float) ($totals['membership_fee'] ?? 0), 2),
                    'total_amount' => number_format((float) ($totals['total_amount'] ?? 0), 2),
                    'membership_count' => (string) ($totals['membership_count'] ?? 0),
                    'without_mortuary_count' => (string) ($totals['without_mortuary_count'] ?? 0),
                    'female_count' => (string) ($totals['female_count'] ?? 0),
                    'male_count' => (string) ($totals['male_count'] ?? 0),
                    default => '',
                },
                'align' => $summaryColumnDefinitions[$column]['align'],
            ],
            $selectedSummaryColumns
        ));

        return $this->output();
    }

    public function buildMasterlist(Collection $rows, array $totals, array $selectedColumns, string $barangay, string $association, string $generatedAt, int $year): string
    {
        $this->bootPdf('Barangay Masterlist Report');
        $this->pageTitle('Barangay Masterlist Report', 'Renewal and membership record for calendar year ' . $year);
        $this->sectionTitle('Report Details');
        $this->metadataRow('Generated At:', $generatedAt);
        $this->metadataRow('Barangay:', $barangay);
        $this->metadataRow('Association:', $association);
        $this->metadataRow('Report Year:', (string) $year);
        $this->metadataRow('Total Farmers:', (string) $totals['total_member_count']);
        $this->sectionTitle('Member Records');

        $masterlistColumnDefinitions = [
            'name' => ['label' => 'Farmer Name', 'width' => 88, 'align' => 'L'],
            'annual_due' => ['label' => 'Annual Due', 'width' => 28, 'align' => 'R'],
            'mortuary_fee' => ['label' => 'Mortuary Fee', 'width' => 28, 'align' => 'R'],
            'membership_fee' => ['label' => 'Membership Fee', 'width' => 30, 'align' => 'R'],
            'total_amount' => ['label' => 'Total Amount', 'width' => 30, 'align' => 'R'],
            'remarks' => ['label' => 'Remarks', 'width' => 34, 'align' => 'L'],
        ];
        $selectedMasterlistColumns = collect($selectedColumns)
            ->filter(fn (string $column): bool => array_key_exists($column, $masterlistColumnDefinitions))
            ->values()
            ->all();

        $this->tableHeader(array_map(
            fn (string $column): array => $masterlistColumnDefinitions[$column],
            $selectedMasterlistColumns
        ));

        foreach ($rows as $row) {
            $this->tableRow(array_map(
                fn (string $column): array => [
                    'width' => $masterlistColumnDefinitions[$column]['width'],
                    'value' => in_array($column, ['name', 'remarks'], true)
                        ? (string) ($row[$column] ?? '')
                        : number_format((float) ($row[$column] ?? 0), 2),
                    'align' => $masterlistColumnDefinitions[$column]['align'],
                ],
                $selectedMasterlistColumns
            ));
        }

        $this->pdf->SetFont('Arial', 'B', 8);
        $this->tableRow(array_map(
            fn (string $column): array => [
                'width' => $masterlistColumnDefinitions[$column]['width'],
                'value' => match ($column) {
                    'name' => 'TOTAL',
                    'annual_due' => number_format((float) ($totals['annual_due'] ?? 0), 2),
                    'mortuary_fee' => number_format((float) ($totals['mortuary_fee'] ?? 0), 2),
                    'membership_fee' => number_format((float) ($totals['membership_fee'] ?? 0), 2),
                    'total_amount' => number_format((float) ($totals['total_amount'] ?? 0), 2),
                    'remarks' => (string) ($totals['total_member_count'] ?? 0) . ' farmers',
                    default => '',
                },
                'align' => $masterlistColumnDefinitions[$column]['align'],
            ],
            $selectedMasterlistColumns
        ));

        return $this->output();
    }
}
