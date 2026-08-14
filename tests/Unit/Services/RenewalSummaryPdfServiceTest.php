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
            'July 30, 2026',
            2026,
            ['name'],
        );

        $this->assertStringStartsWith('%PDF-', $pdf);
    }
}
