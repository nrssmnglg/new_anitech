<?php

namespace Tests\Unit\Services;

use App\Services\Reports\Pdf\RenewalSummaryPdfService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class RenewalSummaryPdfServiceTest extends TestCase
{
    public function test_masterlist_pdf_uses_only_selected_columns(): void
    {
        $service = new RenewalSummaryPdfService();
        $method = new ReflectionMethod(RenewalSummaryPdfService::class, 'visibleMasterlistColumns');
        $definitions = [
            'name' => ['weight' => 74],
            'annual_due' => ['weight' => 23],
            'mortuary_fee' => ['weight' => 23],
            'membership_fee' => ['weight' => 23],
            'total_amount' => ['weight' => 20],
            'remarks' => ['weight' => 19],
        ];

        $this->assertSame(
            ['name'],
            $method->invoke($service, ['name'], $definitions),
        );
    }

    public function test_masterlist_pdf_generates_with_name_as_the_only_selected_column(): void
    {
        $pdf = (new RenewalSummaryPdfService())->buildMasterlist(
            collect([[
                'name' => 'Dela Cruz, Juan',
                'annual_due' => 100,
                'mortuary_fee' => 150,
                'membership_fee' => 0,
                'total_amount' => 250,
                'remarks' => 'OM',
            ]]),
            [
                'annual_due' => 100,
                'mortuary_fee' => 150,
                'membership_fee' => 0,
                'total_amount' => 250,
                'total_member_count' => 1,
                'member_type_counts' => ['OSC' => 0, 'NM' => 0, 'OM' => 1, 'NSC' => 0],
            ],
            'Abanon',
            'Abanon Agrifarmers Association',
            'Juan Dela Cruz',
            'July 30, 2026 10:30 AM',
            2026,
            ['name'],
        );

        $this->assertStringStartsWith('%PDF-', $pdf);
    }

    public function test_summary_pdf_generates_with_date_and_time(): void
    {
        $pdf = (new RenewalSummaryPdfService())->buildSummary(
            collect([[
                'barangay' => 'Abanon',
                'farmer_count' => 10,
                'annual_due' => 1000,
                'mortuary_fee' => 1500,
                'membership_fee' => 0,
                'total_amount' => 2500,
                'membership_count' => 0,
                'without_mortuary_count' => 0,
                'female_count' => 5,
                'male_count' => 5,
            ]]),
            [
                'farmers' => 10,
                'annual_due' => 1000,
                'mortuary_fee' => 1500,
                'membership_fee' => 0,
                'total_amount' => 2500,
                'membership_count' => 0,
                'without_mortuary_count' => 0,
                'female_count' => 5,
                'male_count' => 5,
            ],
            ['barangay', 'farmer_count', 'total_amount'],
            'Renewal Summary CY 2026',
            'October 21, 2026 07:45 PM',
            2026
        );

        $this->assertStringStartsWith('%PDF-', $pdf);
    }
}
