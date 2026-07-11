# Existing System Context-Level DFD

This context-level DFD models the existing manual agriculture management, mortuary assistance, and membership renewal process currently performed by the Municipal Agriculture Office.

Related diagrams:
- [Existing Level-1 DFD](/c:/xampp/htdocs/new_anitech/docs/existing-level1-dfd.md)
- [Existing Level-2 DFD](/c:/xampp/htdocs/new_anitech/docs/existing-level2-dfd.md)

## Existing DFD

```mermaid
flowchart LR
    farmer[FARMER]
    staff[STAFF]
    admin[ADMIN]

    system["0<br/>Existing Manual Agriculture Management, Mortuary Assistance, and Renewal Process"]

    farmer -->|Membership Application Details| system
    farmer -->|Renewal Request Details| system
    farmer -->|Inquiry Details| system
    system -->|Application Confirmation| farmer
    system -->|Renewal Status| farmer
    system -->|Query Response| farmer

    staff -->|Application Review Details| system
    staff -->|Farmer Record Information| system
    staff -->|Renewal Verification Details| system
    staff -->|Mortuary Assistance Details| system
    staff -->|Mortuary Verification Details| system
    staff -->|Query Response Details| system

    system -->|Application Records| staff
    system -->|Farmer Record Details| staff
    system -->|Mortuary Assistance Records| staff
    system -->|Query Response Details| staff

    admin -->|Report Request Details| system

    system -->|Report Information| admin
```

## Main External Entities

- `FARMER` submits paper-based applications, renewal requests, and inquiries to the office.
- `STAFF` reviews documents, updates farmer records, verifies renewals, handles mortuary assistance processing, and prepares responses to farmer concerns.
- `ADMIN` requests consolidated reports from the manual records kept by the office.

## Suggested Diagram Title

`Context-Level Data Flow Diagram of the Existing Manual Agriculture Management, Mortuary Assistance, and Renewal Process`
