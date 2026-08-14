<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RenewalMasterlistExport implements FromArray, ShouldAutoSize, WithHeadings
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
            'name' => 'Name',
            'annual_due' => 'Annual Dues',
            'mortuary_fee' => 'Mortuary',
            'membership_fee' => 'Membership',
            'total_amount' => 'Total',
            'remarks' => 'Remarks',
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
                'name' => 'TOTAL',
                'annual_due' => $this->totals['annual_due'],
                'mortuary_fee' => $this->totals['mortuary_fee'],
                'membership_fee' => $this->totals['membership_fee'],
                'total_amount' => $this->totals['total_amount'],
                'remarks' => ($this->totals['total_member_count'] ?? 0) . ' members',
            ]);
            $memberTypeCounts = $this->totals['member_type_counts'] ?? [];
            $rows[] = $this->mapRow([
                'name' => 'MEMBER TYPE TOTALS',
                'remarks' => collect(['OSC', 'NM', 'OM', 'NSC'])
                    ->map(fn (string $code): string => $code . ': ' . number_format((int) ($memberTypeCounts[$code] ?? 0)))
                    ->implode(' | '),
            ]);
        }

        return $rows;
    }

    private function mapRow(array $row): array
    {
        return collect($this->columns)->map(fn (string $column) => $row[$column] ?? '')->all();
    }
}
