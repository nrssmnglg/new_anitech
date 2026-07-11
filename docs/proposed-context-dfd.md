# Proposed System Level-0 DFD

This level-0 (context-level) DFD models the proposed digital agriculture management system implemented in this project.

Related diagram:
- [Proposed Level-1 DFD](/c:/xampp/htdocs/new_anitech/docs/proposed-level1-dfd.md)
- [Proposed Level-2 DFD](/c:/xampp/htdocs/new_anitech/docs/proposed-level2-dfd.md)

## Proposed Level-0 DFD

```mermaid
flowchart LR
    farmer[FARMER]
    admin[ADMIN]
    staff[AGRICULTURE STAFF]
    gateway[PAYMENT GATEWAY]

    system["0<br/>Proposed Web-Based Agriculture Management System in San Carlos City, Pangasinan"]

    farmer -->|Membership Application Data| system
    farmer -->|Account Setup and Login Data| system
    farmer -->|Renewal Request Data| system
    farmer -->|Document Uploads| system
    farmer -->|Payment Initiation Data| system
    farmer -->|Queries and Inquiries Data| system

    system -->|Application Tracking and Registration Status| farmer
    system -->|Renewal Status Information| farmer
    system -->|Payment QR and Confirmation Details| farmer
    system -->|Query Response Details| farmer
    system -->|Advisories and Notifications| farmer

    admin -->|User Management Data| system
    admin -->|Fee Schedule and Location Management Data| system
    admin -->|Report Request Data| system

    system -->|User Account Records| admin
    system -->|Dashboard and Reports Data| admin
    system -->|Registry and Renewal Summaries| admin

    staff -->|Farmer Record Data| system
    staff -->|Document Verification Data| system
    staff -->|Application and Renewal Review Data| system
    staff -->|Advisory Publication Data| system
    staff -->|Query Response Data| system
    staff -->|Mortuary Claim Processing Data| system

    system -->|Pending Applications and Renewals| staff
    system -->|Farmer Registry Information| staff
    system -->|Payment and Verification Status| staff
    system -->|Submitted Queries and Notifications| staff

    system -->|Payment Request and QR Checkout Data| gateway
    gateway -->|Payment Status and Webhook Confirmation Data| system
```

## Main External Entities

- `FARMER` submits applications, renewals, payments, documents, and queries through the farmer portal.
- `ADMIN` manages accounts, configuration data, and report generation.
- `AGRICULTURE STAFF` verifies documents, reviews transactions, updates farmer records, answers queries, and manages advisories and claims.
- `PAYMENT GATEWAY` processes online payment requests and returns payment confirmation data to the system.

## Suggested Diagram Title

`Level-0 Data Flow Diagram of the Proposed Web-Based Agriculture Management System`
