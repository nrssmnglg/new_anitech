<?php

namespace App\Enums;

enum MemberTypeCode: string
{
    case NM = 'NM';
    case OM = 'OM';
    case NSC = 'NSC';
    case OSC = 'OSC';

    public function label(): string
    {
        return match ($this) {
            self::NM => 'New Member',
            self::OM => 'Old Member',
            self::NSC => 'New Senior Citizen',
            self::OSC => 'Old Senior Citizen',
        };
    }

    public function isNewMember(): bool
    {
        return in_array($this, [self::NM, self::NSC], true);
    }

    public function isSenior(): bool
    {
        return in_array($this, [self::NSC, self::OSC], true);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return array_column(
            array_map(
                fn (self $type): array => ['value' => $type->value, 'label' => $type->label()],
                self::cases(),
            ),
            'label',
            'value',
        );
    }
}
