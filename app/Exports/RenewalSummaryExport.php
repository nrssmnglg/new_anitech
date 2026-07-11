<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RenewalSummaryExport implements FromArray, ShouldAutoSize, WithHeadings
{
    public function __construct(
        private readonly Collection $rows,
        private readonly array $totals,
        private readonly array $columns,
    ) {
    }

    public static function availableColumns(): array
    {
        return [
            'barangay' => 'Barangay',
            'farmer_count' => 'No. of Farmers',
            'annual_due' => 'Annual Dues',
            'mortuary_fee' => 'Mortuary',
            'membership_fee' => 'Membership (New)',
            'total_amount' => 'Total Amount',
            'membership_count' => 'NM',
            'without_mortuary_count' => 'W/O M',
            'female_count' => 'Female',
            'male_count' => 'Male',
        ];
    }

    public function headings(): array
    {
        return array_values(array_intersect_key(self::availableColumns(), array_flip($this->columns)));
    }

    public function array(): array
    {
        $rows = $this->rows->map(fn (array $row): array => $this->mapRow($row))->values()->all();

        if ($rows !== []) {
            $rows[] = $this->mapRow([
                'barangay' => 'TOTAL',
                'farmer_count' => $this->totals['farmers'],
                'annual_due' => $this->totals['annual_due'],
                'mortuary_fee' => $this->totals['mortuary_fee'],
                'membership_fee' => $this->totals['membership_fee'],
                'total_amount' => $this->totals['total_amount'],
                'membership_count' => $this->totals['membership_count'],
                'without_mortuary_count' => $this->totals['without_mortuary_count'],
                'female_count' => $this->totals['female_count'],
                'male_count' => $this->totals['male_count'],
            ]);
        }

        return $rows;
    }

    private function mapRow(array $row): array
    {
        return collect($this->columns)->map(fn (string $column) => $row[$column] ?? '')->all();
    }
}
