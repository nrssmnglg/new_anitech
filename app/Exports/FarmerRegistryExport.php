<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FarmerRegistryExport implements FromCollection, ShouldAutoSize, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        private readonly Collection $farmers,
        private readonly array $columns,
        private readonly array $filterLabels = [],
        private readonly string $generatedAt = '',
        private readonly string $generatedBy = 'Authorized User',
    ) {
    }

    public static function availableColumns(): array
    {
        return [
            'farmer_code' => 'Farmer Code',
            'full_name' => 'Full Name',
            'status' => 'Status',
            'member_type' => 'Member Type',
            'barangay' => 'Barangay',
            'gender' => 'Gender',
            'birth_date' => 'Birth Date',
            'mobile_number' => 'Mobile Number',
            'address' => 'Address',
            'registered_at' => 'Registered At',
        ];
    }

    public function collection(): Collection
    {
        return $this->farmers;
    }

    public function headings(): array
    {
        return array_values(array_intersect_key(self::availableColumns(), array_flip($this->columns)));
    }

    public function startCell(): string
    {
        return 'A6';
    }

    public function title(): string
    {
        return 'Farmer Registry';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']]],
            2 => ['font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => 'D9EEE7']]],
            3 => ['font' => ['size' => 9, 'color' => ['rgb' => '40534B']]],
            4 => ['font' => ['size' => 9, 'color' => ['rgb' => '40534B']]],
            6 => [
                'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '17624F']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $columnCount = max(count($this->columns), 1);
                $lastColumn = Coordinate::stringFromColumnIndex($columnCount);
                $lastRow = 6 + $this->farmers->count();
                $filterSummary = collect([
                    'Search: ' . ($this->filterLabels['search'] ?? 'All farmers'),
                    'Status: ' . ($this->filterLabels['status'] ?? 'All statuses'),
                    'Barangay: ' . ($this->filterLabels['barangay'] ?? 'All barangays'),
                    'Member Type: ' . ($this->filterLabels['member_type'] ?? 'All member types'),
                ])->implode('  |  ');

                if ($columnCount > 1) {
                    $sheet->mergeCells("A1:{$lastColumn}1");
                    $sheet->mergeCells("A2:{$lastColumn}2");
                    $sheet->mergeCells("A3:{$lastColumn}3");
                    $sheet->mergeCells("A4:{$lastColumn}4");
                }

                $sheet->setCellValue('A1', 'ANITECH FARMER REGISTRY');
                $sheet->setCellValue('A2', 'Official Farmer Registry Report');
                $sheet->setCellValue('A3', "Generated: {$this->generatedAt}  |  Prepared by: {$this->generatedBy}  |  Total records: {$this->farmers->count()}");
                $sheet->setCellValue('A4', $filterSummary);
                $sheet->getStyle("A1:{$lastColumn}2")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('00513F');
                $sheet->getRowDimension(1)->setRowHeight(25);
                $sheet->getRowDimension(2)->setRowHeight(18);
                $sheet->getRowDimension(5)->setRowHeight(7);
                $sheet->getRowDimension(6)->setRowHeight(22);
                $sheet->freezePane('A7');
                $sheet->setAutoFilter("A6:{$lastColumn}{$lastRow}");
                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0)
                    ->setRowsToRepeatAtTopByStartAndEnd(1, 6);
                $sheet->getPageMargins()->setTop(0.4)->setRight(0.3)->setBottom(0.5)->setLeft(0.3);
                $sheet->getHeaderFooter()->setOddFooter('&LAnitech Farmer Registry&CConfidential&RPage &P of &N');

                if ($lastRow >= 7) {
                    $dataRange = "A7:{$lastColumn}{$lastRow}";
                    $sheet->getStyle($dataRange)->applyFromArray([
                        'font' => ['size' => 9, 'color' => ['rgb' => '26352F']],
                        'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
                        'borders' => ['bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'DDE5E1']]],
                    ]);

                    for ($row = 8; $row <= $lastRow; $row += 2) {
                        $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F7F5');
                    }
                }
            },
        ];
    }

    public function map($farmer): array
    {
        return collect($this->columns)->map(fn (string $column): string => match ($column) {
            'farmer_code' => (string) $farmer->farmer_code,
            'full_name' => (string) $farmer->full_name,
            'status' => (string) $farmer->status->label(),
            'member_type' => $farmer->memberType?->code ? $farmer->memberType->code . ' - ' . $farmer->memberType->name : '',
            'barangay' => (string) ($farmer->barangay?->name ?? ''),
            'gender' => (string) ($farmer->profile?->sex ? ucfirst(strtolower((string) $farmer->profile->sex)) : ''),
            'birth_date' => (string) ($farmer->profile?->birth_date?->format('Y-m-d') ?? ''),
            'mobile_number' => (string) ($farmer->profile?->mobile_number ?? ''),
            'address' => (string) ($farmer->profile?->address ?? ''),
            'registered_at' => (string) (optional($farmer->registered_at)->format('Y-m-d H:i:s') ?? ''),
            default => '',
        })->all();
    }
}
