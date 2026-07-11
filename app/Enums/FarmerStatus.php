<?php

namespace App\Enums;

enum FarmerStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DECEASED = 'deceased';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::DECEASED => 'Deceased',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return array_column(
            array_map(
                fn (self $status): array => ['value' => $status->value, 'label' => $status->label()],
                self::cases(),
            ),
            'label',
            'value',
        );
    }
}
