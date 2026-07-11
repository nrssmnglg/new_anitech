# Existing System Level-2 DFD

This level-2 DFD explodes the major level-1 processes of the existing manual agriculture management process into more detailed sub-processes based on the current office workflow, including manual handling of membership applications, renewals, mortuary assistance, and reporting.

Related diagrams:
- [Existing Context DFD](/c:/xampp/htdocs/new_anitech/docs/existing-context-dfd.md)
- [Existing Level-1 DFD](/c:/xampp/htdocs/new_anitech/docs/existing-level1-dfd.md)

## Existing Level-2 DFD for Process 1: Submit Membership Documents

```mermaid
flowchart LR
    farmer[Farmer]
    d2[(D2 Membership Application File Folder)]

    p12["1.1 Submit Application Documents"]
    p13["1.2 File Submitted Documents"]

    farmer -->|Membership Application Details| p12
    p12 -->|Submitted Application Documents| d2
    d2 -->|Submitted Application Documents| p13
    p13 -->|Submitted Application Documents| d2
    p13 -->|Submission Confirmation| farmer
```

## Existing Level-2 DFD for Process 2: Handle Membership Applications

```mermaid
flowchart LR
    staff[Staff]
    d1[(D1 Farmer Records File Cabinet)]
    d2[(D2 Membership Application File Folder)]

    p21["2.1 Retrieve Submitted Application"]
    p22["2.2 Review and Verify Application"]
    p23["2.3 Record Application Result"]

    d2 -->|Submitted Application Information| p21
    d2 -->|Application Information| p22
    staff -->|Application Review Details| p22
    p22 -->|Application Status Details| staff
    d2 -->|Application Result Information| p23
    p23 -->|Application Records| d2
    p23 -->|Reviewed Membership Application Record| d1
```

## Existing Level-2 DFD for Process 3: Manage Farmer Records

```mermaid
flowchart LR
    staff[Staff]
    d1[(D1 Farmer Records File Cabinet)]
    d2[(D2 Membership Application File Folder)]

    p31["3.1 Receive Farmer Record Information"]
    p32["3.2 Update Farmer Record Details"]
    p33["3.3 File Updated Farmer Records"]

    staff -->|Farmer Record Information| p31
    d2 -->|Reviewed Membership Application Record| p31
    d1 -->|Farmer Information| p32
    staff -->|Farmer Record Information| p32
    p32 -->|Farmer Record Details| staff
    d1 -->|Updated Farmer Record Information| p33
    p33 -->|Farmer Record Information| d1
```

## Existing Level-2 DFD for Process 4: Process Membership Renewal

```mermaid
flowchart LR
    staff[Staff]
    d1[(D1 Farmer Records File Cabinet)]
    d3[(D3 Renewal Record Logbook)]

    p41["4.1 Retrieve Farmer Membership Information"]
    p42["4.2 Review and Verify Renewal"]
    p43["4.3 Record Renewal Outcome"]

    d1 -->|Farmer Membership Information| p41
    d1 -->|Membership Information| p42
    staff -->|Renewal Verification Details| p42
    d1 -->|Renewal Outcome Information| p43
    p43 -->|Renewal Records| d3
```

## Existing Level-2 DFD for Process 5: Process Mortuary Assistance

```mermaid
flowchart LR
    staff[Staff]
    d1[(D1 Farmer Records File Cabinet)]
    d4[(D4 Mortuary Assistance Logbook)]

    p51["5.1 Receive Mortuary Assistance Details"]
    p52["5.2 Review and Verify Assistance Eligibility"]
    p53["5.3 Record Mortuary Assistance Outcome"]

    staff -->|Mortuary Assistance Details| p51
    staff -->|Mortuary Assistance Information| p52
    d1 -->|Farmer Membership Information| p52
    staff -->|Mortuary Verification Details| p52
    d1 -->|Mortuary Assistance Result| p53
    p53 -->|Mortuary Assistance Records| d4
```

## Existing Level-2 DFD for Process 6: Address Farmer Queries

```mermaid
flowchart LR
    farmer[Farmer]
    staff[Staff]
    d5[(D5 Query Logbook)]

    p61["6.1 Receive Inquiry Details"]
    p63["6.2 Record and Release Query Response"]

    farmer -->|Inquiry Details| p61
    p61 -->|Query Information| d5
    d5 -->|Query Information| p63
    staff -->|Query Response Details| p63
    p63 -->|Query Information| d5
    p63 -->|Query Response| farmer
```

## Existing Level-2 DFD for Process 7: Generate Reports and Records

```mermaid
flowchart LR
    admin[Admin]
    d1[(D1 Farmer Records File Cabinet)]
    d2[(D2 Membership Application File Folder)]
    d3[(D3 Renewal Record Logbook)]
    d4[(D4 Mortuary Assistance Logbook)]

    p71["7.1 Receive Report Request Details"]
    p72["7.2 Retrieve and Consolidate Record Information"]
    p73["7.3 Release Report Information"]

    admin -->|Report Request Details| p71
    admin -->|Report Request Information| p72
    d1 -->|Farmer Record Information| p72
    d2 -->|Application Information| p72
    d3 -->|Renewal Information| p72
    d4 -->|Mortuary Assistance Information| p72
    p73 -->|Report Information| admin
```

## Main Sub-Processes

- `1.1-1.2 Submit Membership Documents` covers submitting application documents to the office and filing the submitted papers.
- `2.1-2.3 Handle Membership Applications` covers retrieving submitted applications, reviewing and verifying them, recording the result, and storing the reviewed membership application record for farmer record use.
- `3.1-3.3 Manage Farmer Records` covers receiving reviewed application records and other farmer record information, updating farmer details, and filing updated records.
- `4.1-4.3 Process Membership Renewal` covers retrieving membership data, reviewing and verifying the renewal, and recording the outcome.
- `5.1-5.3 Process Mortuary Assistance` covers receiving assistance details, reviewing and verifying eligibility, and recording the result.
- `6.1-6.2 Address Farmer Queries` covers receiving inquiries and recording and releasing the response.
- `7.1-7.3 Generate Reports and Records` covers receiving report requests, retrieving and consolidating records, and releasing the report.

## Suggested Diagram Title

`Level-2 Data Flow Diagram of the Existing Manual Agriculture Management Process`
