<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AuditFarmerUserRoles extends Command
{
    protected $signature = 'app:audit-farmer-user-roles {--fix : Update mismatched farmer-linked users to the Farmer role}';

    protected $description = 'Audit farmer-linked user accounts for role mismatches and optionally repair them.';

    public function handle(): int
    {
        $users = User::query()
            ->whereNotNull('farmer_id')
            ->where('role', '!=', User::ROLE_FARMER)
            ->orderBy('id')
            ->get(['id', 'name', 'email', 'farmer_id', 'role']);

        if ($users->isEmpty()) {
            $this->info('No farmer-linked users with mismatched roles were found.');

            return self::SUCCESS;
        }

        $this->warn('Found farmer-linked users with non-Farmer roles:');
        $this->table(
            ['User ID', 'Name', 'Email', 'Farmer ID', 'Current Role'],
            $users->map(fn (User $user): array => [
                $user->id,
                $user->name,
                $user->email,
                $user->farmer_id,
                $user->role,
            ])->all(),
        );

        if (! $this->option('fix')) {
            $this->line('Run with `--fix` to repair these accounts.');

            return self::FAILURE;
        }

        $repaired = 0;

        foreach ($users as $user) {
            $user->forceFill([
                'role' => User::ROLE_FARMER,
            ])->save();

            $user->syncRoles([User::ROLE_FARMER]);
            $repaired++;
        }

        $this->info("Repaired {$repaired} farmer-linked user account(s).");

        return self::SUCCESS;
    }
}
