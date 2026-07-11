$tables = @(
    @{
        Name = "users"
        Purpose = "stores login identity for office staff and linked farmer accounts."
        Fields = @(
            @{ Name = "id"; Type = "bigint"; Constraints = "PK, not null"; Description = "Unique identifier of the user." }
            @{ Name = "farmer_id"; Type = "bigint"; Constraints = "FK -> farmers.id, unique, nullable"; Description = "Reference to the linked farmer record when the user is a farmer account." }
            @{ Name = "name"; Type = "string"; Constraints = "not null"; Description = "Full name of the user." }
            @{ Name = "email"; Type = "string"; Constraints = "unique, not null"; Description = "Email address used for login and communication." }
            @{ Name = "status"; Type = "string"; Constraints = "not null"; Description = "Current account status of the user." }
        )
    }
    @{
        Name = "office_profiles"
        Purpose = "stores the one-to-one extension details of office-side user accounts."
        Fields = @(
            @{ Name = "id"; Type = "bigint"; Constraints = "PK, not null"; Description = "Unique identifier of the office profile." }
            @{ Name = "user_id"; Type = "bigint"; Constraints = "FK -> users.id, unique, not null"; Description = "Reference to the office user account." }
            @{ Name = "employee_id"; Type = "string"; Constraints = "unique, nullable"; Description = "Office employee identifier." }
            @{ Name = "job_title"; Type = "string"; Constraints = "nullable"; Description = "Assigned office job title." }
            @{ Name = "contact_number"; Type = "string"; Constraints = "nullable"; Description = "Office contact number." }
        )
    }
    @{
        Name = "barangays"
        Purpose = "stores the barangay master list."
        Fields = @(
            @{ Name = "id"; Type = "bigint"; Constraints = "PK, not null"; Description = "Unique identifier of the barangay." }
            @{ Name = "name"; Type = "string"; Constraints = "not null"; Description = "Name of the barangay." }
            @{ Name = "code"; Type = "string"; Constraints = "unique, nullable"; Description = "Barangay code used for identification." }
            @{ Name = "status"; Type = "string"; Constraints = "not null"; Description = "Current status of the barangay record." }
        )
    }
    @{
        Name = "associations"
        Purpose = "stores the farmer association master list."
        Fields = @(
            @{ Name = "id"; Type = "bigint"; Constraints = "PK, not null"; Description = "Unique identifier of the association." }
            @{ Name = "barangay_id"; Type = "bigint"; Constraints = "FK -> barangays.id, unique, not null"; Description = "Reference to the barangay served by the association." }
            @{ Name = "name"; Type = "string"; Constraints = "not null"; Description = "Name of the association." }
            @{ Name = "code"; Type = "string"; Constraints = "unique, nullable"; Description = "Association code used for identification." }
            @{ Name = "status"; Type = "string"; Constraints = "not null"; Description = "Current status of the association record." }
        )
        Notes = @(
            "Because barangay_id is unique in the current schema, one barangay maps to at most one association."
        )
    }
    @{
        Name = "member_types"
        Purpose = "stores farmer membership classifications."
        Fields = @(
            @{ Name = "id"; Type = "bigint"; Constraints = "PK, not null"; Description = "Unique identifier of the member type." }
            @{ Name = "code"; Type = "string"; Constraints = "unique, not null"; Description = "Short code of the membership classification." }
            @{ Name = "name"; Type = "string"; Constraints = "not null"; Description = "Name of the membership classification." }
            @{ Name = "is_new_member"; Type = "boolean"; Constraints = "not null"; Description = "Indicates whether the type is for new members." }
            @{ Name = "is_senior"; Type = "boolean"; Constraints = "not null"; Description = "Indicates whether the type is for senior members." }
            @{ Name = "requires_membership_fee"; Type = "boolean"; Constraints = "not null"; Description = "Indicates whether a membership fee is required." }
            @{ Name = "mortuary_eligible"; Type = "boolean"; Constraints = "not null"; Description = "Indicates whether the type is eligible for mortuary benefits." }
        )
    }
    @{
        Name = "farmers"
        Purpose = "stores the main farmer or member registry information."
        Fields = @(
            @{ Name = "id"; Type = "bigint"; Constraints = "PK, not null"; Description = "Unique identifier of the farmer." }
            @{ Name = "farmer_code"; Type = "string"; Constraints = "unique, not null"; Description = "System-generated farmer code." }
            @{ Name = "first_name"; Type = "string"; Constraints = "not null"; Description = "Farmer first name." }
            @{ Name = "middle_name"; Type = "string"; Constraints = "nullable"; Description = "Farmer middle name." }
            @{ Name = "last_name"; Type = "string"; Constraints = "not null"; Description = "Farmer last name." }
            @{ Name = "suffix"; Type = "string"; Constraints = "nullable"; Description = "Farmer name suffix." }
            @{ Name = "sex"; Type = "string"; Constraints = "nullable"; Description = "Farmer sex." }
            @{ Name = "birth_date"; Type = "date"; Constraints = "nullable"; Description = "Farmer date of birth." }
            @{ Name = "civil_status"; Type = "string"; Constraints = "nullable"; Description = "Farmer civil status." }
            @{ Name = "mobile_number"; Type = "string"; Constraints = "nullable"; Description = "Farmer mobile number." }
            @{ Name = "email"; Type = "string"; Constraints = "nullable"; Description = "Farmer email address." }
            @{ Name = "address"; Type = "string"; Constraints = "nullable"; Description = "Farmer address." }
            @{ Name = "barangay_id"; Type = "bigint"; Constraints = "FK -> barangays.id, not null"; Description = "Reference to the farmer barangay." }
            @{ Name = "association_id"; Type = "bigint"; Constraints = "FK -> associations.id, nullable"; Description = "Reference to the farmer association." }
            @{ Name = "member_type_id"; Type = "bigint"; Constraints = "FK -> member_types.id, nullable"; Description = "Reference to the farmer member type." }
            @{ Name = "source_application_id"; Type = "bigint"; Constraints = "FK -> membership_applications.id, nullable"; Description = "Reference to the source membership application." }
            @{ Name = "is_registry_record"; Type = "boolean"; Constraints = "not null"; Description = "Indicates whether the record came from the main registry." }
            @{ Name = "record_origin"; Type = "string"; Constraints = "not null"; Description = "Source origin of the farmer record." }
            @{ Name = "status"; Type = "string"; Constraints = "not null"; Description = "Current farmer record status." }
            @{ Name = "membership_status"; Type = "string"; Constraints = "not null"; Description = "Current membership status of the farmer." }
            @{ Name = "registered_at"; Type = "datetime"; Constraints = "nullable"; Description = "Date and time the farmer was registered." }
            @{ Name = "activated_at"; Type = "datetime"; Constraints = "nullable"; Description = "Date and time the membership was activated." }
            @{ Name = "last_renewal_year"; Type = "year"; Constraints = "nullable"; Description = "Most recent approved renewal year." }
            @{ Name = "inactive_at"; Type = "datetime"; Constraints = "nullable"; Description = "Date and time the farmer became inactive." }
            @{ Name = "inactive_reason"; Type = "string"; Constraints = "nullable"; Description = "Reason for inactive membership status." }
        )
    }
    @{
        Name = "membership_applications"
        Purpose = "stores new membership application transactions."
        Fields = @(
            @{ Name = "id"; Type = "bigint"; Constraints = "PK, not null"; Description = "Unique identifier of the membership application." }
            @{ Name = "farmer_id"; Type = "bigint"; Constraints = "FK -> farmers.id, not null"; Description = "Reference to the applicant farmer." }
            @{ Name = "application_no"; Type = "string"; Constraints = "unique, not null"; Description = "Unique application number." }
            @{ Name = "source"; Type = "string"; Constraints = "not null"; Description = "Origin or channel of the application." }
            @{ Name = "status"; Type = "string"; Constraints = "not null"; Description = "Current application status." }
            @{ Name = "submitted_at"; Type = "datetime"; Constraints = "nullable"; Description = "Date and time the application was submitted." }
            @{ Name = "reviewed_by"; Type = "bigint"; Constraints = "FK -> users.id, nullable"; Description = "Reference to the user who reviewed the application." }
            @{ Name = "reviewed_at"; Type = "datetime"; Constraints = "nullable"; Description = "Date and time the application was reviewed." }
            @{ Name = "approved_at"; Type = "datetime"; Constraints = "nullable"; Description = "Date and time the application was approved." }
            @{ Name = "remarks"; Type = "text"; Constraints = "nullable"; Description = "Review remarks for the application." }
            @{ Name = "rejection_reason"; Type = "string"; Constraints = "nullable"; Description = "Reason for rejecting the application." }
            @{ Name = "rejection_details"; Type = "text"; Constraints = "nullable"; Description = "Additional rejection details." }
        )
    }
    @{
        Name = "renewal_requests"
        Purpose = "stores annual membership renewal transactions."
        Fields = @(
            @{ Name = "id"; Type = "bigint"; Constraints = "PK, not null"; Description = "Unique identifier of the renewal request." }
            @{ Name = "farmer_id"; Type = "bigint"; Constraints = "FK -> farmers.id, not null"; Description = "Reference to the farmer requesting renewal." }
            @{ Name = "year"; Type = "year"; Constraints = "not null, unique with farmer_id"; Description = "Renewal year covered by the request." }
            @{ Name = "source"; Type = "string"; Constraints = "not null"; Description = "Origin or channel of the renewal request." }
            @{ Name = "status"; Type = "string"; Constraints = "not null"; Description = "Current renewal request status." }
            @{ Name = "submitted_at"; Type = "datetime"; Constraints = "nullable"; Description = "Date and time the renewal request was submitted." }
            @{ Name = "reviewed_by"; Type = "bigint"; Constraints = "FK -> users.id, nullable"; Description = "Reference to the user who reviewed the renewal request." }
            @{ Name = "reviewed_at"; Type = "datetime"; Constraints = "nullable"; Description = "Date and time the renewal request was reviewed." }
            @{ Name = "approved_at"; Type = "datetime"; Constraints = "nullable"; Description = "Date and time the renewal request was approved." }
            @{ Name = "is_late"; Type = "boolean"; Constraints = "not null"; Description = "Indicates whether the renewal was filed late." }
            @{ Name = "remarks"; Type = "text"; Constraints = "nullable"; Description = "Review remarks for the renewal request." }
        )
        Notes = @(
            "Unique constraint: (farmer_id, year)."
        )
    }
    @{
        Name = "reactivation_requests"
        Purpose = "stores reactivation requests for inactive members."
        Fields = @(
            @{ Name = "id"; Type = "bigint"; Constraints = "PK, not null"; Description = "Unique identifier of the reactivation request." }
            @{ Name = "farmer_id"; Type = "bigint"; Constraints = "FK -> farmers.id, not null"; Description = "Reference to the farmer requesting reactivation." }
            @{ Name = "status"; Type = "string"; Constraints = "not null"; Description = "Current reactivation request status." }
            @{ Name = "reason"; Type = "text"; Constraints = "nullable"; Description = "Reason for requesting reactivation." }
            @{ Name = "submitted_at"; Type = "datetime"; Constraints = "nullable"; Description = "Date and time the request was submitted." }
            @{ Name = "reviewed_by"; Type = "bigint"; Constraints = "FK -> users.id, nullable"; Description = "Reference to the user who reviewed the reactivation request." }
            @{ Name = "reviewed_at"; Type = "datetime"; Constraints = "nullable"; Description = "Date and time the request was reviewed." }
            @{ Name = "remarks"; Type = "text"; Constraints = "nullable"; Description = "Review remarks for the request." }
        )
    }
    @{
        Name = "document_requirements"
        Purpose = "stores normalized rules for required documents by workflow."
        Fields = @(
            @{ Name = "id"; Type = "bigint"; Constraints = "PK, not null"; Description = "Unique identifier of the document requirement rule." }
            @{ Name = "workflow"; Type = "string"; Constraints = "not null"; Description = "Workflow covered by the requirement." }
            @{ Name = "source"; Type = "string"; Constraints = "nullable"; Description = "Optional source or channel qualifier for the workflow." }
            @{ Name = "document_type"; Type = "string"; Constraints = "not null, unique with workflow and source"; Description = "Type of required document." }
            @{ Name = "is_active"; Type = "boolean"; Constraints = "not null"; Description = "Indicates whether the requirement is active." }
            @{ Name = "sort_order"; Type = "integer"; Constraints = "not null"; Description = "Display order of the requirement." }
        )
        Notes = @(
            "Unique constraint: (workflow, source, document_type)."
        )
    }
    @{
        Name = "farmer_documents"
        Purpose = "stores submitted documents attached to a specific business transaction."
        Fields = @(
            @{ Name = "id"; Type = "bigint"; Constraints = "PK, not null"; Description = "Unique identifier of the submitted document." }
            @{ Name = "membership_application_id"; Type = "bigint"; Constraints = "FK -> membership_applications.id, nullable"; Description = "Reference to the related membership application." }
            @{ Name = "renewal_request_id"; Type = "bigint"; Constraints = "FK -> renewal_requests.id, nullable"; Description = "Reference to the related renewal request." }
            @{ Name = "document_type"; Type = "string"; Constraints = "not null"; Description = "Type of submitted document." }
            @{ Name = "original_name"; Type = "string"; Constraints = "not null"; Description = "Original filename of the submitted document." }
            @{ Name = "is_required"; Type = "boolean"; Constraints = "not null"; Description = "Indicates whether the document is required." }
            @{ Name = "is_received"; Type = "boolean"; Constraints = "not null"; Description = "Indicates whether the document has been received." }
            @{ Name = "received_by"; Type = "bigint"; Constraints = "FK -> users.id, nullable"; Description = "Reference to the user who received the document." }
            @{ Name = "verification_status"; Type = "string"; Constraints = "not null"; Description = "Current verification status of the document." }
            @{ Name = "verified_by"; Type = "bigint"; Constraints = "FK -> users.id, nullable"; Description = "Reference to the user who verified the document." }
            @{ Name = "verified_at"; Type = "datetime"; Constraints = "nullable"; Description = "Date and time the document was verified." }
            @{ Name = "remarks"; Type = "text"; Constraints = "nullable"; Description = "Remarks related to the document." }
        )
        Notes = @(
            "farmer_id is intentionally omitted because the farmer can be derived from the parent transaction."
        )
    }
    @{
        Name = "fee_schedules"
        Purpose = "stores yearly fee configurations."
        Fields = @(
            @{ Name = "id"; Type = "bigint"; Constraints = "PK, not null"; Description = "Unique identifier of the fee schedule." }
            @{ Name = "year"; Type = "year"; Constraints = "unique, not null"; Description = "Year covered by the fee schedule." }
            @{ Name = "membership_fee"; Type = "decimal"; Constraints = "not null"; Description = "Configured membership fee amount." }
            @{ Name = "annual_due"; Type = "decimal"; Constraints = "not null"; Description = "Configured annual due amount." }
            @{ Name = "mortuary_fee"; Type = "decimal"; Constraints = "not null"; Description = "Configured mortuary fee amount." }
            @{ Name = "renewal_deadline"; Type = "date"; Constraints = "not null"; Description = "Deadline for renewal payments." }
            @{ Name = "is_active"; Type = "boolean"; Constraints = "not null"; Description = "Indicates whether the fee schedule is active." }
            @{ Name = "effective_from"; Type = "date"; Constraints = "not null"; Description = "Date when the schedule becomes effective." }
            @{ Name = "effective_to"; Type = "date"; Constraints = "nullable"; Description = "Date when the schedule stops being effective." }
        )
    }
    @{
        Name = "payment_assessments"
        Purpose = "stores computed payment obligations for an application or renewal."
        Fields = @(
            @{ Name = "id"; Type = "bigint"; Constraints = "PK, not null"; Description = "Unique identifier of the payment assessment." }
            @{ Name = "membership_application_id"; Type = "bigint"; Constraints = "FK -> membership_applications.id, nullable"; Description = "Reference to the related membership application." }
            @{ Name = "renewal_request_id"; Type = "bigint"; Constraints = "FK -> renewal_requests.id, nullable"; Description = "Reference to the related renewal request." }
            @{ Name = "fee_schedule_id"; Type = "bigint"; Constraints = "FK -> fee_schedules.id, not null"; Description = "Reference to the fee schedule used for the assessment." }
            @{ Name = "member_type_snapshot"; Type = "string"; Constraints = "not null"; Description = "Stored member type value used during assessment." }
            @{ Name = "membership_fee"; Type = "decimal"; Constraints = "not null"; Description = "Assessed membership fee amount." }
            @{ Name = "annual_due"; Type = "decimal"; Constraints = "not null"; Description = "Assessed annual due amount." }
            @{ Name = "mortuary_fee"; Type = "decimal"; Constraints = "not null"; Description = "Assessed mortuary fee amount." }
            @{ Name = "total_amount_due"; Type = "decimal"; Constraints = "not null"; Description = "Total amount due for the assessment." }
            @{ Name = "due_date"; Type = "date"; Constraints = "nullable"; Description = "Assessment due date." }
            @{ Name = "status"; Type = "string"; Constraints = "not null"; Description = "Current assessment status." }
        )
        Notes = @(
            "farmer_id is intentionally omitted because the farmer can be derived through the source transaction."
        )
    }
    @{
        Name = "payments"
        Purpose = "stores actual payment records under a payment assessment."
        Fields = @(
            @{ Name = "id"; Type = "bigint"; Constraints = "PK, not null"; Description = "Unique identifier of the payment." }
            @{ Name = "payment_assessment_id"; Type = "bigint"; Constraints = "FK -> payment_assessments.id, not null"; Description = "Reference to the related payment assessment." }
            @{ Name = "payment_method"; Type = "string"; Constraints = "not null"; Description = "Method used for the payment." }
            @{ Name = "reference_no"; Type = "string"; Constraints = "nullable"; Description = "Optional reference number of the payment transaction." }
            @{ Name = "amount_paid"; Type = "decimal"; Constraints = "not null"; Description = "Actual amount paid." }
            @{ Name = "paid_at"; Type = "datetime"; Constraints = "not null"; Description = "Date and time the payment was made." }
            @{ Name = "verified_by"; Type = "bigint"; Constraints = "FK -> users.id, nullable"; Description = "Reference to the user who verified the payment." }
            @{ Name = "verified_at"; Type = "datetime"; Constraints = "nullable"; Description = "Date and time the payment was verified." }
            @{ Name = "status"; Type = "string"; Constraints = "not null"; Description = "Current payment status." }
            @{ Name = "receipt_no"; Type = "string"; Constraints = "nullable"; Description = "Official receipt number of the payment." }
        )
    }
    @{
        Name = "membership_ledgers"
        Purpose = "stores posted yearly membership results from approved applications or renewals."
        Fields = @(
            @{ Name = "id"; Type = "bigint"; Constraints = "PK, not null"; Description = "Unique identifier of the membership ledger entry." }
            @{ Name = "year"; Type = "year"; Constraints = "not null"; Description = "Membership year covered by the ledger entry." }
            @{ Name = "membership_application_id"; Type = "bigint"; Constraints = "FK -> membership_applications.id, nullable"; Description = "Reference to the approved membership application." }
            @{ Name = "renewal_request_id"; Type = "bigint"; Constraints = "FK -> renewal_requests.id, nullable"; Description = "Reference to the approved renewal request." }
            @{ Name = "member_type_snapshot"; Type = "string"; Constraints = "not null"; Description = "Stored member type value used for the posted ledger." }
            @{ Name = "membership_fee"; Type = "decimal"; Constraints = "not null"; Description = "Posted membership fee amount." }
            @{ Name = "annual_due"; Type = "decimal"; Constraints = "not null"; Description = "Posted annual due amount." }
            @{ Name = "mortuary_fee"; Type = "decimal"; Constraints = "not null"; Description = "Posted mortuary fee amount." }
            @{ Name = "total_amount_due"; Type = "decimal"; Constraints = "not null"; Description = "Posted total amount due." }
            @{ Name = "amount_paid"; Type = "decimal"; Constraints = "not null"; Description = "Total amount paid for the ledger entry." }
            @{ Name = "paid_at"; Type = "datetime"; Constraints = "nullable"; Description = "Date and time payment was completed." }
            @{ Name = "payment_status"; Type = "string"; Constraints = "not null"; Description = "Payment status of the ledger entry." }
            @{ Name = "mortuary_eligible"; Type = "boolean"; Constraints = "not null"; Description = "Indicates whether the ledger is eligible for mortuary benefit claims." }
            @{ Name = "status"; Type = "string"; Constraints = "not null"; Description = "Current ledger status." }
        )
        Notes = @(
            "farmer_id is intentionally omitted because the farmer can be derived through the source transaction."
        )
    }
    @{
        Name = "mortuary_claims"
        Purpose = "stores benefit claims based on eligible membership ledger entries."
        Fields = @(
            @{ Name = "id"; Type = "bigint"; Constraints = "PK, not null"; Description = "Unique identifier of the mortuary claim." }
            @{ Name = "membership_ledger_id"; Type = "bigint"; Constraints = "FK -> membership_ledgers.id, not null"; Description = "Reference to the eligible membership ledger entry." }
            @{ Name = "claim_reference"; Type = "string"; Constraints = "unique, not null"; Description = "Unique claim reference number." }
            @{ Name = "claim_amount"; Type = "decimal"; Constraints = "not null"; Description = "Amount claimed under the mortuary benefit." }
            @{ Name = "claim_date"; Type = "date"; Constraints = "not null"; Description = "Date the claim was filed." }
            @{ Name = "claimer_name"; Type = "string"; Constraints = "nullable"; Description = "Name of the person claiming the benefit." }
            @{ Name = "claimer_relationship"; Type = "string"; Constraints = "nullable"; Description = "Relationship of the claimer to the member." }
            @{ Name = "claimer_contact_number"; Type = "string"; Constraints = "nullable"; Description = "Contact number of the claimer." }
            @{ Name = "claimer_address"; Type = "string"; Constraints = "nullable"; Description = "Address of the claimer." }
            @{ Name = "claimer_valid_id_received"; Type = "boolean"; Constraints = "not null"; Description = "Indicates whether the claimer valid ID was received." }
            @{ Name = "proof_of_relationship_received"; Type = "boolean"; Constraints = "not null"; Description = "Indicates whether proof of relationship was received." }
            @{ Name = "death_certificate_received"; Type = "boolean"; Constraints = "not null"; Description = "Indicates whether the death certificate was received." }
            @{ Name = "status"; Type = "string"; Constraints = "not null"; Description = "Current claim status." }
            @{ Name = "filed_by"; Type = "bigint"; Constraints = "FK -> users.id, nullable"; Description = "Reference to the user who filed the claim record." }
            @{ Name = "approved_by"; Type = "bigint"; Constraints = "FK -> users.id, nullable"; Description = "Reference to the user who approved the claim." }
            @{ Name = "released_by"; Type = "bigint"; Constraints = "FK -> users.id, nullable"; Description = "Reference to the user who released the claim." }
            @{ Name = "remarks"; Type = "text"; Constraints = "nullable"; Description = "Additional remarks about the claim." }
        )
        Notes = @(
            "farmer_id is intentionally omitted because the farmer and eligibility are derived from membership_ledger_id."
        )
    }
    @{
        Name = "advisories"
        Purpose = "stores announcements published to specific audiences."
        Fields = @(
            @{ Name = "id"; Type = "bigint"; Constraints = "PK, not null"; Description = "Unique identifier of the advisory." }
            @{ Name = "title"; Type = "string"; Constraints = "not null"; Description = "Title of the advisory." }
            @{ Name = "slug"; Type = "string"; Constraints = "unique, not null"; Description = "Unique slug used for the advisory." }
            @{ Name = "status"; Type = "string"; Constraints = "not null"; Description = "Current advisory publication status." }
            @{ Name = "audience_type"; Type = "string"; Constraints = "not null"; Description = "Type of target audience for the advisory." }
            @{ Name = "barangay_id"; Type = "bigint"; Constraints = "FK -> barangays.id, nullable"; Description = "Reference to the targeted barangay." }
            @{ Name = "association_id"; Type = "bigint"; Constraints = "FK -> associations.id, nullable"; Description = "Reference to the targeted association." }
            @{ Name = "published_by"; Type = "bigint"; Constraints = "FK -> users.id, nullable"; Description = "Reference to the user who published the advisory." }
            @{ Name = "published_at"; Type = "datetime"; Constraints = "nullable"; Description = "Date and time the advisory was published." }
        )
    }
    @{
        Name = "advisory_attachments"
        Purpose = "stores files attached to advisories."
        Fields = @(
            @{ Name = "id"; Type = "bigint"; Constraints = "PK, not null"; Description = "Unique identifier of the advisory attachment." }
            @{ Name = "advisory_id"; Type = "bigint"; Constraints = "FK -> advisories.id, not null"; Description = "Reference to the parent advisory." }
            @{ Name = "original_name"; Type = "string"; Constraints = "not null"; Description = "Original filename of the advisory attachment." }
        )
    }
    @{
        Name = "advisory_member_type"
        Purpose = "stores the bridge records for advisory targeting by member type."
        Fields = @(
            @{ Name = "id"; Type = "bigint"; Constraints = "PK, not null"; Description = "Unique identifier of the bridge record." }
            @{ Name = "advisory_id"; Type = "bigint"; Constraints = "FK -> advisories.id, not null"; Description = "Reference to the advisory." }
            @{ Name = "member_type_id"; Type = "bigint"; Constraints = "FK -> member_types.id, not null, unique with advisory_id"; Description = "Reference to the targeted member type." }
        )
        Notes = @(
            "Unique constraint: (advisory_id, member_type_id)."
        )
    }
    @{
        Name = "queries"
        Purpose = "stores farmer-submitted concerns or inquiries."
        Fields = @(
            @{ Name = "id"; Type = "bigint"; Constraints = "PK, not null"; Description = "Unique identifier of the query." }
            @{ Name = "farmer_id"; Type = "bigint"; Constraints = "FK -> farmers.id, not null"; Description = "Reference to the farmer who submitted the query." }
            @{ Name = "subject"; Type = "string"; Constraints = "not null"; Description = "Subject of the query." }
            @{ Name = "status"; Type = "string"; Constraints = "not null"; Description = "Current query status." }
            @{ Name = "archived_at"; Type = "datetime"; Constraints = "nullable"; Description = "Date and time the query was archived." }
        )
    }
    @{
        Name = "query_images"
        Purpose = "stores images attached to farmer queries."
        Fields = @(
            @{ Name = "id"; Type = "bigint"; Constraints = "PK, not null"; Description = "Unique identifier of the query image." }
            @{ Name = "query_id"; Type = "bigint"; Constraints = "FK -> queries.id, not null"; Description = "Reference to the parent query." }
            @{ Name = "original_name"; Type = "string"; Constraints = "not null"; Description = "Original filename of the attached image." }
        )
    }
    @{
        Name = "query_responses"
        Purpose = "stores office response entries for queries."
        Fields = @(
            @{ Name = "id"; Type = "bigint"; Constraints = "PK, not null"; Description = "Unique identifier of the query response." }
            @{ Name = "query_id"; Type = "bigint"; Constraints = "FK -> queries.id, not null"; Description = "Reference to the parent query." }
            @{ Name = "responded_by"; Type = "bigint"; Constraints = "FK -> users.id, nullable"; Description = "Reference to the user who responded to the query." }
            @{ Name = "message"; Type = "text"; Constraints = "not null"; Description = "Response message content." }
        )
    }
    @{
        Name = "query_response_attachments"
        Purpose = "stores file attachments for query responses."
        Fields = @(
            @{ Name = "id"; Type = "bigint"; Constraints = "PK, not null"; Description = "Unique identifier of the response attachment." }
            @{ Name = "query_response_id"; Type = "bigint"; Constraints = "FK -> query_responses.id, not null"; Description = "Reference to the parent query response." }
            @{ Name = "original_name"; Type = "string"; Constraints = "not null"; Description = "Original filename of the attached file." }
        )
    }
)

$outputPath = "c:\Users\manga\OneDrive\3rd Year\2nd Semester\CAP 101\MANUSCRIPT\Data_Dictionary.docx"
$backupPath = "c:\Users\manga\OneDrive\3rd Year\2nd Semester\CAP 101\MANUSCRIPT\Data_Dictionary.backup.docx"

$wdCollapseEnd = 0
$wdStory = 6
$wdTableGrid = 1
$wdColorBlueGray = 15987699
$wdAutoFitFixed = 0
$wdRowHeightAuto = 0
$wdSaveFormatDocumentDefault = 16

$word = $null
$document = $null

try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $document = $word.Documents.Add()
    $selection = $word.Selection
    $selection.Style = "Normal"
    $selection.Font.Name = "Times New Roman"
    $selection.Font.Size = 12

    $selection.TypeText("The following data dictionary is based on the normalized ERD of the Municipal Agriculture Management Information System. It includes only the core presentation-oriented entities and attributes retained in the normalized design. The table structure uses a consolidated Constraints column to document primary keys, foreign keys, uniqueness, and nullability.")
    $selection.TypeParagraph()
    $selection.TypeParagraph()

    $tableNumber = 1
    foreach ($table in $tables) {
        $selection.Style = "Normal"
        $selection.ParagraphFormat.Alignment = 3
        $selection.Font.Bold = $false
        $selection.TypeText("Table 3.5.$tableNumber presents the $($table.Name) table, which $($table.Purpose)")
        $selection.TypeParagraph()
        $selection.TypeParagraph()

        $selection.ParagraphFormat.Alignment = 1
        $selection.Font.Bold = $true
        $selection.TypeText("Table 3.5.$tableNumber " + ($table.Name -replace "_", " "))
        $selection.TypeParagraph()
        $selection.Font.Bold = $false

        $range = $selection.Range
        $rowCount = $table.Fields.Count + 1
        $wordTable = $document.Tables.Add($range, $rowCount, 4)
        $wordTable.Style = "Table Grid"
        $wordTable.Borders.Enable = 1
        $wordTable.AutoFitBehavior($wdAutoFitFixed)

        $headers = @("Field", "Data Type", "Constraints", "Description")
        for ($col = 1; $col -le 4; $col++) {
            $cell = $wordTable.Cell(1, $col)
            $cell.Range.Text = $headers[$col - 1]
            $cell.Range.Bold = $true
            $cell.Shading.BackgroundPatternColor = $wdColorBlueGray
            $cell.Range.ParagraphFormat.Alignment = 1
        }

        $wordTable.Columns.Item(1).Width = 120
        $wordTable.Columns.Item(2).Width = 100
        $wordTable.Columns.Item(3).Width = 160
        $wordTable.Columns.Item(4).Width = 220

        $row = 2
        foreach ($field in $table.Fields) {
            $wordTable.Cell($row, 1).Range.Text = $field.Name
            $wordTable.Cell($row, 2).Range.Text = $field.Type
            $wordTable.Cell($row, 3).Range.Text = $field.Constraints
            $wordTable.Cell($row, 4).Range.Text = $field.Description
            $wordTable.Cell($row, 2).Range.ParagraphFormat.Alignment = 1
            $row++
        }

        $selection.SetRange($wordTable.Range.End, $wordTable.Range.End)
        $selection.Collapse($wdCollapseEnd)
        $selection.TypeParagraph()

        if ($table.ContainsKey("Notes")) {
            foreach ($note in $table.Notes) {
                $selection.ParagraphFormat.Alignment = 3
                $selection.Font.Italic = $true
                $selection.TypeText("Note: $note")
                $selection.TypeParagraph()
                $selection.Font.Italic = $false
            }
        }

        $selection.TypeParagraph()
        $tableNumber++
    }

    $selection.ParagraphFormat.Alignment = 3
    $selection.Font.Italic = $false
    $selection.TypeText("Overall, this data dictionary serves as the normalized reference for the project database by defining the retained entities, their key attributes, and their constraints in a concise presentation-oriented form.")

    if (Test-Path $outputPath) {
        Copy-Item -LiteralPath $outputPath -Destination $backupPath -Force
    }

    $document.SaveAs([ref]$outputPath, [ref]$wdSaveFormatDocumentDefault)
}
finally {
    if ($document -ne $null) {
        $document.Close()
    }
    if ($word -ne $null) {
        $word.Quit()
    }
    [System.Runtime.Interopservices.Marshal]::ReleaseComObject($selection) | Out-Null
    if ($wordTable -ne $null) {
        [System.Runtime.Interopservices.Marshal]::ReleaseComObject($wordTable) | Out-Null
    }
    if ($document -ne $null) {
        [System.Runtime.Interopservices.Marshal]::ReleaseComObject($document) | Out-Null
    }
    if ($word -ne $null) {
        [System.Runtime.Interopservices.Marshal]::ReleaseComObject($word) | Out-Null
    }
    [GC]::Collect()
    [GC]::WaitForPendingFinalizers()
}
