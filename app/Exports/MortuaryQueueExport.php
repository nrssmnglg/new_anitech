<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MortuaryQueueExport implements FromArray, ShouldAutoSize, WithHeadings
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

    private function mapRow(array $row): array
    {
        return collect($this->columns)->map(fn (string $column) => $row[$column] ?? '')->all();
    }
}
