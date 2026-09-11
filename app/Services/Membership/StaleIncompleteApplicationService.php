<?php

namespace App\Services\Membership;

use App\Enums\ApplicationStatus;
use App\Enums\MembershipApplicationRejectionReason;
use App\Models\MembershipApplication;
use App\Services\Audit\AuditTrailService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class StaleIncompleteApplicationService
{
    public const INACTIVITY_DAYS = 7;

    public function __construct(private readonly AuditTrailService $auditTrailService)
    {
    }

    public function rejectInactive(): int
    {
        $cutoff = CarbonImmutable::now()->subDays(self::INACTIVITY_DAYS);
        $rejected = 0;

        MembershipApplication::query()
            ->where('status', 'Pending')
            ->where('updated_at', '<=', $cutoff)
            ->with(['documents:id,membership_transaction_id,file_path,verification_status,updated_at'])
            ->orderBy('id')
            ->chunkById(100, function ($applications) use ($cutoff, &$rejected): void {
                foreach ($applications as $application) {
                    if (! $this->isIncomplete($application) || $this->lastActivityAt($application)->gt($cutoff)) {
                        continue;
                    }

                    DB::transaction(function () use ($application, $cutoff, &$rejected): void {
                        $locked = MembershipApplication::query()
                            ->with(['documents:id,membership_transaction_id,file_path,verification_status,updated_at'])
                            ->lockForUpdate()
                            ->find($application->getKey());

                        if ($locked === null
                            || $locked->status !== ApplicationStatus::SUBMITTED
                            || ! $this->isIncomplete($locked)
                            || $this->lastActivityAt($locked)->gt($cutoff)) {
                            return;
                        }

                        $locked->forceFill([
                            'status' => ApplicationStatus::REJECTED,
                            'rejection_reason' => MembershipApplicationRejectionReason::UNABLE_TO_FOLLOW_UP->value,
                            'rejected_at' => now(),
                        ])->save();

                        $this->auditTrailService->recordById(
                            'membership_applications',
                            'application_auto_rejected',
                            'Rejected an incomplete membership application after seven days without activity.',
                            null,
                            $locked,
                            [
                                'application_no' => $locked->application_no,
                                'reason' => MembershipApplicationRejectionReason::UNABLE_TO_FOLLOW_UP->label(),
                                'inactivity_days' => self::INACTIVITY_DAYS,
                            ],
                        );

                        $rejected++;
                    });
                }
            });

        return $rejected;
    }

    private function isIncomplete(MembershipApplication $application): bool
    {
        return $application->documents->isEmpty()
            || $application->documents->contains(fn ($document): bool => ! $document->is_received);
    }

    private function lastActivityAt(MembershipApplication $application): CarbonImmutable
    {
        return collect([$application->updated_at])
            ->merge($application->documents->pluck('updated_at'))
            ->filter()
            ->map(fn ($timestamp) => CarbonImmutable::parse($timestamp))
            ->max() ?? CarbonImmutable::parse($application->created_at);
    }
}
