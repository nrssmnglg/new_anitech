<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            BarangaySeeder::class,
            AssociationSeeder::class,
            MemberTypeSeeder::class,
            FeeScheduleSeeder::class,
            SampleQuerySeeder::class,
        ]);

        User::query()->updateOrCreate(
            ['email' => 'mangaliagneriss@gmail.com'],
            [
                'name' => 'Neriss Mangaliag',
                'password' => 'admin12345',
                'role' => User::ROLE_ADMIN,
                'status' => User::STATUS_ACTIVE,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'staff@anitech.test'],
            [
                'name' => 'Melvin Agbuya',
                'password' => 'staff12345',
                'role' => User::ROLE_STAFF,
                'status' => User::STATUS_ACTIVE,
            ],
        );
    }
}
