<?php

namespace App\Models;

use App\Enums\DocumentType as DocumentTypeEnum;
use App\Enums\DocumentVerificationStatus;
use App\Models\Concerns\HasEncryptedPublicRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FarmerDocument extends Model
{
    use HasEncryptedPublicRouteKey;
    use HasFactory;

    protected $fillable = [
        'membership_transaction_id',
        'document_type_id',
        'original_name',
        'file_path',
        'verification_status',
        'verified_by',
        'verified_at',
        'remarks',
        'uploaded_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'uploaded_at' => 'datetime',
    ];

    public function membershipTransaction(): BelongsTo
    {
        return $this->belongsTo(MembershipTransaction::class);
    }

    public function membershipApplication(): BelongsTo
    {
        return $this->belongsTo(MembershipApplication::class, 'membership_transaction_id');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    protected function verificationStatus(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value): ?DocumentVerificationStatus {
                if ($value instanceof DocumentVerificationStatus) {
                    return $value;
                }

                $normalized = strtolower((string) $value);

                return DocumentVerificationStatus::tryFrom($normalized);
            },
            set: function (mixed $value): ?string {
                $normalized = $value instanceof DocumentVerificationStatus
                    ? $value->value
                    : strtolower((string) $value);

                return match ($normalized) {
                    DocumentVerificationStatus::VERIFIED->value => 'Verified',
                    DocumentVerificationStatus::REJECTED->value => 'Rejected',
                    DocumentVerificationStatus::EXPIRED->value => 'Rejected',
                    default => 'Pending',
                };
            },
        );
    }

    public function getDocumentTypeAttribute(): ?DocumentTypeEnum
    {
        $documentType = $this->relationLoaded('documentType')
            ? $this->relations['documentType']
            : $this->documentType()->first(['id', 'code']);

        return DocumentTypeEnum::tryFrom((string) $documentType?->code);
    }

    public function getPathAttribute(): string
    {
        return (string) $this->file_path;
    }

    public function getDiskAttribute(): string
    {
        return 'public';
    }

    public function getIsRequiredAttribute(): bool
    {
        return true;
    }

    public function getIsReceivedAttribute(): bool
    {
        $path = trim((string) $this->file_path);

        if ($this->verification_status === DocumentVerificationStatus::VERIFIED) {
            return true;
        }

        return $path !== ''
            && ! str_starts_with($path, 'pending-upload/')
            && ! str_starts_with($path, 'office-checklist/');
    }
}
