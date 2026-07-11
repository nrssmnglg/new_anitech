<?php

namespace App\Services\Membership;

use App\Enums\MemberTypeCode;
use App\Models\FeeSchedule;
use InvalidArgumentException;

class FeeCalculatorService
{
    private const FEE_MATRIX = [
        MemberTypeCode::NM->value => [
            'membership_fee' => 100.0,
            'annual_due' => 100.0,
            'mortuary_fee' => 150.0,
        ],
        MemberTypeCode::OM->value => [
            'membership_fee' => 0.0,
            'annual_due' => 100.0,
            'mortuary_fee' => 150.0,
        ],
        MemberTypeCode::NSC->value => [
            'membership_fee' => 100.0,
            'annual_due' => 100.0,
            'mortuary_fee' => 0.0,
        ],
        MemberTypeCode::OSC->value => [
            'membership_fee' => 0.0,
            'annual_due' => 100.0,
            'mortuary_fee' => 0.0,
        ],
    ];

    public function __construct(
        private readonly MemberTypeResolverService $memberTypeResolver,
    ) {
    }

    public function calculate(array|object $context, iterable $feeDefinitions = []): array
    {
        $data = $this->normalize($context);
        $transactionType = $this->transactionType($data);
        $memberType = $this->memberType($data);
        $lineItems = $this->resolveLineItems($data, $memberType, $transactionType, $feeDefinitions);
        $breakdown = $this->breakdown($lineItems);
        $subtotal = $this->sumLineItems($lineItems);
        $discount = $this->normalizeAmount($data['discount'] ?? 0);
        $surcharge = $this->normalizeAmount($data['surcharge'] ?? 0);
        $total = round(max(0, $subtotal - $discount + $surcharge), 2);

        return [
            'transaction_type' => $transactionType,
            'member_type' => $memberType->value,
            'mortuary_eligible' => ! $memberType->isSenior(),
            'line_items' => $lineItems,
            'membership_fee' => $breakdown['membership_fee'],
            'annual_due' => $breakdown['annual_due'],
            'mortuary_fee' => $breakdown['mortuary_fee'],
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'surcharge' => round($surcharge, 2),
            'total' => $total,
        ];
    }

    public function calculateApplication(array|object $context, iterable $feeDefinitions = []): array
    {
        return $this->calculate($this->withTransactionType($context, 'application'), $feeDefinitions);
    }

    public function calculateRenewal(array|object $context, iterable $feeDefinitions = []): array
    {
        return $this->calculate($this->withTransactionType($context, 'renewal'), $feeDefinitions);
    }

    public function calculateReactivation(array|object $context, iterable $feeDefinitions = []): array
    {
        return $this->calculate($this->withTransactionType($context, 'reactivation'), $feeDefinitions);
    }

    public function usesTestingChargeAmount(): bool
    {
        return $this->testingChargeAmount() !== null;
    }

    public function resolveChargeAmount(mixed $actualAmount): float
    {
        return $this->testingChargeAmount() ?? $this->normalizeAmount($actualAmount);
    }

    public function testingChargeAmount(): ?float
    {
        $value = config('services.paymongo.qrph_test_amount');

        if (! is_numeric($value)) {
            return null;
        }

        $amount = round((float) $value, 2);

        return $amount > 0 ? $amount : null;
    }

    private function resolveLineItems(array $context, MemberTypeCode $memberType, string $transactionType, iterable $feeDefinitions): array
    {
        $definitions = is_array($feeDefinitions)
            ? array_values($feeDefinitions)
            : iterator_to_array($feeDefinitions, false);

        if ($definitions !== []) {
            $lineItems = [];

            foreach ($definitions as $definition) {
                $item = $this->normalizeLineItem($definition);

                if (! $this->matches($item, 'member_type', $memberType->value)) {
                    continue;
                }

                if (! $this->matches($item, 'transaction_type', $transactionType)) {
                    continue;
                }

                $quantity = isset($item['quantity']) ? max(1, (int) $item['quantity']) : 1;
                $amount = $this->normalizeAmount($item['amount'] ?? 0);

                $lineItems[] = [
                    'code' => (string) ($item['code'] ?? 'fee_' . (count($lineItems) + 1)),
                    'label' => (string) ($item['label'] ?? $item['name'] ?? 'Fee Item'),
                    'quantity' => $quantity,
                    'unit_amount' => $amount,
                    'amount' => round($amount * $quantity, 2),
                ];
            }

            if ($lineItems !== []) {
                return $lineItems;
            }
        }

        $contextLineItems = $this->contextLineItems($context);

        if ($contextLineItems !== []) {
            return $contextLineItems;
        }

        if (($context['fee_schedule'] ?? null) instanceof FeeSchedule) {
            return $this->lineItemsFromFees($this->feesForTransaction([
                'fee_schedule' => $context['fee_schedule'],
            ], $memberType, $transactionType));
        }

        return $this->fixedRuleLineItems($memberType, $transactionType);
    }

    private function fixedRuleLineItems(MemberTypeCode $memberType, string $transactionType): array
    {
        $fees = self::FEE_MATRIX[$memberType->value] ?? null;

        if ($fees === null) {
            throw new InvalidArgumentException("Unsupported member type [{$memberType->value}].");
        }

        return $this->lineItemsFromFees($this->feesForTransaction($fees, $memberType, $transactionType));
    }

    private function feesForTransaction(array $fees, MemberTypeCode $memberType, string $transactionType): array
    {
        $annualDue = $transactionType === 'renewal'
            ? 0.0
            : $this->normalizeAmount(($fees['fee_schedule'] ?? null) instanceof FeeSchedule
                ? $fees['fee_schedule']->annual_due
                : ($fees['annual_due'] ?? 0));

        if (($fees['fee_schedule'] ?? null) instanceof FeeSchedule) {
            /** @var FeeSchedule $feeSchedule */
            $feeSchedule = $fees['fee_schedule'];

            return [
                'membership_fee' => $transactionType === 'application' && $memberType->isNewMember()
                    ? $this->normalizeAmount($feeSchedule->membership_fee)
                    : 0.0,
                'annual_due' => $annualDue,
                'mortuary_fee' => $memberType->isSenior()
                    ? 0.0
                    : $this->normalizeAmount($feeSchedule->mortuary_fee),
            ];
        }

        return [
            'membership_fee' => $transactionType === 'application' && $memberType->isNewMember()
                ? $this->normalizeAmount($fees['membership_fee'] ?? 0)
                : 0.0,
            'annual_due' => $annualDue,
            'mortuary_fee' => $memberType->isSenior()
                ? 0.0
                : $this->normalizeAmount($fees['mortuary_fee'] ?? 0),
        ];
    }

    private function lineItemsFromFees(array $fees): array
    {
        $lineItems = [];

        foreach ([
            'membership_fee' => 'Membership Fee',
            'annual_due' => 'Annual Due',
            'mortuary_fee' => 'Mortuary Fee',
        ] as $code => $label) {
            $amount = $this->normalizeAmount($fees[$code] ?? 0);

            if ($amount <= 0) {
                continue;
            }

            $lineItems[] = [
                'code' => $code,
                'label' => $label,
                'quantity' => 1,
                'unit_amount' => $amount,
                'amount' => $amount,
            ];
        }

        return $lineItems;
    }

    private function contextLineItems(array $context): array
    {
        $lineItems = [];

        foreach (($context['line_items'] ?? []) as $lineItem) {
            $normalized = $this->normalizeLineItem($lineItem);
            $quantity = isset($normalized['quantity']) ? max(1, (int) $normalized['quantity']) : 1;
            $unitAmount = $this->normalizeAmount($normalized['unit_amount'] ?? $normalized['amount'] ?? 0);

            $lineItems[] = [
                'code' => (string) ($normalized['code'] ?? 'fee_' . (count($lineItems) + 1)),
                'label' => (string) ($normalized['label'] ?? $normalized['name'] ?? 'Fee Item'),
                'quantity' => $quantity,
                'unit_amount' => $unitAmount,
                'amount' => round($unitAmount * $quantity, 2),
            ];
        }

        if ($lineItems !== []) {
            return $lineItems;
        }

        if (array_key_exists('base_amount', $context) || array_key_exists('amount', $context)) {
            $amount = $this->normalizeAmount($context['base_amount'] ?? $context['amount'] ?? 0);

            return [[
                'code' => 'base_fee',
                'label' => 'Base Fee',
                'quantity' => 1,
                'unit_amount' => $amount,
                'amount' => $amount,
            ]];
        }

        return [];
    }

    private function breakdown(array $lineItems): array
    {
        $breakdown = [
            'membership_fee' => 0.0,
            'annual_due' => 0.0,
            'mortuary_fee' => 0.0,
        ];

        foreach ($lineItems as $lineItem) {
            $code = (string) ($lineItem['code'] ?? '');

            if (array_key_exists($code, $breakdown)) {
                $breakdown[$code] = round($breakdown[$code] + $this->normalizeAmount($lineItem['amount'] ?? 0), 2);
            }
        }

        return $breakdown;
    }

    private function normalizeLineItem(array|object $lineItem): array
    {
        if (is_array($lineItem)) {
            return $lineItem;
        }

        if (method_exists($lineItem, 'toArray')) {
            return $lineItem->toArray();
        }

        return get_object_vars($lineItem);
    }

    private function matches(array $item, string $prefix, string $needle): bool
    {
        $single = $item[$prefix] ?? null;
        $multiple = $item[$prefix . 's'] ?? null;

        if ($single === null && $multiple === null) {
            return true;
        }

        $haystack = $multiple ?? [$single];

        if (! is_array($haystack)) {
            $haystack = [$haystack];
        }

        return in_array($needle, array_map('strval', $haystack), true);
    }

    private function sumLineItems(array $lineItems): float
    {
        return array_reduce(
            $lineItems,
            fn (float $carry, array $lineItem): float => $carry + $this->normalizeAmount($lineItem['amount'] ?? 0),
            0.0,
        );
    }

    private function transactionType(array $context): string
    {
        $transactionType = strtolower((string) ($context['transaction_type'] ?? 'application'));

        if (! in_array($transactionType, ['application', 'renewal', 'reactivation'], true)) {
            throw new InvalidArgumentException("Unsupported transaction type [{$transactionType}].");
        }

        return $transactionType;
    }

    private function memberType(array $context): MemberTypeCode
    {
        if (isset($context['member_type']) && $context['member_type'] !== null && $context['member_type'] !== '') {
            return $context['member_type'] instanceof MemberTypeCode
                ? $context['member_type']
                : MemberTypeCode::from((string) $context['member_type']);
        }

        return $this->memberTypeResolver->resolveEnum($context);
    }

    private function withTransactionType(array|object $context, string $transactionType): array
    {
        $data = $this->normalize($context);
        $data['transaction_type'] = $transactionType;

        return $data;
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

    private function normalizeAmount(mixed $amount): float
    {
        if ($amount === null || $amount === '') {
            return 0.0;
        }

        return round((float) $amount, 2);
    }
}
