<?php

namespace App\Services\Reports\Pdf;

use Illuminate\Support\Collection;

class FarmerRegistryPdfService extends BasePdfReport
{
    public function build(Collection $farmers, array $filterLabels, string $generatedAt): string
    {
        $this->bootPdf('Farmer Registry Report');
        $this->pageTitle('Farmer Registry Report', 'Official farmer member information registry');
        $this->sectionTitle('Report Details');
        $this->metadataRow('Generated At:', $generatedAt);
        $this->metadataRow('Status Filter:', (string) ($filterLabels['status'] ?? 'All statuses'));
        $this->metadataRow('Barangay Filter:', (string) ($filterLabels['barangay'] ?? 'All barangays'));
        $this->metadataRow('Member Type Filter:', (string) ($filterLabels['member_type'] ?? 'All member types'));
        $this->metadataRow('Renewal Filter:', (string) ($filterLabels['renewal'] ?? 'All renewal statuses'));
        $this->metadataRow('Search Term:', (string) ($filterLabels['search'] ?? 'All farmers'));
        $this->metadataRow('Total Records:', (string) $farmers->count());
        $this->sectionTitle('Registered Farmers');

        $headers = [
            ['label' => 'Farmer Name', 'width' => 58],
            ['label' => 'Birth Date', 'width' => 24, 'align' => 'C'],
            ['label' => 'Mobile Number', 'width' => 34],
            ['label' => 'Barangay', 'width' => 38],
            ['label' => 'Association', 'width' => 48],
            ['label' => 'Residential Address', 'width' => 76],
        ];

        $this->tableHeader($headers);

        if ($farmers->isEmpty()) {
            $this->tableRow([
                ['width' => 278, 'value' => 'No farmer records match the current filters.', 'align' => 'C'],
            ]);

            return $this->output();
        }

        foreach ($farmers as $farmer) {
            $this->tableRow([
                ['width' => 58, 'value' => (string) ($farmer->full_name ?? 'Unknown Farmer')],
                ['width' => 24, 'value' => $farmer->birth_date?->format('m/d/Y') ?? 'N/A', 'align' => 'C'],
                ['width' => 34, 'value' => (string) ($farmer->mobile_number ?: 'N/A')],
                ['width' => 38, 'value' => (string) ($farmer->barangay?->name ?? 'N/A')],
                ['width' => 48, 'value' => (string) ($farmer->association?->name ?? 'No association')],
                ['width' => 76, 'value' => (string) ($farmer->address ?: 'N/A')],
            ]);
        }

        return $this->output();
    }
}
