<?php

namespace App\Services\Documents;

use App\Models\AuditLog;
use App\Models\MembershipTransaction;
use Illuminate\Support\Collection;

class DocumentVerifierAttributionService
{
    /** @return Collection<int, array{name: string, verified_at: mixed}> */
    public function forTransaction(MembershipTransaction $transaction): Collection
    {
        return AuditLog::query()
            ->with('actor:id,name')
            ->where('subject_type', $transaction->getMorphClass())
            ->where('subject_id', $transaction->getKey())
            ->whereIn('event', ['document_verified', 'document_received'])
            ->oldest('created_at')
            ->oldest('id')
            ->get()
            ->mapWithKeys(function (AuditLog $log): array {
                $documentId = (int) ($log->metadata['document_id'] ?? 0);
                $name = $log->actor_name ?: $log->actor?->name;

                return $documentId && $name
                    ? [$documentId => ['name' => $name, 'verified_at' => $log->created_at]]
                    : [];
            });
    }
}
