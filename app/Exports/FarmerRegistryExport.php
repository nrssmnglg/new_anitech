<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FarmerRegistryExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private readonly Collection $farmers,
        private readonly array $columns
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
