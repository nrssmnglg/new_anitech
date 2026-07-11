# Proposed System Level-1 DFD

This simplified level-1 DFD decomposes the proposed web-based agriculture management system into its major processing components using only the main data stores for a cleaner presentation.

## Proposed Simplified Level-1 DFD

```mermaid
flowchart LR
    farmer[Farmer]
    staff[Staff]
    admin[Admin]
    gateway[Payment Gateway]

    p1["1<br/>Handle Digital Membership Applications"]
    p2["2<br/>Manage Farmer Registry and Verification"]
    p3["3<br/>Process Renewal and Reactivation Requests"]
    p4["4<br/>Manage Queries, Advisories, and Notifications"]
    p5["5<br/>Manage Assessments and Payments"]
    p6["6<br/>Process Mortuary Assistance Claims"]
    p7["7<br/>Generate Reports and Administrative Records"]

    d1[(D1 farmers)]
    d2[(D2 membership_applications)]
    d3[(D3 renewal_requests)]
    d4[(D4 queries)]
    d5[(D5 payments)]
    d6[(D6 mortuary_claims)]

    farmer -->|Membership Application Data| p1
    farmer -->|Supporting Documents Data| p1
    p1 -->|Application Confirmation Details| farmer
    p1 -->|Application Records| d2
    d2 -->|Application Information| p1
    staff -->|Application Review Data| p1
    p1 -->|Submitted Application Queue| staff

    staff -->|Farmer Record Updates| p2
    p2 -->|Farmer Registry Details| staff
    p2 -->|Farmer Record Data| d1
    d2 -->|Approved Applicant Data| p2
    d1 -->|Farmer Information| p2

    farmer -->|Renewal and Reactivation Request Data| p3
    p3 -->|Renewal Status Details| farmer
    d1 -->|Farmer Membership Information| p3
    staff -->|Renewal Verification Data| p3
    p3 -->|Renewal and Reactivation Records| d3
    d3 -->|Renewal and Reactivation Information| p3
    farmer -->|Queries and Inquiry Data| p4
    p4 -->|Query Response Details| farmer
    staff -->|Query Response Data| p4
    staff -->|Advisory Publication Data| p4
    p4 -->|Submitted Queries and Alerts| staff
    p4 -->|Query and Notification Records| d4
    d4 -->|Query and Notification Information| p4

    farmer -->|Payment Initiation Data| p5
    p5 -->|Payment QR and Confirmation Details| farmer
    p5 -->|Payment Request Data| gateway
    gateway -->|Payment Verification Data| p5
    d1 -->|Farmer Membership Information| p5
    d3 -->|Renewal Assessment Information| p5
    p5 -->|Payment Records| d5
    d5 -->|Payment Information| p5

    staff -->|Mortuary Claim Processing Data| p6
    d1 -->|Farmer Membership Information| p6
    p6 -->|Mortuary Claim Records| d6
    d6 -->|Mortuary Claim Information| p6
    p6 -->|Mortuary Claim Status| staff

    admin -->|Report Request Data| p7
    admin -->|User and Configuration Management Data| p7
    d1 -->|Farmer Record Information| p7
    d2 -->|Application Information| p7
    d3 -->|Renewal Information| p7
    d4 -->|Query and Notification Information| p7
    d5 -->|Payment Information| p7
    d6 -->|Mortuary Claim Information| p7
    p7 -->|Dashboard, Reports, and Administrative Records| admin
```

## Main Processes

- `1 Handle Digital Membership Applications` receives online applications, supporting documents, and staff review actions.
- `2 Manage Farmer Registry and Verification` maintains the official farmer registry after application approval and staff validation.
- `3 Process Renewal and Reactivation Requests` handles renewal submissions, reactivation requests, and status tracking.
- `4 Manage Queries, Advisories, and Notifications` manages two-way communication between farmers and office staff.
- `5 Manage Assessments and Payments` computes assessments, creates payment requests, and records verified payments.
- `6 Process Mortuary Assistance Claims` records, verifies, and tracks mortuary assistance claims for eligible farmers.
- `7 Generate Reports and Administrative Records` consolidates operational data for dashboards, reports, and office administration.

## Simplification Notes

- Related tables are grouped into broader logical stores to keep the level-1 diagram readable.
- Detailed table-by-table decomposition remains available in [docs/proposed-level2-dfd.md](/c:/xampp/htdocs/new_anitech/docs/proposed-level2-dfd.md).

## Suggested Diagram Title

`Simplified Level-1 Data Flow Diagram of the Proposed Web-Based Agriculture Management System`
