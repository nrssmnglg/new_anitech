<?php

namespace Tests\Feature\Admin;

use App\Enums\AssessmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\FeeSchedule;
use App\Models\MemberType;
use App\Models\MembershipLedger;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Models\PaymentAssessment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HistoricalRecordEncodingTest extends TestCase
{
    use RefreshDatabase;

    private Barangay $barangay;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::query()->create([
            'name' => 'Encoder', 'email' => 'encoder@example.test', 'password' => 'secret123',
            'role' => User::ROLE_ADMIN, 'status' => 'Active',
        ]));
        $this->barangay = Barangay::query()->create(['name' => 'Historical Village', 'code' => 'HV', 'status' => 'Active']);
        foreach (['OM', 'OSC', 'NM', 'NSC'] as $code) {
            $type = MemberType::query()->create(['code' => $code, 'name' => $code]);
            foreach ([2024 => 110, 2025 => 130] as $year => $annual) {
                FeeSchedule::query()->create([
                    'member_type_id' => $type->id, 'year' => $year,
                    'membership_fee' => 100, 'annual_due' => $annual, 'mortuary_fee' => 160,
                    'renewal_deadline' => "$year-02-14", 'effective_from' => "$year-01-01",
                    'effective_to' => "$year-12-31", 'is_active' => $year === 2025,
                ]);
            }
        }
    }

    public static function memberTypes(): array
    {
        return [
            'old member' => ['OM', 'Renewal', 270.0, 0.0, 160.0],
            'old senior' => ['OSC', 'Renewal', 110.0, 0.0, 0.0],
            'new member' => ['NM', 'Application', 370.0, 100.0, 160.0],
            'new senior' => ['NSC', 'Application', 210.0, 100.0, 0.0],
        ];
    }

    #[DataProvider('memberTypes')]
    public function test_old_record_member_type_controls_transaction_fees_and_history(string $code, string $kind, float $total, float $membership, float $mortuary): void
    {
        $response = $this->post(route('admin.farmers.store'), $this->payload($code));
        $response->assertSessionHasNoErrors()->assertRedirect();
        $farmer = Farmer::query()->firstOrFail();
        $transaction = MembershipTransaction::query()->sole();
        $assessment = PaymentAssessment::query()->sole();
        $payment = Payment::query()->sole();
        $ledger = MembershipLedger::query()->sole();

        $this->assertDatabaseCount('farmers', 1);
        $this->assertDatabaseCount('farmer_profiles', 1);
        $this->assertDatabaseCount('farmer_documents', 0);
        $this->assertSame('Old Record', $farmer->record_origin);
        $this->assertSame($farmer->id, $transaction->farmer_id);
        $this->assertSame($kind, $transaction->transaction_type);
        $this->assertSame(2024, $transaction->year);
        $this->assertSame('legacy', $transaction->source);
        $this->assertSame($transaction->id, $assessment->membership_transaction_id);
        $this->assertSame($assessment->id, $payment->payment_assessment_id);
        $this->assertSame($transaction->id, $ledger->membership_transaction_id);
        $this->assertSame($assessment->fee_schedule_id, $ledger->fee_schedule_id);
        $this->assertEquals(2024, $assessment->feeSchedule->year);
        $this->assertSame($this->typeId($code), $assessment->feeSchedule->member_type_id);
        $this->assertSame(AssessmentStatus::PAID, $assessment->status);
        $this->assertSame(PaymentStatus::PAID, $payment->status);
        $this->assertSame($total, (float) $assessment->total_amount_due);
        $this->assertSame($total, (float) $payment->amount_paid);
        $this->assertSame($total, (float) $ledger->amount_paid);
        $this->assertSame($membership, (float) $assessment->membership_fee);
        $this->assertSame($mortuary, (float) $assessment->mortuary_fee);
        $this->assertSame('2024-02-14', $farmer->registered_at->toDateString());
        $this->assertSame('2024-02-14', $transaction->submitted_at->toDateString());
        $this->assertSame('2024-02-14', $payment->paid_at->toDateString());
        $this->assertSame('paid', $ledger->payment_status);
        $this->assertDatabaseMissing('membership_transactions', ['year' => 2025]);
        $dashboard = app(\App\Http\Controllers\Admin\DashboardController::class);
        $collections = (new \ReflectionMethod($dashboard, 'collectionsSummary'))->invoke($dashboard, 2024, null);
        $this->assertSame($kind === 'Application' ? 1 : 0, $collections['counts']['applicationPayments']);
        $this->assertSame($kind === 'Renewal' ? 1 : 0, $collections['counts']['renewalPayments']);
        $this->assertSame($total, $collections['totals'][$kind === 'Application' ? 'applications' : 'renewals']);


        $this->get(route('admin.farmers.show', $farmer))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('timeline', function ($items) use ($kind): bool {
                $counts = collect($items)->countBy('type');
                return $counts->get('registration', 0) === 1 && $counts->get('payment', 0) === 1
                    && $counts->get('application', 0) === ($kind === 'Application' ? 1 : 0)
                    && $counts->get('renewal', 0) === ($kind === 'Renewal' ? 1 : 0);
            }));
        $url = $kind === 'Renewal' ? route('admin.renewals.show', $transaction->id)
            : route('admin.membership-applications.show', $transaction->application_no);
        $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('flow.isHistorical', true)->where('flow.paymentSettled', true)->has('documents', 0));
        $this->assertDatabaseCount('farmer_documents', 0);
    }

    public static function existingSelectors(): array
    {
        return [['farmer_id'], ['existing_farmer_code']];
    }

    #[DataProvider('existingSelectors')]
    public function test_old_record_existing_farmer_gets_only_the_explicit_new_year(string $selector): void
    {
        $this->post(route('admin.farmers.store'), $this->payload('OM'))->assertSessionHasNoErrors();
        $farmer = Farmer::query()->firstOrFail();
        $beforeFarmer = $farmer->getAttributes();
        $beforeProfile = $farmer->profile->getAttributes();
        $payload = [
            'record_mode' => 'existing', $selector => $selector === 'farmer_id' ? $farmer->id : $farmer->farmer_code,
            'member_type_id' => $this->typeId('OM'), 'historical_year' => 2025,
            'first_name' => 'Must not overwrite', 'status' => 'active',
        ];
        $this->post(route('admin.farmers.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseCount('farmers', 1);
        $this->assertDatabaseCount('farmer_profiles', 1);
        $this->assertSame($beforeFarmer, $farmer->fresh()->getAttributes());
        $this->assertSame($beforeProfile, $farmer->profile->fresh()->getAttributes());
        $this->assertDatabaseCount('membership_transactions', 2);
        $this->assertDatabaseCount('payments', 2);
        $this->assertDatabaseCount('membership_ledgers', 2);
        $this->assertDatabaseMissing('membership_transactions', ['transaction_type' => 'Application']);
        $renewal = MembershipTransaction::query()->where('year', 2025)->sole();
        $assessment = $renewal->paymentAssessments()->sole();
        $this->assertEquals(2025, $assessment->feeSchedule->year);
        $this->assertSame(290.0, (float) $assessment->total_amount_due);
        $this->assertSame('2025-02-14', $renewal->submitted_at->toDateString());
        $this->assertSame('2025-02-14', $assessment->payments()->sole()->paid_at->toDateString());
        $this->assertDatabaseMissing('membership_transactions', ['year' => 2026]);
        $this->post(route('admin.farmers.store'), $payload)->assertSessionHasErrors('historical_year');
        $this->assertDatabaseCount('payments', 2);
        $this->assertDatabaseCount('membership_transactions', 2);
    }

    public function test_old_record_missing_exact_fee_schedule_rolls_back_everything(): void
    {
        FeeSchedule::query()->where('year', 2024)->where('member_type_id', $this->typeId('OM'))->delete();
        $this->post(route('admin.farmers.store'), $this->payload('OM'))->assertSessionHasErrors('historical_year');
        foreach (['farmers', 'farmer_profiles', 'membership_transactions', 'payment_assessments', 'payments', 'membership_ledgers'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    public function test_old_record_lookup_finds_code_and_profile_name(): void
    {
        $this->post(route('admin.farmers.store'), $this->payload('OSC'))->assertSessionHasNoErrors();
        $farmer = Farmer::query()->firstOrFail();
        foreach ([$farmer->farmer_code, 'Historical'] as $search) {
            $this->getJson(route('admin.farmers.historical-lookup', ['search' => $search]))
                ->assertOk()->assertJsonPath('farmers.0.id', $farmer->id)
                ->assertJsonPath('farmers.0.recordedYears', [2024]);
        }
    }

    public function test_old_record_existing_application_blocks_same_year_renewal(): void
    {
        $this->post(route('admin.farmers.store'), $this->payload('NM'))->assertSessionHasNoErrors();
        $farmer = Farmer::query()->firstOrFail();
        $this->post(route('admin.farmers.store'), [
            'record_mode' => 'existing', 'farmer_id' => $farmer->id,
            'member_type_id' => $this->typeId('OM'), 'historical_year' => 2024,
        ])->assertSessionHasErrors('historical_year');
        $this->assertDatabaseCount('membership_transactions', 1);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_old_record_duplicate_person_is_offered_existing_farmer_flow(): void
    {
        $payload = $this->payload('OM');
        $this->post(route('admin.farmers.store'), $payload)->assertSessionHasNoErrors();
        $this->post(route('admin.farmers.store'), [...$payload, 'historical_year' => 2025])
            ->assertSessionHasErrors('duplicate_check')->assertSessionHas('duplicate_matches');
        $this->assertDatabaseCount('farmers', 1);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_old_record_database_rejects_duplicate_renewals_from_other_sources(): void
    {
        $this->post(route('admin.farmers.store'), $this->payload('OM'))->assertSessionHasNoErrors();
        $transaction = MembershipTransaction::query()->sole();
        $this->expectException(QueryException::class);
        DB::table('membership_transactions')->insert([
            'farmer_id' => $transaction->farmer_id, 'transaction_type' => 'Renewal',
            'year' => 2024, 'source' => 'walk_in', 'status' => 'Approved',
        ]);
    }

    public function test_old_record_validation_requires_existing_farmer_and_explicit_year(): void
    {
        $this->post(route('admin.farmers.store'), ['record_mode' => 'existing', 'member_type_id' => $this->typeId('OM')])
            ->assertSessionHasErrors(['farmer_id', 'historical_year']);
        $this->post(route('admin.farmers.store'), [...$this->payload('OM'), 'historical_year' => now()->year + 1])
            ->assertSessionHasErrors('historical_year');
        $this->assertDatabaseCount('farmers', 0);
    }

    public function test_old_record_existing_new_member_renews_with_year_specific_old_member_type(): void
    {
        $this->post(route('admin.farmers.store'), $this->payload('NM'))->assertSessionHasNoErrors();
        $farmer = Farmer::query()->firstOrFail();
        $this->post(route('admin.farmers.store'), [
            'record_mode' => 'existing', 'farmer_id' => $farmer->id,
            'historical_year' => 2025, 'member_type_id' => $this->typeId('OM'),
        ])->assertSessionHasNoErrors();
        $renewal = MembershipTransaction::query()->where('year', 2025)->sole();
        $assessment = $renewal->paymentAssessments()->sole();
        $this->assertSame('Renewal', $renewal->transaction_type);
        $this->assertSame(0.0, (float) $assessment->membership_fee);
        $this->assertSame($this->typeId('OM'), $assessment->feeSchedule->member_type_id);
        $this->assertSame($this->typeId('NM'), $farmer->fresh()->member_type_id);
        $this->assertSame('NM', MembershipLedger::query()->where('year', 2024)->sole()->member_type_snapshot);
        $this->assertSame('OM', MembershipLedger::query()->where('year', 2025)->sole()->member_type_snapshot);
        $this->get(route('admin.renewals.show', $renewal->id))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('farmer.memberType.code', 'OM'));
    }

    public function test_old_record_ledger_failure_rolls_back_payment_and_profile(): void
    {
        $this->mock(\App\Services\Membership\MembershipLedgerService::class, function ($mock): void {
            $mock->shouldReceive('buildFromSource')->once()->andThrow(new \DomainException('Cannot record ledger.'));
        });
        $this->post(route('admin.farmers.store'), $this->payload('OM'))->assertSessionHasErrors('historical_year');
        foreach (['farmers', 'farmer_profiles', 'membership_transactions', 'payment_assessments', 'payments', 'membership_ledgers'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    private function typeId(string $code): int
    {
        return (int) MemberType::query()->where('code', $code)->value('id');
    }

    private function payload(string $code): array
    {
        return [
            'record_mode' => 'new', 'historical_year' => 2024,
            'first_name' => 'Historical', 'last_name' => $code, 'sex' => 'female',
            'barangay_id' => $this->barangay->id, 'member_type_id' => $this->typeId($code),
            'status' => 'inactive',
        ];
    }
}
