<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\RenewalController;
use App\Models\Farmer;
use App\Models\FarmerProfile;
use App\Models\MemberType;
use App\Models\MembershipLedger;
use App\Models\MembershipTransaction;
use App\Models\PaymentAssessment;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class RenewalReportSortingTest extends TestCase
{
    public function test_masterlist_rows_are_sorted_and_displayed_by_last_name_then_first_name(): void
    {
        $rows = $this->buildMasterlistRows(collect([
            $this->ledgerFor('Pedro', 'Cruz', 3),
            $this->ledgerFor('Juan', 'Cruz', 2),
            $this->ledgerFor('Ana', 'Alonzo', 1),
        ]));

        $this->assertSame([
            'Alonzo, Ana',
            'Cruz, Juan',
            'Cruz, Pedro',
        ], $rows->pluck('name')->all());
        $this->assertFalse($rows->contains(fn (array $row): bool => array_key_exists('sort_name', $row)));
    }

    public function test_masterlist_totals_include_each_member_type_count(): void
    {
        $rows = collect([
            $this->masterlistRow('OSC'),
            $this->masterlistRow('OM'),
            $this->masterlistRow('OM'),
            $this->masterlistRow('NM', true),
        ]);

        $controller = (new ReflectionClass(RenewalController::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(RenewalController::class, 'masterlistTotals');
        $totals = $method->invoke($controller, $rows);

        $this->assertSame([
            'OSC' => 1,
            'NM' => 1,
            'OM' => 2,
            'NSC' => 0,
        ], $totals['member_type_counts']);
        $this->assertSame(4, $totals['total_member_count']);
    }

    public function test_renewal_queue_filters_accept_search_year_barangay_and_member_type(): void
    {
        $controller = (new ReflectionClass(RenewalController::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(RenewalController::class, 'queueFilters');
        $request = Request::create('/admin/renewals', 'GET', [
            'queue_search' => 'Dela Cruz',
            'queue_year' => '2025',
            'queue_barangay_id' => '12',
            'queue_member_type_id' => '3',
        ]);

        $this->assertSame([
            'queue_search' => 'Dela Cruz',
            'queue_year' => '2025',
            'queue_barangay_id' => '12',
            'queue_member_type_id' => '3',
        ], $method->invoke($controller, $request));
    }

    private function buildMasterlistRows(Collection $ledgers): Collection
    {
        $controller = (new ReflectionClass(RenewalController::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(RenewalController::class, 'buildMasterlistRows');

        return $method->invoke($controller, $ledgers);
    }

    private function ledgerFor(string $firstName, string $lastName, int $id): MembershipLedger
    {
        $profile = new FarmerProfile([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'sex' => 'male',
        ]);
        $memberType = new MemberType([
            'code' => 'OM',
            'name' => 'Old Member',
        ]);
        $farmer = (new Farmer([
            'farmer_code' => sprintf('FRM-2026-%05d', $id),
        ]))->forceFill(['id' => $id]);
        $farmer->setRelation('profile', $profile);
        $farmer->setRelation('memberType', $memberType);
        $farmer->setRelation('barangay', null);
        $farmer->setRelation('association', null);

        $assessment = (new PaymentAssessment([
            'annual_due' => 100,
            'mortuary_fee' => 150,
            'membership_fee' => 0,
        ]))->forceFill(['id' => $id]);
        $transaction = (new MembershipTransaction([
            'farmer_id' => $id,
            'transaction_type' => 'Renewal',
        ]))->forceFill(['id' => $id]);
        $transaction->setRelation('farmer', $farmer);
        $transaction->setRelation('paymentAssessments', collect([$assessment]));

        $ledger = (new MembershipLedger([
            'year' => 2026,
            'amount_paid' => 250,
        ]))->forceFill(['id' => $id]);
        $ledger->setRelation('membershipTransaction', $transaction);

        return $ledger;
    }

    private function masterlistRow(string $memberTypeCode, bool $isNewMember = false): array
    {
        return [
            'name' => 'Test Member',
            'annual_due' => 100.0,
            'mortuary_fee' => 150.0,
            'membership_fee' => $isNewMember ? 100.0 : 0.0,
            'total_amount' => $isNewMember ? 350.0 : 250.0,
            'remarks' => $memberTypeCode,
            'is_new_member' => $isNewMember,
            'has_mortuary' => true,
        ];
    }
}
