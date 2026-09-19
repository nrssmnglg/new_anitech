<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\HasEncryptedPublicRouteKey;
use Database\Factories\UserFactory;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements CanResetPasswordContract
{
    /** @use HasFactory<UserFactory> */
    use CanResetPassword, HasEncryptedPublicRouteKey, HasFactory, HasRoles, Notifiable {
        HasRoles::hasRole as private hasSpatieRole;
    }

    public const ROLE_ADMIN = 'Admin';
    public const ROLE_STAFF = 'Staff';
    public const ROLE_FARMER = 'Farmer';

    public const STATUS_PENDING = 'Pending';
    public const STATUS_ACTIVE = 'Active';
    public const STATUS_INACTIVE = 'Inactive';
    public const STATUS_SUSPENDED = 'Suspended';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'farmer_id',
        'role',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function officeProfile(): HasOne
    {
        return $this->hasOne(OfficeProfile::class);
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    public function hasRole($roles, ?string $guard = null): bool
    {
        if ($this->hasSpatieRole($roles, $guard)) {
            return true;
        }

        if (is_string($roles)) {
            return $this->role === $roles;
        }

        if ($roles instanceof \BackedEnum) {
            return $this->role === $roles->value;
        }

        if ($roles instanceof RoleContract) {
            return $this->role === $roles->getAttribute('name');
        }

        if ($roles instanceof Collection) {
            return $roles->contains(fn ($role) => $this->hasRole($role, $guard));
        }

        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->hasRole($role, $guard)) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }
}
