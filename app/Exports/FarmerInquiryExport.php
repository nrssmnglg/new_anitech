<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FarmerInquiryExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private readonly Collection $queries,
        private readonly array $columns
    ) {
    }

    public static function availableColumns(): array
    {
        return [
            'farmer_code' => 'Farmer Code',
            'full_name' => 'Farmer Name',
            'barangay' => 'Barangay',
            'subject' => 'Subject',
            'message' => 'Inquiry Message',
            'status' => 'Status',
            'responses_count' => 'Responses',
            'submitted_at' => 'Submitted At',
        ];
    }

    public function collection(): Collection
    {
        return $this->queries;
    }

    public function headings(): array
    {
        return array_values(array_intersect_key(self::availableColumns(), array_flip($this->columns)));
    }

    public function map($query): array
    {
        return collect($this->columns)->map(fn (string $column): string => match ($column) {
            'farmer_code' => (string) ($query->farmer?->farmer_code ?? ''),
            'full_name' => (string) ($query->farmer?->full_name ?? 'Unknown Farmer'),
            'barangay' => (string) ($query->farmer?->barangay?->name ?? ''),
            'subject' => (string) ($query->subject ?? ''),
            'message' => (string) ($query->message ?? ''),
            'status' => (string) ($query->status ?? ''),
            'responses_count' => (string) ($query->responses_count ?? 0),
            'submitted_at' => (string) (optional($query->created_at)->format('Y-m-d H:i:s') ?? ''),
            default => '',
        })->all();
    }
}
