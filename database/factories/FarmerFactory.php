<?php

namespace Database\Factories;

use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\MemberType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Farmer>
 */
class FarmerFactory extends Factory
{
    protected $model = Farmer::class;

    public function definition(): array
    {
        $token = Str::upper(Str::random(8));
        $barangay = Barangay::query()->create([
            'code' => 'BRGY-' . $token,
            'name' => 'Barangay ' . $token,
            'status' => 'Active',
        ]);
        $memberType = MemberType::query()->create([
            'code' => 'MT-' . $token,
            'name' => 'Member Type ' . $token,
            'status' => 'Active',
        ]);

        return [
            'farmer_code' => 'FRM-' . $token,
            'barangay_id' => $barangay->id,
            'member_type_id' => $memberType->id,
            'membership_status' => 'Pending',
            'record_origin' => 'Admin',
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Farmer $farmer): void {
            // The current schema stores normalized membership statuses, while
            // the model mutator is intentionally tolerant of legacy inputs.
            $attributes = $farmer->getAttributes();
            $attributes['membership_status'] = 'pending_application';
            $farmer->setRawAttributes($attributes);
        });
    }
}
