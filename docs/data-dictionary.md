# Data Dictionary for `new_anitech`

This document is based on the normalized ERD in [normalized-erd.md](/abs/path/c:/xampp/htdocs/new_anitech/docs/normalized-erd.md). It presents each conceptual table with its fields, inferred data types, constraints, and descriptions in a presentation-friendly format.

Notes:
- data types are generalized for documentation purposes
- `string` means short text such as `VARCHAR`
- `text` means longer freeform content
- `decimal` is used for monetary values
- `datetime` is used for timestamps with date and time

## Core Registry

Table 3.5.1 presents the users table, which stores the account information of system users. It contains the basic authentication and account status details needed to manage access to the system.

### Table 3.5.1 Users

| Field | Data Type | Constraints | Description |
|---|---|---|---|
| id | bigint | PK, not null | Unique identifier of the user account. |
| email | string | unique, not null | Email address used for login. |
| password | string | not null | Password hash stored for authentication. |
| status | string | not null | Current account status of the user. |

Table 3.5.2 presents the office_profiles table, which stores the personal and employment details of office personnel linked to a user account. It separates staff identity information from the authentication data kept in the users table.

### Table 3.5.2 Office Profiles

| Field | Data Type | Constraints | Description |
|---|---|---|---|
| id | bigint | PK, not null | Unique identifier of the office profile. |
| user_id | bigint | FK -> users.id, unique, not null | Reference to the office-side user account. |
| first_name | string | not null | First name of the office personnel. |
| middle_name | string | nullable | Middle name of the office personnel. |
| last_name | string | not null | Last name of the office personnel. |
| suffix | string | nullable | Name suffix such as Jr. or Sr. |
| employee_id | string | unique, nullable | Employee identifier assigned to the office user. |
| job_title | string | nullable | Position or designation of the office user. |
| contact_number | string | nullable | Contact number of the office user. |

Table 3.5.3 presents the barangays table, which stores the master list of barangays used by the system. It serves as a reference for farmer and association location records.

### Table 3.5.3 Barangays

| Field | Data Type | Constraints | Description |
|---|---|---|---|
| id | bigint | PK, not null | Unique identifier of the barangay. |
| name | string | not null | Official name of the barangay. |
| code | string | unique, nullable | Optional code used to identify the barangay. |
| status | string | not null | Current status of the barangay record. |

Table 3.5.4 presents the associations table, which stores the master list of farmer associations. It links associations to barangays when applicable and supports farmer grouping in the registry.

### Table 3.5.4 Associations

| Field | Data Type | Constraints | Description |
|---|---|---|---|
| id | bigint | PK, not null | Unique identifier of the association. |
| barangay_id | bigint | FK -> barangays.id, nullable | Reference to the barangay where the association belongs. |
| name | string | not null | Official name of the association. |
| code | string | unique, nullable | Optional code used to identify the association. |
| status | string | not null | Current status of the association record. |

Table 3.5.5 presents the member_types table, which stores the membership classifications used for farmers. It defines the membership rules and eligibility flags applied throughout the system.

### Table 3.5.5 Member Types

| Field | Data Type | Constraints | Description |
|---|---|---|---|
| id | bigint | PK, not null | Unique identifier of the member type. |
| code | string | unique, not null | Short code for the membership classification. |
| name | string | not null | Name of the membership classification. |
| is_new_member | boolean | not null | Indicates whether the type is for new members. |
| is_senior | boolean | not null | Indicates whether the type applies to senior members. |
| requires_membership_fee | boolean | not null | Indicates whether a membership fee is required. |
| mortuary_eligible | boolean | not null | Indicates whether members of this type are eligible for mortuary benefits. |

Table 3.5.6 presents the farmers table, which stores the main registry and membership-tracking record for each farmer. It keeps only registry and membership status information, while personal profile details are stored separately.

### Table 3.5.6 Farmers

| Field | Data Type | Constraints | Description |
|---|---|---|---|
| id | bigint | PK, not null | Unique identifier of the farmer record. |
| farmer_code | string | unique, not null | Unique farmer or member code. |
| barangay_id | bigint | FK -> barangays.id, not null | Reference to the farmer's barangay. |
| association_id | bigint | FK -> associations.id, nullable | Reference to the farmer's association. |
| member_type_id | bigint | FK -> member_types.id, nullable | Reference to the farmer's membership type. |
| status | string | not null | Current overall status of the farmer record. |
| membership_status | string | not null | Current membership standing of the farmer. |
| record_origin | string | not null | Source of how the farmer record was created. |
| is_registry_record | boolean | not null | Indicates whether the record belongs to the official registry. |
| registered_at | datetime | nullable | Date and time when the farmer was registered. |
| activated_at | datetime | nullable | Date and time when the membership became active. |
| last_renewal_year | integer | nullable | Most recent year the farmer renewed membership. |
| inactive_at | datetime | nullable | Date and time when the farmer became inactive. |
| inactive_reason | text | nullable | Reason why the farmer became inactive. |

Table 3.5.7 presents the farmer_profiles table, which stores the personal identity and contact details of each farmer. It supports normalization by separating profile data from the main farmer registry record.

### Table 3.5.7 Farmer Profiles

| Field | Data Type | Constraints | Description |
|---|---|---|---|
| id | bigint | PK, not null | Unique identifier of the farmer profile. |
| farmer_id | bigint | FK -> farmers.id, unique, not null | Reference to the farmer registry record. |
| first_name | string | not null | First name of the farmer. |
| middle_name | string | nullable | Middle name of the farmer. |
| last_name | string | not null | Last name of the farmer. |
| suffix | string | nullable | Name suffix such as Jr. or Sr. |
| sex | string | nullable | Sex of the farmer. |
| birth_date | date | nullable | Birth date of the farmer. |
| civil_status | string | nullable | Civil status of the farmer. |
| mobile_number | string | nullable | Mobile contact number of the farmer. |
| email | string | nullable | Email address of the farmer. |
| address | string | nullable | Home address of the farmer. |

## Membership Lifecycle

Table 3.5.8 presents the membership_applications table, which stores new membership application transactions submitted by farmers. It records submission, review, approval, and rejection details for the application process.

### Table 3.5.8 Membership Applications

| Field | Data Type | Constraints | Description |
|---|---|---|---|
| id | bigint | PK, not null | Unique identifier of the membership application. |
| farmer_id | bigint | FK -> farmers.id, not null | Reference to the farmer who submitted the application. |
| application_no | string | unique, not null | Unique application number for the transaction. |
| source | string | not null | Origin of the application submission. |
| status | string | not null | Current processing status of the application. |
| submitted_at | datetime | nullable | Date and time when the application was submitted. |
| reviewed_by | bigint | FK -> users.id, nullable | User who reviewed the application. |
| reviewed_at | datetime | nullable | Date and time when the application was reviewed. |
| approved_at | datetime | nullable | Date and time when the application was approved. |
| remarks | text | nullable | Additional remarks about the application. |
| rejection_reason | string | nullable | Short reason for rejection if the application was denied. |
| rejection_details | text | nullable | Detailed explanation for the rejection. |

Table 3.5.9 presents the renewal_requests table, which stores the annual membership renewal transactions of farmers. It tracks the membership year, review details, approval details, and late renewal status.

### Table 3.5.9 Renewal Requests

| Field | Data Type | Constraints | Description |
|---|---|---|---|
| id | bigint | PK, not null | Unique identifier of the renewal request. |
| farmer_id | bigint | FK -> farmers.id, not null | Reference to the farmer requesting renewal. |
| year | integer | unique with farmer_id, not null | Membership year being renewed. |
| source | string | not null | Origin of the renewal request. |
| status | string | not null | Current processing status of the renewal. |
| submitted_at | datetime | nullable | Date and time when the renewal was submitted. |
| reviewed_by | bigint | FK -> users.id, nullable | User who reviewed the renewal request. |
| reviewed_at | datetime | nullable | Date and time when the renewal was reviewed. |
| approved_at | datetime | nullable | Date and time when the renewal was approved. |
| is_late | boolean | not null | Indicates whether the renewal was submitted late. |
| remarks | text | nullable | Additional remarks about the renewal request. |

Table 3.5.10 presents the reactivation_requests table, which stores requests from inactive farmers who want to restore their membership status. It records the reason for reactivation and the review outcome.

### Table 3.5.10 Reactivation Requests

| Field | Data Type | Constraints | Description |
|---|---|---|---|
| id | bigint | PK, not null | Unique identifier of the reactivation request. |
| farmer_id | bigint | FK -> farmers.id, not null | Reference to the farmer requesting reactivation. |
| status | string | not null | Current processing status of the reactivation request. |
| reason | text | nullable | Reason provided for requesting reactivation. |
| submitted_at | datetime | nullable | Date and time when the request was submitted. |
| reviewed_by | bigint | FK -> users.id, nullable | User who reviewed the request. |
| reviewed_at | datetime | nullable | Date and time when the request was reviewed. |
| remarks | text | nullable | Additional remarks about the reactivation request. |

Table 3.5.11 presents the document_requirements table, which stores the rules for required documents by workflow and submission source. It standardizes the document checklist used in membership-related transactions.

### Table 3.5.11 Document Requirements

| Field | Data Type | Constraints | Description |
|---|---|---|---|
| id | bigint | PK, not null | Unique identifier of the document requirement rule. |
| workflow | string | unique with source and document_type, not null | Workflow where the document is required. |
| source | string | unique with workflow and document_type, nullable | Optional submission source the rule applies to. |
| document_type | string | unique with workflow and source, not null | Type of document being required. |
| is_active | boolean | not null | Indicates whether the rule is currently active. |
| sort_order | integer | not null | Display or processing order of the requirement. |

Table 3.5.12 presents the farmer_documents table, which stores the submitted documents attached to membership applications and renewal requests. It records whether required documents were received and verified by office personnel.

### Table 3.5.12 Farmer Documents

| Field | Data Type | Constraints | Description |
|---|---|---|---|
| id | bigint | PK, not null | Unique identifier of the submitted document record. |
| membership_application_id | bigint | FK -> membership_applications.id, nullable | Reference to the related membership application. |
| renewal_request_id | bigint | FK -> renewal_requests.id, nullable | Reference to the related renewal request. |
| document_type | string | not null | Type of document submitted or required. |
| original_name | string | not null | Original filename of the uploaded document. |
| is_required | boolean | not null | Indicates whether the document is required. |
| is_received | boolean | not null | Indicates whether the document has been received. |
| received_by | bigint | FK -> users.id, nullable | User who marked the document as received. |
| verification_status | string | not null | Current verification status of the document. |
| verified_by | bigint | FK -> users.id, nullable | User who verified the document. |
| verified_at | datetime | nullable | Date and time when the document was verified. |
| remarks | text | nullable | Additional remarks about the document. |

Table 3.5.13 presents the fee_schedules table, which stores the yearly fee configuration used by the system. It defines the membership, annual, and mortuary fee amounts together with the effective dates.

### Table 3.5.13 Fee Schedules

| Field | Data Type | Constraints | Description |
|---|---|---|---|
| id | bigint | PK, not null | Unique identifier of the fee schedule. |
| year | integer | unique, not null | Membership year covered by the fee schedule. |
| membership_fee | decimal | not null | Membership fee amount for the year. |
| annual_due | decimal | not null | Annual due amount for the year. |
| mortuary_fee | decimal | not null | Mortuary fee amount for the year. |
| renewal_deadline | date | not null | Deadline for regular renewal processing. |
| is_active | boolean | not null | Indicates whether the schedule is currently active. |
| effective_from | date | not null | Date when the fee schedule takes effect. |
| effective_to | date | nullable | Date when the fee schedule stops being effective. |

Table 3.5.14 presents the payment_assessments table, which stores the computed payment obligations for membership applications and renewals. It captures the fee schedule used and the total amount due for the transaction.

### Table 3.5.14 Payment Assessments

| Field | Data Type | Constraints | Description |
|---|---|---|---|
| id | bigint | PK, not null | Unique identifier of the payment assessment. |
| membership_application_id | bigint | FK -> membership_applications.id, nullable | Reference to the source membership application. |
| renewal_request_id | bigint | FK -> renewal_requests.id, nullable | Reference to the source renewal request. |
| fee_schedule_id | bigint | FK -> fee_schedules.id, not null | Reference to the fee schedule used for the computation. |
| member_type_snapshot | string | not null | Member type recorded at the time of assessment. |
| membership_fee | decimal | not null | Computed membership fee amount. |
| annual_due | decimal | not null | Computed annual due amount. |
| mortuary_fee | decimal | not null | Computed mortuary fee amount. |
| total_amount_due | decimal | not null | Total amount that must be paid. |
| due_date | date | nullable | Date when payment is due. |
| status | string | not null | Current payment assessment status. |

Table 3.5.15 presents the payments table, which stores the actual payment transactions made under a payment assessment. It records the payment method, amount paid, verification details, and receipt reference.

### Table 3.5.15 Payments

| Field | Data Type | Constraints | Description |
|---|---|---|---|
| id | bigint | PK, not null | Unique identifier of the payment record. |
| payment_assessment_id | bigint | FK -> payment_assessments.id, not null | Reference to the assessment being paid. |
| payment_method | string | not null | Mode or method used for payment. |
| reference_no | string | nullable | External reference number for the payment. |
| amount_paid | decimal | not null | Amount paid in the transaction. |
| paid_at | datetime | not null | Date and time when the payment was made. |
| verified_by | bigint | FK -> users.id, nullable | User who verified the payment. |
| verified_at | datetime | nullable | Date and time when the payment was verified. |
| status | string | not null | Current status of the payment. |
| receipt_no | string | nullable | Receipt number issued for the payment. |

Table 3.5.16 presents the membership_ledgers table, which stores the posted yearly membership results of approved applications and renewals. It summarizes fees, payment status, and mortuary eligibility for a membership year.

### Table 3.5.16 Membership Ledgers

| Field | Data Type | Constraints | Description |
|---|---|---|---|
| id | bigint | PK, not null | Unique identifier of the membership ledger entry. |
| year | integer | not null | Membership year covered by the ledger entry. |
| membership_application_id | bigint | FK -> membership_applications.id, nullable | Reference to the source membership application. |
| renewal_request_id | bigint | FK -> renewal_requests.id, nullable | Reference to the source renewal request. |
| member_type_snapshot | string | not null | Member type recorded when the ledger was posted. |
| membership_fee | decimal | not null | Membership fee posted to the ledger. |
| annual_due | decimal | not null | Annual due posted to the ledger. |
| mortuary_fee | decimal | not null | Mortuary fee posted to the ledger. |
| total_amount_due | decimal | not null | Total amount due posted to the ledger. |
| amount_paid | decimal | not null | Total amount paid for the ledger entry. |
| paid_at | datetime | nullable | Date and time when the related payment was completed. |
| payment_status | string | not null | Current payment status in the ledger. |
| mortuary_eligible | boolean | not null | Indicates whether the ledger entry qualifies for mortuary benefits. |
| status | string | not null | Current status of the ledger entry. |

Table 3.5.17 presents the mortuary_claims table, which stores the mortuary benefit claims filed against eligible membership ledger entries. It records the claim details, supporting document status, and office action references.

### Table 3.5.17 Mortuary Claims

| Field | Data Type | Constraints | Description |
|---|---|---|---|
| id | bigint | PK, not null | Unique identifier of the mortuary claim. |
| membership_ledger_id | bigint | FK -> membership_ledgers.id, not null | Reference to the eligible membership ledger entry. |
| claim_reference | string | unique, not null | Unique reference number for the claim. |
| claim_amount | decimal | not null | Amount claimed under the mortuary benefit. |
| claim_date | date | not null | Date the claim was filed or recorded. |
| claimer_name | string | nullable | Name of the person claiming the benefit. |
| claimer_relationship | string | nullable | Relationship of the claimer to the deceased member. |
| claimer_contact_number | string | nullable | Contact number of the claimer. |
| claimer_address | string | nullable | Address of the claimer. |
| claimer_valid_id_received | boolean | not null | Indicates whether the claimer's valid ID was received. |
| proof_of_relationship_received | boolean | not null | Indicates whether proof of relationship was received. |
| death_certificate_received | boolean | not null | Indicates whether the death certificate was received. |
| status | string | not null | Current processing status of the claim. |
| filed_by | bigint | FK -> users.id, nullable | User who filed the claim record in the system. |
| approved_by | bigint | FK -> users.id, nullable | User who approved the claim. |
| released_by | bigint | FK -> users.id, nullable | User who released the benefit. |
| remarks | text | nullable | Additional remarks about the claim. |

## Communication

Table 3.5.18 presents the advisories table, which stores announcements published for specific audiences. It supports targeting by barangay, association, and publication status.

### Table 3.5.18 Advisories

| Field | Data Type | Constraints | Description |
|---|---|---|---|
| id | bigint | PK, not null | Unique identifier of the advisory. |
| title | string | not null | Title of the advisory or announcement. |
| slug | string | unique, not null | URL-friendly unique identifier of the advisory. |
| status | string | not null | Current publication status of the advisory. |
| audience_type | string | not null | Target audience category of the advisory. |
| barangay_id | bigint | FK -> barangays.id, nullable | Reference to the targeted barangay when applicable. |
| association_id | bigint | FK -> associations.id, nullable | Reference to the targeted association when applicable. |
| published_by | bigint | FK -> users.id, nullable | User who published the advisory. |
| published_at | datetime | nullable | Date and time when the advisory was published. |

Table 3.5.19 presents the advisory_attachments table, which stores files attached to advisories. It supports the distribution of supplementary documents together with announcements.

### Table 3.5.19 Advisory Attachments

| Field | Data Type | Constraints | Description |
|---|---|---|---|
| id | bigint | PK, not null | Unique identifier of the advisory attachment. |
| advisory_id | bigint | FK -> advisories.id, not null | Reference to the advisory that owns the attachment. |
| original_name | string | not null | Original filename of the attached file. |

Table 3.5.20 presents the advisory_member_type table, which stores the bridge records for advisory targeting by member type. It enables many-to-many filtering between advisories and membership classifications.

### Table 3.5.20 Advisory Member Type

| Field | Data Type | Constraints | Description |
|---|---|---|---|
| id | bigint | PK, not null | Unique identifier of the bridge record. |
| advisory_id | bigint | FK -> advisories.id, unique with member_type_id, not null | Reference to the advisory. |
| member_type_id | bigint | FK -> member_types.id, unique with advisory_id, not null | Reference to the targeted member type. |

Table 3.5.21 presents the queries table, which stores farmer-submitted concerns and inquiries. It tracks the subject, status, and archival state of each query.

### Table 3.5.21 Queries

| Field | Data Type | Constraints | Description |
|---|---|---|---|
| id | bigint | PK, not null | Unique identifier of the farmer query. |
| farmer_id | bigint | FK -> farmers.id, not null | Reference to the farmer who submitted the query. |
| subject | string | not null | Subject or topic of the query. |
| status | string | not null | Current status of the query. |
| archived_at | datetime | nullable | Date and time when the query was archived. |

Table 3.5.22 presents the query_images table, which stores image attachments submitted with farmer queries. It keeps the uploaded file references associated with each query.

### Table 3.5.22 Query Images

| Field | Data Type | Constraints | Description |
|---|---|---|---|
| id | bigint | PK, not null | Unique identifier of the query image. |
| query_id | bigint | FK -> queries.id, not null | Reference to the query that owns the image. |
| original_name | string | not null | Original filename of the uploaded image. |

Table 3.5.23 presents the query_responses table, which stores the responses given by office personnel to farmer queries. It records the responder and the response message for each inquiry.

### Table 3.5.23 Query Responses

| Field | Data Type | Constraints | Description |
|---|---|---|---|
| id | bigint | PK, not null | Unique identifier of the query response. |
| query_id | bigint | FK -> queries.id, not null | Reference to the query being answered. |
| responded_by | bigint | FK -> users.id, nullable | User who responded to the query. |
| message | text | not null | Response message sent for the query. |

Table 3.5.24 presents the query_response_attachments table, which stores files attached to query responses. It supports sending supporting documents together with an office reply.

### Table 3.5.24 Query Response Attachments

| Field | Data Type | Constraints | Description |
|---|---|---|---|
| id | bigint | PK, not null | Unique identifier of the query response attachment. |
| query_response_id | bigint | FK -> query_responses.id, not null | Reference to the query response that owns the attachment. |
| original_name | string | not null | Original filename of the attached file. |

## Key Alignment Notes

- `users` now stores only account-level data and no longer carries farmer identity fields or a direct `farmer_id`.
- `office_profiles` now contains the personal identity fields for office personnel.
- `farmers` now stores only registry and membership-tracking data.
- `farmer_profiles` is added as a separate one-to-one table for farmer identity and contact details.
- Direct source or circular references removed in the normalized ERD are also omitted here.
