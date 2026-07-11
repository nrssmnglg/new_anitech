<?php

namespace App\Services\Membership;

use App\Enums\FarmerStatus;
use App\Enums\MemberTypeCode;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class MemberTypeResolverService
{
    private const SENIOR_AGE = 60;

    public function resolve(array|object $context, ?CarbonInterface $asOf = null): string
    {
        return $this->resolveEnum($context, $asOf)->value;
    }

    public function resolveEnum(array|object $context, ?CarbonInterface $asOf = null): MemberTypeCode
    {
        $data = $this->normalize($context);
        $isSenior = $this->isSeniorCitizen($data, $asOf);
        $hasExistingMembership = $this->hasExistingMembership($data);

        return match (true) {
            $hasExistingMembership && $isSenior => MemberTypeCode::OSC,
            $hasExistingMembership => MemberTypeCode::OM,
            $isSenior => MemberTypeCode::NSC,
            default => MemberTypeCode::NM,
        };
    }

    public function description(MemberTypeCode|string $memberType): string
    {
        return $this->normalizeMemberType($memberType)->label();
    }

    public function isSeniorType(MemberTypeCode|string $memberType): bool
    {
        return $this->normalizeMemberType($memberType)->isSenior();
    }

    public function hasExistingMembership(array|object $context): bool
    {
        $data = $this->normalize($context);

        foreach (['has_existing_membership', 'has_previous_membership', 'is_existing_member'] as $key) {
            if (array_key_exists($key, $data)) {
                return filter_var($data[$key], FILTER_VALIDATE_BOOL);
            }
        }

        if (isset($data['member_since']) && $data['member_since'] !== null && $data['member_since'] !== '') {
            return true;
        }

        if (isset($data['transaction_type'])) {
            $transactionType = strtolower((string) $data['transaction_type']);

            if (in_array($transactionType, ['renewal', 'reactivation'], true)) {
                return true;
            }
        }

        if (isset($data['farmer_status']) && $data['farmer_status'] !== null && $data['farmer_status'] !== '') {
            $status = FarmerStatus::tryFrom((string) $data['farmer_status']);

            if ($status !== null && $status !== FarmerStatus::PENDING) {
                return true;
            }
        }

        return false;
    }

    public function isSeniorCitizen(array|object $context, ?CarbonInterface $asOf = null): bool
    {
        $data = $this->normalize($context);

        foreach (['is_senior_citizen', 'is_senior'] as $key) {
            if (array_key_exists($key, $data)) {
                return filter_var($data[$key], FILTER_VALIDATE_BOOL);
            }
        }

        foreach (['age', 'farmer_age'] as $key) {
            if (isset($data[$key]) && is_numeric($data[$key])) {
                return (int) $data[$key] >= self::SENIOR_AGE;
            }
        }

        foreach (['birth_date', 'date_of_birth', 'dob'] as $key) {
            if (! isset($data[$key]) || $data[$key] === null || $data[$key] === '') {
                continue;
            }

            $comparisonDate = $asOf !== null
                ? CarbonImmutable::instance($asOf)
                : CarbonImmutable::now();
            $birthDate = CarbonImmutable::parse((string) $data[$key]);

            return $birthDate->diffInYears($comparisonDate) >= self::SENIOR_AGE;
        }

        return false;
    }

    public function types(): array
    {
        return MemberTypeCode::options();
    }

    private function normalize(array|object $context): array
    {
        if (is_array($context)) {
            return $context;
        }

        if (method_exists($context, 'toArray')) {
            return $context->toArray();
        }

        return get_object_vars($context);
    }

    private function normalizeMemberType(MemberTypeCode|string $memberType): MemberTypeCode
    {
        return $memberType instanceof MemberTypeCode
            ? $memberType
            : MemberTypeCode::from($memberType);
    }
}
