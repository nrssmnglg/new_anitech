# Proposed System Level-2 DFD

This level-2 DFD explodes the major level-1 processes of the proposed web-based agriculture management system into more detailed sub-processes.

Data store numbering is kept consistent with the level-1 DFD for shared stores, while additional level-2-only stores are assigned new data store identifiers.

Related diagrams:
- [Proposed Level-0 DFD](/c:/xampp/htdocs/new_anitech/docs/proposed-context-dfd.md)
- [Proposed Level-1 DFD](/c:/xampp/htdocs/new_anitech/docs/proposed-level1-dfd.md)

## Proposed Level-2 DFD for Process 1: Handle Digital Membership Applications

```mermaid
flowchart LR
    farmer[Farmer]
    staff[Staff]
    d1[(D2 membership_applications)]
    d2[(D7 farmer_documents)]

    p11["1.1 Receive Online Application Data"]
    p12["1.2 Validate Submitted Requirements"]
    p13["1.3 Review Application and Documents"]
    p14["1.4 Record Application Status"]

    farmer -->|Membership Application Data| p11
    farmer -->|Supporting Documents Data| p11
    p11 -->|Submitted Application Information| d1
    p11 -->|Submitted Document Information| d2
    d1 -->|Application Information| p12
    d2 -->|Document Information| p12
    p12 -->|Validated Application Details| d1
    p12 -->|Validated Document Details| d2
    d1 -->|Validated Application Details| p13
    d2 -->|Validated Document Details| p13
    staff -->|Application Review Data| p13
    p13 -->|Reviewed Application Result| d1
    p13 -->|Reviewed Document Result| d2
    d1 -->|Reviewed Application Result| p14
    d2 -->|Reviewed Document Result| p14
    p14 -->|Membership Application Records| d1
    p14 -->|Farmer Document Records| d2
    p14 -->|Application Confirmation Details| farmer
    p14 -->|Approved Applicant Data| staff
```

## Proposed Level-2 DFD for Process 2: Manage Farmer Registry and Verification

```mermaid
flowchart LR
    staff[Staff]
    d1[(D1 farmers)]
    d2[(D2 membership_applications)]

    p21["2.1 Receive Approved Applicant Data"]
    p22["2.2 Verify Farmer Identity and Eligibility"]
    p23["2.3 Create or Update Farmer Registry Record"]
    p24["2.4 Release Verified Farmer Record Details"]

    d2 -->|Approved Applicant Data| p21
    p21 -->|Applicant Record for Verification| d1
    d1 -->|Applicant Record for Verification| p22
    staff -->|Farmer Record Updates| p22
    p22 -->|Verified Farmer Information| d1
    d1 -->|Verified Farmer Information| p23
    p23 -->|Farmer Record Data| d1
    d1 -->|Farmer Registry Information| p24
    p24 -->|Farmer Registry Details| staff
```

## Proposed Level-2 DFD for Process 3: Process Renewal and Reactivation Requests

```mermaid
flowchart LR
    farmer[Farmer]
    staff[Staff]
    d1[(D1 farmers)]
    d2[(D3 renewal_requests)]
    d3[(D8 reactivation_requests)]

    p31["3.1 Receive Renewal or Reactivation Request"]
    p32["3.2 Check Membership Record and Eligibility"]
    p33["3.3 Evaluate Renewal or Reactivation Status"]
    p34["3.4 Record Request Outcome"]

    farmer -->|Renewal and Reactivation Request Data| p31
    p31 -->|Renewal Request Information| d2
    p31 -->|Reactivation Request Information| d3
    d2 -->|Renewal Request Information| p32
    d3 -->|Reactivation Request Information| p32
    d1 -->|Farmer Membership Information| p32
    staff -->|Renewal Verification Data| p32
    p32 -->|Verified Renewal Details| d2
    p32 -->|Verified Reactivation Details| d3
    d2 -->|Verified Renewal Details| p33
    d3 -->|Verified Reactivation Details| p33
    p33 -->|Renewal Assessment Result| d2
    p33 -->|Reactivation Assessment Result| d3
    d2 -->|Renewal Assessment Result| p34
    d3 -->|Reactivation Assessment Result| p34
    p34 -->|Renewal Request Records| d2
    p34 -->|Reactivation Request Records| d3
    p34 -->|Renewal Status Details| farmer
    p34 -->|Payment Assessment Data| staff
```

## Proposed Level-2 DFD for Process 4: Manage Queries, Advisories, and Notifications

```mermaid
flowchart LR
    farmer[Farmer]
    staff[Staff]
    d1[(D4 queries)]
    d2[(D9 query_responses)]
    d3[(D10 advisories)]
    d4[(D11 notifications)]

    p41["4.1 Receive Inquiry or Advisory Input"]
    p42["4.2 Review Communication Details"]
    p43["4.3 Prepare Response or Advisory Notice"]
    p44["4.4 Record and Release Communication"]

    farmer -->|Queries and Inquiry Data| p41
    staff -->|Advisory Publication Data| p41
    p41 -->|Query Information| d1
    p41 -->|Advisory Information| d3
    d1 -->|Query Information| p42
    d3 -->|Advisory Information| p42
    staff -->|Query Response Data| p42
    p42 -->|Reviewed Query Details| d1
    p42 -->|Reviewed Advisory Details| d3
    d1 -->|Reviewed Query Details| p43
    d3 -->|Reviewed Advisory Details| p43
    p43 -->|Query Response Details| d2
    p43 -->|Notification Details| d4
    d2 -->|Query Response Details| p44
    d4 -->|Notification Details| p44
    p44 -->|Query Records| d1
    p44 -->|Query Response Records| d2
    p44 -->|Advisory Records| d3
    p44 -->|Notification Records| d4
    p44 -->|Query Response Details| farmer
    p44 -->|Submitted Queries and Alerts| staff
```

## Proposed Level-2 DFD for Process 5: Manage Assessments and Payments

```mermaid
flowchart LR
    farmer[Farmer]
    gateway[Payment Gateway]
    d1[(D12 payment_assessments)]
    d2[(D5 payments)]
    d3[(D13 membership_ledgers)]
    d4[(D14 paymongo_webhook_events)]

    p51["5.1 Receive Payment Assessment or Payment Request"]
    p52["5.2 Generate Payment Reference and QR Details"]
    p53["5.3 Verify Gateway Payment Result"]
    p54["5.4 Record Ledger and Payment Confirmation"]

    d1 -->|Payment Assessment Data| p51
    farmer -->|Payment Initiation Data| p51
    p51 -->|Payment Assessment Records| d1
    p51 -->|Payment Request Records| d2
    d1 -->|Payment Assessment Details| p52
    d2 -->|Payment Request Details| p52
    p52 -->|Payment Request Data| gateway
    p52 -->|Payment QR and Reference Details| farmer
    gateway -->|Payment Verification Data| p53
    d2 -->|Pending Payment Information| p53
    d4 -->|Webhook Information| p53
    p53 -->|Verified Payment Result| d2
    p53 -->|Payment Webhook Event Records| d4
    d2 -->|Verified Payment Result| p54
    p54 -->|Payment Records| d2
    p54 -->|Membership Ledger Records| d3
    p54 -->|Payment QR and Confirmation Details| farmer
```

## Proposed Level-2 DFD for Process 6: Process Mortuary Assistance Claims

```mermaid
flowchart LR
    staff[Staff]
    d1[(D1 farmers)]
    d2[(D6 mortuary_claims)]

    p61["6.1 Receive Mortuary Claim Details"]
    p62["6.2 Verify Membership and Claim Eligibility"]
    p63["6.3 Evaluate and Process Claim"]
    p64["6.4 Record Mortuary Claim Result"]

    staff -->|Mortuary Claim Processing Data| p61
    p61 -->|Mortuary Claim Details| d2
    d2 -->|Mortuary Claim Information| p62
    d1 -->|Farmer Membership Information| p62
    p62 -->|Verified Claim Details| d2
    d2 -->|Verified Claim Details| p63
    p63 -->|Processed Claim Result| d2
    d2 -->|Processed Claim Result| p64
    p64 -->|Mortuary Claim Records| d2
    p64 -->|Mortuary Claim Status| staff
```

## Proposed Level-2 DFD for Process 7: Generate Reports and Administrative Records

```mermaid
flowchart LR
    admin[Admin]
    d1[(D1 farmers)]
    d2[(D2 membership_applications)]
    d3[(D3 renewal_requests)]
    d4[(D4 queries)]
    d5[(D5 payments)]
    d6[(D6 mortuary_claims)]
    d7[(D16 audit_logs)]
    d8[(D15 report_exports)]

    p71["7.1 Receive Report or Administration Request"]
    p72["7.2 Retrieve Operational Records"]
    p73["7.3 Consolidate Dashboard and Report Data"]
    p74["7.4 Release Reports and Administrative Records"]

    admin -->|Report Request Data| p71
    admin -->|Audit Trail Request Data| p71
    admin -->|User and Configuration Management Data| p71
    p71 -->|Report and Administration Request Records| d8
    d8 -->|Report and Administration Request Details| p72
    d1 -->|Farmer Record Information| p72
    d2 -->|Application Information| p72
    d3 -->|Renewal Information| p72
    d4 -->|Query Information| p72
    d5 -->|Payment Information| p72
    d6 -->|Mortuary Claim Information| p72
    d7 -->|Audit Trail Information| p72
    p72 -->|Retrieved Operational Data| d8-
    d8 -->|Retrieved Operational Data| p73
    p73 -->|Dashboard and Administrative Output| d8
    d8 -->|Dashboard and Administrative Output| p74
    p74 -->|Report Export Records| d8
    p74 -->|Dashboard, Reports, and Administrative Records| admin
```

## Main Sub-Processes

- `1.1-1.4 Handle Digital Membership Applications` covers receiving online applications, validating requirements, reviewing submissions, and recording application status.
- `2.1-2.4 Manage Farmer Registry and Verification` covers receiving approved applicant data, verifying identity and eligibility, updating registry records, and releasing verified farmer details.
- `3.1-3.4 Process Renewal and Reactivation Requests` covers receiving requests, checking membership eligibility, evaluating request status, and recording the result.
- `4.1-4.4 Manage Queries, Advisories, and Notifications` covers receiving communications, reviewing details, preparing responses or notices, and recording released communications.
- `5.1-5.4 Manage Assessments and Payments` covers receiving assessment or payment requests, generating payment references, verifying gateway results, and recording payment confirmations.
- `6.1-6.4 Process Mortuary Assistance Claims` covers receiving claim details, verifying eligibility, evaluating the claim, and recording the claim result.
- `7.1-7.4 Generate Reports and Administrative Records` covers receiving reporting and audit trail requests, retrieving operational records, consolidating report data, and releasing administrative outputs.

## Suggested Diagram Title

`Level-2 Data Flow Diagram of the Proposed Web-Based Agriculture Management System`
