<?php

namespace Tests\Feature\Admin;

use App\Models\Association;
use App\Models\Barangay;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->actingAs($this->makeAdmin());
    }

    public function test_barangay_index_lists_records(): void
    {
        Barangay::query()->create([
            'name' => 'San Roque',
            'code' => 'BRGY-001',
            'status' => 'active',
        ]);

        $response = $this->get(route('admin.barangays.index'));

        $response->assertOk();
        $response->assertSee('Barangay Management');
        $response->assertSee('San Roque');
    }

    public function test_barangay_index_supports_search_and_filters(): void
    {
        $withAssociation = Barangay::query()->create([
            'name' => 'San Roque',
            'code' => 'BRGY-001',
            'status' => 'active',
        ]);

        Association::query()->create([
            'barangay_id' => $withAssociation->id,
            'name' => 'San Roque Farmers Association',
            'code' => 'ASSOC-001',
            'status' => 'active',
        ]);

        Barangay::query()->create([
            'name' => 'Poblacion',
            'code' => 'BRGY-002',
            'status' => 'inactive',
        ]);

        $response = $this->get(route('admin.barangays.index', [
            'search' => 'San',
            'status' => 'active',
            'association' => 'with',
        ]));

        $response->assertOk();
        $response->assertSee('San Roque');
        $response->assertDontSee('Poblacion');
    }

    public function test_store_creates_barangay_record(): void
    {
        $response = $this->post(route('admin.barangays.store'), [
            'name' => 'Poblacion',
            'code' => 'BRGY-002',
            'status' => 'active',
        ]);

        $barangay = Barangay::query()->first();

        $this->assertNotNull($barangay);
        $response->assertRedirect(route('admin.barangays.show', $barangay));
        $this->assertSame('Poblacion', $barangay->name);
    }

    public function test_store_auto_generates_barangay_code(): void
    {
        $response = $this->post(route('admin.barangays.store'), [
            'name' => 'Bagong Silang',
            'code' => '',
            'status' => 'active',
        ]);

        $barangay = Barangay::query()->where('name', 'Bagong Silang')->first();

        $this->assertNotNull($barangay);
        $response->assertRedirect(route('admin.barangays.show', $barangay));
        $this->assertSame('BRGY01', $barangay->code);
    }

    public function test_store_creates_single_association_for_barangay(): void
    {
        $barangay = Barangay::query()->create([
            'name' => 'San Isidro',
            'code' => 'BRGY-003',
            'status' => 'active',
        ]);

        $response = $this->post(route('admin.associations.store'), [
            'barangay_id' => $barangay->id,
            'name' => 'San Isidro Farmers Association',
            'code' => 'ASSOC-001',
            'status' => 'active',
        ]);

        $association = Association::query()->first();

        $this->assertNotNull($association);
        $response->assertRedirect(route('admin.associations.show', $association));
        $this->assertSame($barangay->id, $association->barangay_id);
    }

    public function test_store_auto_generates_association_code(): void
    {
        $barangay = Barangay::query()->create([
            'name' => 'San Jose',
            'code' => 'BRGY-005',
            'status' => 'active',
        ]);

        $response = $this->post(route('admin.associations.store'), [
            'barangay_id' => $barangay->id,
            'name' => 'San Jose Farmers Association',
            'code' => '',
            'status' => 'active',
        ]);

        $association = Association::query()->where('name', 'San Jose Farmers Association')->first();

        $this->assertNotNull($association);
        $response->assertRedirect(route('admin.associations.show', $association));
        $this->assertSame('ASSOC01', $association->code);
    }

    public function test_association_index_supports_search_and_filters(): void
    {
        $barangayOne = Barangay::query()->create([
            'name' => 'San Isidro',
            'code' => 'BRGY-006',
            'status' => 'active',
        ]);

        $barangayTwo = Barangay::query()->create([
            'name' => 'Poblacion',
            'code' => 'BRGY-007',
            'status' => 'active',
        ]);

        Association::query()->create([
            'barangay_id' => $barangayOne->id,
            'name' => 'San Isidro Farmers Association',
            'code' => 'ASSOC-010',
            'status' => 'active',
        ]);

        Association::query()->create([
            'barangay_id' => $barangayTwo->id,
            'name' => 'Poblacion Growers',
            'code' => 'ASSOC-011',
            'status' => 'inactive',
        ]);

        $response = $this->get(route('admin.associations.index', [
            'search' => 'San Isidro',
            'status' => 'active',
            'barangay_id' => $barangayOne->id,
        ]));

        $response->assertOk();
        $response->assertSee('San Isidro Farmers Association');
        $response->assertDontSee('Poblacion Growers');
    }

    public function test_store_rejects_second_association_for_same_barangay(): void
    {
        $barangay = Barangay::query()->create([
            'name' => 'Mabini',
            'code' => 'BRGY-004',
            'status' => 'active',
        ]);

        Association::query()->create([
            'barangay_id' => $barangay->id,
            'name' => 'Mabini Growers',
            'code' => 'ASSOC-002',
            'status' => 'active',
        ]);

        $response = $this->from(route('admin.associations.create'))->post(route('admin.associations.store'), [
            'barangay_id' => $barangay->id,
            'name' => 'Mabini Producers',
            'code' => 'ASSOC-003',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.associations.create'));
        $response->assertSessionHasErrors('barangay_id');
        $this->assertDatabaseCount('associations', 1);
    }

    public function test_store_rejects_duplicate_barangay_name(): void
    {
        Barangay::query()->create([
            'name' => 'San Roque',
            'code' => 'BRGY-008',
            'status' => 'active',
        ]);

        $response = $this->from(route('admin.barangays.create'))->post(route('admin.barangays.store'), [
            'name' => 'San Roque',
            'code' => 'BRGY-009',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.barangays.create'));
        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('barangays', 1);
    }

    public function test_store_rejects_duplicate_association_name(): void
    {
        $barangayOne = Barangay::query()->create([
            'name' => 'San Jose',
            'code' => 'BRGY-010',
            'status' => 'active',
        ]);

        $barangayTwo = Barangay::query()->create([
            'name' => 'Mabini',
            'code' => 'BRGY-011',
            'status' => 'active',
        ]);

        Association::query()->create([
            'barangay_id' => $barangayOne->id,
            'name' => 'Unified Farmers Association',
            'code' => 'ASSOC-020',
            'status' => 'active',
        ]);

        $response = $this->from(route('admin.associations.create'))->post(route('admin.associations.store'), [
            'barangay_id' => $barangayTwo->id,
            'name' => 'Unified Farmers Association',
            'code' => 'ASSOC-021',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.associations.create'));
        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('associations', 1);
    }

    public function test_can_toggle_barangay_status_from_index(): void
    {
        $barangay = Barangay::query()->create([
            'name' => 'Bagong Silang',
            'code' => 'BRGY-012',
            'status' => 'active',
        ]);

        $response = $this->from(route('admin.barangays.index'))->patch(route('admin.barangays.status', $barangay), [
            'status' => 'inactive',
        ]);

        $response->assertRedirect(route('admin.barangays.index'));
        $this->assertSame('inactive', $barangay->fresh()->status);
    }

    public function test_can_toggle_association_status_from_index(): void
    {
        $barangay = Barangay::query()->create([
            'name' => 'Calumpang',
            'code' => 'BRGY-013',
            'status' => 'active',
        ]);

        $association = Association::query()->create([
            'barangay_id' => $barangay->id,
            'name' => 'Calumpang Farmers Association',
            'code' => 'ASSOC-022',
            'status' => 'active',
        ]);

        $response = $this->from(route('admin.associations.index'))->patch(route('admin.associations.status', $association), [
            'status' => 'inactive',
        ]);

        $response->assertRedirect(route('admin.associations.index'));
        $this->assertSame('inactive', $association->fresh()->status);
    }

    public function test_edit_barangay_shows_dependency_warnings(): void
    {
        $barangay = Barangay::query()->create([
            'name' => 'Magsaysay',
            'code' => 'BRGY-014',
            'status' => 'active',
        ]);

        Association::query()->create([
            'barangay_id' => $barangay->id,
            'name' => 'Magsaysay Association',
            'code' => 'ASSOC-023',
            'status' => 'active',
        ]);

        $response = $this->get(route('admin.barangays.edit', $barangay));

        $response->assertOk();
        $response->assertSee('Dependency Warnings');
        $response->assertSee('associated association record');
    }

    public function test_edit_association_shows_dependency_warnings(): void
    {
        $barangay = Barangay::query()->create([
            'name' => 'Lourdes',
            'code' => 'BRGY-015',
            'status' => 'inactive',
        ]);

        $association = Association::query()->create([
            'barangay_id' => $barangay->id,
            'name' => 'Lourdes Farmers Association',
            'code' => 'ASSOC-024',
            'status' => 'active',
        ]);

        $response = $this->get(route('admin.associations.edit', $association));

        $response->assertOk();
        $response->assertSee('Dependency Warnings');
        $response->assertSee('barangay is currently inactive');
    }

    private function makeAdmin(): User
    {
        $admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin.location@example.test',
            'password' => 'secret123',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $admin->assignRole(User::ROLE_ADMIN);

        return $admin;
    }
}
