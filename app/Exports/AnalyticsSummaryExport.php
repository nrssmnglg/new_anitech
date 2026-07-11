<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AnalyticsSummaryExport implements FromArray, ShouldAutoSize, WithHeadings
{
    public function __construct(
        private readonly array $rows,
    ) {
    }

    public function headings(): array
    {
        return ['Section', 'Metric', 'Value'];
    }

    public function array(): array
    {
        return array_map(static fn (array $row): array => [
            $row['section'] ?? '',
            $row['metric'] ?? '',
            $row['value'] ?? '',
        ], $this->rows);
    }
}
