# Normalized ERD for `new_anitech`

This document presents a conceptual normalized ERD for the project.

Purpose:
- define the core business entities
- keep only important conceptual attributes
- separate authentication data from personal profile data
- remove redundant and circular references
- provide a cleaner structure for `draw.io` and manuscript presentation

Important:
- this version is conceptual and normalized
- it does not depend on the exact structure of the current system implementation
- only necessary relationships are kept
- profile data is separated from account data and transaction data

## Excluded Tables

These are excluded because they are framework, access-control, logging, notification, export, or support tables:

- `cache`
- `cache_locks`
- `jobs`
- `job_batches`
- `failed_jobs`
- `sessions`
- `password_reset_tokens`
- `personal_access_tokens`
- `roles`
- `permissions`
- `model_has_roles`
- `model_has_permissions`
- `role_has_permissions`
- `notifications`
- `notification_recipients`
- `report_exports`
- `audit_logs`
- `farmer_password_reset_otps`
- `office_password_reset_otps`
- `paymongo_webhook_events`

## Core Entity Groups

### Core Registry
- `users`
- `office_profiles`
- `barangays`
- `associations`
- `member_types`
- `farmers`
- `farmer_profiles`

### Membership Lifecycle
- `membership_applications`
- `renewal_requests`
- `reactivation_requests`
- `document_requirements`
- `farmer_documents`
- `fee_schedules`
- `payment_assessments`
- `payments`
- `membership_ledgers`
- `mortuary_claims`

### Communication
- `advisories`
- `advisory_attachments`
- `advisory_member_type`
- `queries`
- `query_images`
- `query_responses`
- `query_response_attachments`

## Entities and Attributes

### `users`
Purpose: authentication and account table for all system users.

Important attributes:
- `id` PK
- `email` unique
- `password`
- `status`

Normalization note:
- `users` stores only account-level data
- personal identity data is moved to `office_profiles`
- farmers do not need a user account to exist in the registry

### `office_profiles`
Purpose: one-to-one office personnel profile for a user account.

Important attributes:
- `id` PK
- `user_id` FK -> `users.id`, unique
- `first_name`
- `middle_name`, nullable
- `last_name`
- `suffix`, nullable
- `employee_id` unique, nullable
- `job_title`, nullable
- `contact_number`, nullable

### `barangays`
Purpose: barangay master list.

Important attributes:
- `id` PK
- `name`
- `code` unique, nullable
- `status`

### `associations`
Purpose: farmer association master list.

Important attributes:
- `id` PK
- `barangay_id` FK -> `barangays.id`, nullable
- `name`
- `code` unique, nullable
- `status`

Normalization note:
- an association may belong to one barangay
- a barangay may have many associations

### `member_types`
Purpose: membership classification reference for farmers.

Important attributes:
- `id` PK
- `code` unique
- `name`
- `is_new_member`
- `is_senior`
- `requires_membership_fee`
- `mortuary_eligible`

### `farmers`
Purpose: main farmer registry and membership-tracking table.

Important attributes:
- `id` PK
- `farmer_code` unique
- `barangay_id` FK -> `barangays.id`
- `association_id` FK -> `associations.id`, nullable
- `member_type_id` FK -> `member_types.id`, nullable
- `status`
- `membership_status`
- `record_origin`
- `is_registry_record`
- `registered_at`, nullable
- `activated_at`, nullable
- `last_renewal_year`, nullable
- `inactive_at`, nullable
- `inactive_reason`, nullable

Normalization note:
- `farmers` stores membership and registry status, not personal identity attributes
- personal identity and contact details are stored in `farmer_profiles`
- no direct back-reference to source transactions is included, to avoid circular dependencies

### `farmer_profiles`
Purpose: one-to-one personal profile of a farmer.

Important attributes:
- `id` PK
- `farmer_id` FK -> `farmers.id`, unique
- `first_name`
- `middle_name`, nullable
- `last_name`
- `suffix`, nullable
- `sex`, nullable
- `birth_date`, nullable
- `civil_status`, nullable
- `mobile_number`, nullable
- `email`, nullable
- `address`, nullable

### `membership_applications`
Purpose: new membership application transaction.

Important attributes:
- `id` PK
- `farmer_id` FK -> `farmers.id`
- `application_no` unique
- `source`
- `status`
- `submitted_at`, nullable
- `reviewed_by` FK -> `users.id`, nullable
- `reviewed_at`, nullable
- `approved_at`, nullable
- `remarks`, nullable
- `rejection_reason`, nullable
- `rejection_details`, nullable

### `renewal_requests`
Purpose: annual renewal transaction.

Important attributes:
- `id` PK
- `farmer_id` FK -> `farmers.id`
- `year`
- `source`
- `status`
- `submitted_at`, nullable
- `reviewed_by` FK -> `users.id`, nullable
- `reviewed_at`, nullable
- `approved_at`, nullable
- `is_late`
- `remarks`, nullable

Constraint:
- unique(`farmer_id`, `year`)

### `reactivation_requests`
Purpose: reactivation request for inactive members.

Important attributes:
- `id` PK
- `farmer_id` FK -> `farmers.id`
- `status`
- `reason`, nullable
- `submitted_at`, nullable
- `reviewed_by` FK -> `users.id`, nullable
- `reviewed_at`, nullable
- `remarks`, nullable

### `document_requirements`
Purpose: rules table for required documents by workflow.

Important attributes:
- `id` PK
- `workflow`
- `source`, nullable
- `document_type`
- `is_active`
- `sort_order`

Constraint:
- unique(`workflow`, `source`, `document_type`)

### `farmer_documents`
Purpose: submitted documents attached to a membership application or renewal request.

Important attributes:
- `id` PK
- `membership_application_id` FK -> `membership_applications.id`, nullable
- `renewal_request_id` FK -> `renewal_requests.id`, nullable
- `document_type`
- `original_name`
- `is_required`
- `is_received`
- `received_by` FK -> `users.id`, nullable
- `verification_status`
- `verified_by` FK -> `users.id`, nullable
- `verified_at`, nullable
- `remarks`, nullable

Normalization note:
- `farmer_id` is intentionally omitted because the farmer is derived from the parent transaction

### `fee_schedules`
Purpose: yearly fee configuration table.

Important attributes:
- `id` PK
- `year` unique
- `membership_fee`
- `annual_due`
- `mortuary_fee`
- `renewal_deadline`
- `is_active`
- `effective_from`
- `effective_to`, nullable

### `payment_assessments`
Purpose: computed payment obligation for an application or renewal.

Important attributes:
- `id` PK
- `membership_application_id` FK -> `membership_applications.id`, nullable
- `renewal_request_id` FK -> `renewal_requests.id`, nullable
- `fee_schedule_id` FK -> `fee_schedules.id`
- `member_type_snapshot`
- `membership_fee`
- `annual_due`
- `mortuary_fee`
- `total_amount_due`
- `due_date`, nullable
- `status`

Normalization note:
- `farmer_id` is intentionally omitted because the farmer is derived from the source transaction

### `payments`
Purpose: actual payment records under an assessment.

Important attributes:
- `id` PK
- `payment_assessment_id` FK -> `payment_assessments.id`
- `payment_method`
- `reference_no`, nullable
- `amount_paid`
- `paid_at`
- `verified_by` FK -> `users.id`, nullable
- `verified_at`, nullable
- `status`
- `receipt_no`, nullable

### `membership_ledgers`
Purpose: posted yearly membership result from an approved application or renewal.

Important attributes:
- `id` PK
- `year`
- `membership_application_id` FK -> `membership_applications.id`, nullable
- `renewal_request_id` FK -> `renewal_requests.id`, nullable
- `member_type_snapshot`
- `membership_fee`
- `annual_due`
- `mortuary_fee`
- `total_amount_due`
- `amount_paid`
- `paid_at`, nullable
- `payment_status`
- `mortuary_eligible`
- `status`

Normalization note:
- `farmer_id` is intentionally omitted because the farmer is derived from the source transaction

### `mortuary_claims`
Purpose: benefit claim based on an eligible membership ledger.

Important attributes:
- `id` PK
- `membership_ledger_id` FK -> `membership_ledgers.id`
- `claim_reference` unique
- `claim_amount`
- `claim_date`
- `claimer_name`, nullable
- `claimer_relationship`, nullable
- `claimer_contact_number`, nullable
- `claimer_address`, nullable
- `claimer_valid_id_received`
- `proof_of_relationship_received`
- `death_certificate_received`
- `status`
- `filed_by` FK -> `users.id`, nullable
- `approved_by` FK -> `users.id`, nullable
- `released_by` FK -> `users.id`, nullable
- `remarks`, nullable

Normalization note:
- `farmer_id` is intentionally omitted because both the farmer and the eligibility basis can be derived from `membership_ledger_id`

### `advisories`
Purpose: announcements published to specific audiences.

Important attributes:
- `id` PK
- `title`
- `slug` unique
- `status`
- `audience_type`
- `barangay_id` FK -> `barangays.id`, nullable
- `association_id` FK -> `associations.id`, nullable
- `published_by` FK -> `users.id`, nullable
- `published_at`, nullable

### `advisory_attachments`
Purpose: files attached to advisories.

Important attributes:
- `id` PK
- `advisory_id` FK -> `advisories.id`
- `original_name`

### `advisory_member_type`
Purpose: bridge table for advisory targeting by member type.

Important attributes:
- `id` PK
- `advisory_id` FK -> `advisories.id`
- `member_type_id` FK -> `member_types.id`

Constraint:
- unique(`advisory_id`, `member_type_id`)

### `queries`
Purpose: farmer-submitted concerns or inquiries.

Important attributes:
- `id` PK
- `farmer_id` FK -> `farmers.id`
- `subject`
- `status`
- `archived_at`, nullable

### `query_images`
Purpose: images attached to a farmer query.

Important attributes:
- `id` PK
- `query_id` FK -> `queries.id`
- `original_name`

### `query_responses`
Purpose: office response entries for a query.

Important attributes:
- `id` PK
- `query_id` FK -> `queries.id`
- `responded_by` FK -> `users.id`, nullable
- `message`

### `query_response_attachments`
Purpose: file attachments under a query response.

Important attributes:
- `id` PK
- `query_response_id` FK -> `query_responses.id`
- `original_name`

## Main Normalized Relationships

Use these as the primary connectors in `draw.io`.

### Core Registry
- `users` 1 -> 0..1 `office_profiles` : "has office profile"
- `barangays` 1 -> many `associations` : "has associations"
- `barangays` 1 -> many `farmers` : "contains farmers"
- `associations` 1 -> many `farmers` : "groups farmers"
- `member_types` 1 -> many `farmers` : "classifies farmers"
- `farmers` 1 -> 1 `farmer_profiles` : "has personal profile"

### Membership Lifecycle
- `farmers` 1 -> many `membership_applications` : "submits"
- `farmers` 1 -> many `renewal_requests` : "files"
- `farmers` 1 -> many `reactivation_requests` : "requests"
- `membership_applications` 1 -> many `farmer_documents` : "has submitted documents"
- `renewal_requests` 1 -> many `farmer_documents` : "has submitted documents"
- `fee_schedules` 1 -> many `payment_assessments` : "defines fees for"
- `membership_applications` 1 -> many `payment_assessments` : "generates"
- `renewal_requests` 1 -> many `payment_assessments` : "generates"
- `payment_assessments` 1 -> many `payments` : "is settled by"
- `membership_applications` 1 -> many `membership_ledgers` : "creates ledger entry for"
- `renewal_requests` 1 -> many `membership_ledgers` : "creates ledger entry for"
- `membership_ledgers` 1 -> many `mortuary_claims` : "supports"

### Office Action References
- `users` 1 -> many `membership_applications` via `reviewed_by` : "reviews"
- `users` 1 -> many `renewal_requests` via `reviewed_by` : "reviews"
- `users` 1 -> many `reactivation_requests` via `reviewed_by` : "reviews"
- `users` 1 -> many `farmer_documents` via `received_by` : "receives"
- `users` 1 -> many `farmer_documents` via `verified_by` : "verifies"
- `users` 1 -> many `payments` via `verified_by` : "verifies"
- `users` 1 -> many `mortuary_claims` via `filed_by` : "files"
- `users` 1 -> many `mortuary_claims` via `approved_by` : "approves"
- `users` 1 -> many `mortuary_claims` via `released_by` : "releases"

### Communication
- `users` 1 -> many `advisories` via `published_by` : "publishes"
- `barangays` 1 -> many `advisories` : "is targeted by"
- `associations` 1 -> many `advisories` : "is targeted by"
- `advisories` 1 -> many `advisory_attachments` : "has attachments"
- `advisories` 1 -> many `advisory_member_type` : "targets member types through"
- `member_types` 1 -> many `advisory_member_type` : "is targeted through"
- `farmers` 1 -> many `queries` : "submits"
- `queries` 1 -> many `query_images` : "has images"
- `queries` 1 -> many `query_responses` : "receives responses"
- `users` 1 -> many `query_responses` via `responded_by` : "responds to"
- `query_responses` 1 -> many `query_response_attachments` : "has attachments"

## Draw.io Layout Suggestion

Place the tables in this order:

### Left side: core registry
- `barangays`
- `associations`
- `member_types`
- `farmers`
- `farmer_profiles`
- `users`
- `office_profiles`

### Center: membership lifecycle
- `membership_applications`
- `renewal_requests`
- `reactivation_requests`
- `document_requirements`
- `farmer_documents`
- `fee_schedules`
- `payment_assessments`
- `payments`
- `membership_ledgers`
- `mortuary_claims`

### Right side: communication
- `advisories`
- `advisory_attachments`
- `advisory_member_type`
- `queries`
- `query_images`
- `query_responses`
- `query_response_attachments`

## Presentation Notes

You can explain the normalization like this:

- `users` stores only authentication and account data
- `office_profiles` stores office staff identity and employment details
- `farmers` stores only registry and membership-tracking data
- `farmer_profiles` stores farmer identity and contact details
- membership applications, renewals, and reactivations are business transactions attached to a farmer
- documents, assessments, payments, ledgers, and claims are attached to the transaction that creates or governs them
- redundant direct farmer references are removed from child tables when the farmer can already be derived from the parent transaction
- circular references are avoided so the ERD remains clean, readable, and normalized
