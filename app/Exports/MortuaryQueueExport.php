<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MortuaryQueueExport implements FromArray, WithHeadings, WithColumnWidths, WithEvents, WithStyles, WithTitle
{
    public function __construct(
        private readonly Collection $rows,
        private readonly array $columns,
    ) {
    }

    public static function availableColumns(): array
    {
        return [
            'farmer_code' => 'Farmer Code',
            'full_name' => 'Full Name',
            'barangay' => 'Barangay',
            'association' => 'Association',
            'member_type' => 'Member Type',
            'ledger_year' => 'Ledger Year',
            'contribution_years' => 'Contribution Years',
            'claim_amount' => 'Expected Claim',
            'status' => 'Status',
        ];
    }

    public function headings(): array
    {
        return array_values(array_intersect_key(self::availableColumns(), array_flip($this->columns)));
    }

    public function array(): array
    {
        return $this->rows
            ->map(fn (array $row): array => $this->mapRow($row))
            ->values()
            ->all();
    }

    public function title(): string
    {
        return 'Claim Queue';
    }

    public function columnWidths(): array
    {
        $widths = ['farmer_code'=>18,'full_name'=>28,'barangay'=>20,'association'=>34,'member_type'=>20,'ledger_year'=>13,'contribution_years'=>18,'claim_amount'=>18,'status'=>16];

        return collect($this->columns)->values()->mapWithKeys(
            fn (string $column, int $index): array => [\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1) => $widths[$column] ?? 18]
        )->all();
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00523F']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event): void {
            $sheet = $event->sheet->getDelegate();
            $lastColumn = $sheet->getHighestColumn();
            $lastRow = max(1, $sheet->getHighestRow());
            $sheet->freezePane('A2');
            $sheet->setAutoFilter("A1:{$lastColumn}1");
            $sheet->getRowDimension(1)->setRowHeight(24);
            $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D6E0DB');
            $sheet->getStyle("A2:{$lastColumn}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
            $amountIndex = array_search('claim_amount', $this->columns, true);
            if ($amountIndex !== false && $lastRow > 1) {
                $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($amountIndex + 1);
                $sheet->getStyle("{$letter}2:{$letter}{$lastRow}")->getNumberFormat()->setFormatCode('₱#,##0.00');
            }
            $sheet->getPageSetup()->setOrientation('landscape')->setFitToWidth(1)->setFitToHeight(0)->setRowsToRepeatAtTopByStartAndEnd(1, 1);
            $sheet->getPageMargins()->setTop(.35)->setRight(.3)->setBottom(.35)->setLeft(.3);
        }];
    }

    private function mapRow(array $row): array
    {
        return collect($this->columns)->map(fn (string $column) => $row[$column] ?? '')->all();
    }
}
