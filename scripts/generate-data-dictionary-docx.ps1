$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
$inputPath = Join-Path $root 'docs\data-dictionary.md'
$outputPath = Join-Path $root 'docs\data-dictionary.docx'
$tempDir = Join-Path $root 'tmp\data-dictionary-docx'

if (Test-Path $tempDir) {
    Remove-Item -LiteralPath $tempDir -Recurse -Force
}

New-Item -ItemType Directory -Path $tempDir | Out-Null
New-Item -ItemType Directory -Path (Join-Path $tempDir '_rels') | Out-Null
New-Item -ItemType Directory -Path (Join-Path $tempDir 'word') | Out-Null
New-Item -ItemType Directory -Path (Join-Path $tempDir 'word\_rels') | Out-Null

$md = Get-Content -Raw $inputPath
$lines = $md -split "`r?`n"

$tablePurposes = @{
    'Users' = 'stores the account information of system users such as administrators, staff, and farmer-linked users'
    'Office Profiles' = 'stores the office-side profile details of system users assigned to administrative or staff functions'
    'Barangays' = 'stores the master list of barangays used for organizing farmer records and location-based transactions'
    'Associations' = 'stores the master list of farmer associations and their linked barangays'
    'Member Types' = 'stores the membership classifications used to categorize farmers based on eligibility and fee rules'
    'Farmers' = 'stores the main registry records of farmers and members in the system'
    'Membership Applications' = 'stores new membership application transactions submitted by farmers'
    'Renewal Requests' = 'stores annual membership renewal transactions filed by existing farmers'
    'Reactivation Requests' = 'stores requests from inactive members who want to restore their membership status'
    'Document Requirements' = 'stores the normalized document requirement rules for each workflow and submission source'
    'Farmer Documents' = 'stores document submissions attached to membership and renewal transactions'
    'Fee Schedules' = 'stores the yearly fee configuration used for membership processing and payment computation'
    'Payment Assessments' = 'stores the computed payment obligations generated from membership applications and renewal requests'
    'Payments' = 'stores the actual payment transactions made against assessed obligations'
    'Membership Ledgers' = 'stores the posted yearly membership records resulting from approved applications and renewals'
    'Mortuary Claims' = 'stores benefit claims filed against eligible membership ledger records'
    'Advisories' = 'stores announcements and notices published for specific target audiences'
    'Advisory Attachments' = 'stores files attached to published advisories'
    'Advisory Member Type' = 'stores the bridge records used to target advisories to specific member types'
    'Queries' = 'stores concerns, questions, or inquiries submitted by farmers'
    'Query Images' = 'stores image files attached to farmer queries'
    'Query Responses' = 'stores office responses to farmer-submitted queries'
    'Query Response Attachments' = 'stores files attached to query responses'
}

$tableExtra = @{
    'Users' = 'It contains basic identification, authentication, and account status details, while the farmer_id field links a user to a corresponding farmer record when applicable. This table is essential for managing access to the system and supporting user authentication and authorization processes.'
    'Office Profiles' = 'It contains employee identification, job title, and contact details that extend the core user account through the user_id field. This table is essential for separating office profile information from login credentials while preserving a one-to-one relationship with office users.'
    'Barangays' = 'It contains the barangay name, optional code, and record status needed for maintaining a clean location reference. This table is essential for classifying farmers, associations, and advisories according to geographic coverage within the system.'
    'Associations' = 'It contains the association name, optional code, and status, while the barangay_id field links the association to its corresponding barangay. This table is essential for organizing farmers into local groups and supporting association-based reporting and targeting.'
    'Member Types' = 'It contains the membership code, name, and rule-based flags that determine whether a member is new, senior, fee-paying, or mortuary-eligible. This table is essential for applying consistent classification and business rules across membership transactions.'
    'Farmers' = 'It contains personal, contact, location, classification, and membership status details, while foreign keys connect each farmer to the proper barangay, association, member type, and source application when applicable. This table is essential as the central member registry that supports transactions, payments, communication, and reporting.'
    'Membership Applications' = 'It contains the application number, source, review status, dates, and decision remarks, while the farmer_id field links the transaction to the applicant. This table is essential for tracking the lifecycle of new membership requests from submission to approval or rejection.'
    'Renewal Requests' = 'It contains the renewal year, source, status, review details, and lateness indicator, while the farmer_id field links the request to the renewing farmer. This table is essential for managing annual membership continuation and enforcing one renewal record per farmer per year.'
    'Reactivation Requests' = 'It contains the request status, reason, review details, and remarks, while the farmer_id field links the request to the inactive farmer seeking reactivation. This table is essential for documenting and processing the restoration of inactive memberships.'
    'Document Requirements' = 'It contains the workflow, optional source, document type, activation flag, and sort order used to define requirement rules. This table is essential for standardizing which documents must be submitted for each business process in the system.'
    'Farmer Documents' = 'It contains document type, file name, receipt indicators, verification details, and remarks, while parent transaction fields link each document to either a membership application or a renewal request. This table is essential for monitoring compliance with document submission and verification requirements.'
    'Fee Schedules' = 'It contains the applicable year, fee amounts, renewal deadline, activity flag, and effectivity dates used by the system. This table is essential for ensuring that payment computations follow the correct yearly configuration.'
    'Payment Assessments' = 'It contains the fee schedule reference, member type snapshot, individual fee components, total amount due, due date, and status, while source transaction fields connect the assessment to the originating application or renewal. This table is essential for formalizing the amount that a farmer must pay before membership processing is completed.'
    'Payments' = 'It contains the payment method, reference number, amount paid, payment date, verification details, receipt number, and status, while the payment_assessment_id field links the payment to its corresponding assessment. This table is essential for recording and validating financial transactions in the system.'
    'Membership Ledgers' = 'It contains the membership year, source transaction reference, member type snapshot, fee totals, payment totals, mortuary eligibility flag, and status. This table is essential for maintaining the official yearly membership record that summarizes approved and posted membership results.'
    'Mortuary Claims' = 'It contains the claim reference, amount, claim date, claimer details, documentary receipt flags, processing users, and remarks, while the membership_ledger_id field links the claim to an eligible ledger entry. This table is essential for managing benefit claims and documenting their processing history.'
    'Advisories' = 'It contains the title, slug, publication status, audience type, target location references, and publication details. This table is essential for distributing official announcements to the appropriate farmers, barangays, associations, or other target groups.'
    'Advisory Attachments' = 'It contains the original filename of each attached file, while the advisory_id field links the attachment to its parent advisory. This table is essential for storing supporting documents and media related to announcements.'
    'Advisory Member Type' = 'It contains the advisory_id and member_type_id fields that connect advisories to their targeted membership classifications. This table is essential for implementing a normalized many-to-many relationship between advisories and member types.'
    'Queries' = 'It contains the subject, status, and archive details of each concern, while the farmer_id field links the query to the submitting farmer. This table is essential for tracking farmer communication and support-related interactions.'
    'Query Images' = 'It contains the original filename of each uploaded image, while the query_id field links the image to its corresponding query. This table is essential for preserving visual evidence or supporting material attached to farmer concerns.'
    'Query Responses' = 'It contains the response message and the responding user reference, while the query_id field links the response to the farmer query being answered. This table is essential for documenting how office personnel address and resolve farmer inquiries.'
    'Query Response Attachments' = 'It contains the original filename of each attached file, while the query_response_id field links the file to its corresponding response entry. This table is essential for storing supporting documents sent together with office responses.'
}

function Escape-Xml {
    param([string]$Text)
    if ($null -eq $Text) { return '' }
    return [System.Security.SecurityElement]::Escape($Text)
}

function New-ParagraphXml {
    param(
        [string]$Text,
        [switch]$Bold,
        [switch]$Center
    )

    $escaped = Escape-Xml $Text
    $pPr = ''
    if ($Center) {
        $pPr = '<w:pPr><w:jc w:val="center"/></w:pPr>'
    }
    $rPr = ''
    if ($Bold) {
        $rPr = '<w:rPr><w:b/></w:rPr>'
    }
    return "<w:p>${pPr}<w:r>${rPr}<w:t xml:space=`"preserve`">$escaped</w:t></w:r></w:p>"
}

function New-TableCellXml {
    param(
        [string]$Text,
        [int]$Width = 2200,
        [switch]$Bold
    )

    $escaped = Escape-Xml $Text
    $rPr = ''
    if ($Bold) {
        $rPr = '<w:rPr><w:b/></w:rPr>'
    }
    return @"
<w:tc>
  <w:tcPr>
    <w:tcW w:w="$Width" w:type="dxa"/>
  </w:tcPr>
  <w:p>
    <w:r>$rPr<w:t xml:space="preserve">$escaped</w:t></w:r>
  </w:p>
</w:tc>
"@
}

function New-TableRowXml {
    param(
        [string[]]$Cells,
        [int[]]$Widths,
        [switch]$Header
    )

    $cellXml = for ($i = 0; $i -lt $Cells.Count; $i++) {
        $width = if ($Widths -and $i -lt $Widths.Count) { $Widths[$i] } else { 2200 }
        New-TableCellXml -Text $Cells[$i] -Width $width -Bold:$Header
    }

    return "<w:tr>$($cellXml -join '')</w:tr>"
}

function New-TableXml {
    param(
        [object[]]$Rows
    )

    $widths = @(1800, 1800, 3400, 5000)
    $rowXml = foreach ($row in $Rows) {
        New-TableRowXml -Cells $row -Widths $widths -Header:($row[0] -eq 'Field')
    }

    return @"
<w:tbl>
  <w:tblPr>
    <w:tblStyle w:val="TableGrid"/>
    <w:tblW w:w="0" w:type="auto"/>
    <w:tblBorders>
      <w:top w:val="single" w:sz="8" w:space="0" w:color="000000"/>
      <w:left w:val="single" w:sz="8" w:space="0" w:color="000000"/>
      <w:bottom w:val="single" w:sz="8" w:space="0" w:color="000000"/>
      <w:right w:val="single" w:sz="8" w:space="0" w:color="000000"/>
      <w:insideH w:val="single" w:sz="8" w:space="0" w:color="000000"/>
      <w:insideV w:val="single" w:sz="8" w:space="0" w:color="000000"/>
    </w:tblBorders>
  </w:tblPr>
  <w:tblGrid>
    <w:gridCol w:w="1800"/>
    <w:gridCol w:w="1800"/>
    <w:gridCol w:w="3400"/>
    <w:gridCol w:w="5000"/>
  </w:tblGrid>
  $($rowXml -join "`n")
</w:tbl>
"@
}

$sections = @()
$i = 0
while ($i -lt $lines.Count) {
    if ($lines[$i] -match '^###\s+Table\s+([0-9.]+)\s+(.+)$') {
        $number = $matches[1]
        $name = $matches[2].Trim()
        $j = $i + 1
        while ($j -lt $lines.Count -and [string]::IsNullOrWhiteSpace($lines[$j])) {
            $j++
        }

        $rows = @()
        while ($j -lt $lines.Count -and $lines[$j].Trim().StartsWith('|')) {
            $parts = $lines[$j].Trim().Trim('|').Split('|') | ForEach-Object { $_.Trim() }
            if ($parts.Count -ge 4 -and $parts[0] -ne '---') {
                if (-not ($parts[0] -match '^-+$')) {
                    $rows += ,@($parts[0], $parts[1], $parts[2], $parts[3])
                }
            }
            $j++
        }

        $sections += [pscustomobject]@{
            Number = $number
            Name = $name
            Rows = $rows
        }
        $i = $j
        continue
    }
    $i++
}

$bodyParts = @()
$bodyParts += New-ParagraphXml -Text 'Data Dictionary Tables' -Bold -Center
$bodyParts += New-ParagraphXml -Text ''

foreach ($section in $sections) {
    $name = $section.Name
    $purpose = $tablePurposes[$name]
    $extra = $tableExtra[$name]

    if (-not $purpose) {
        $purpose = "stores the data recorded for the $($name.ToLower()) entity"
    }
    if (-not $extra) {
        $extra = 'It contains the key attributes, references, and status details needed for this part of the system. This table is essential for supporting its corresponding business process and maintaining normalized data relationships.'
    }

    $description = "Table $($section.Number) presents the $($name.ToLower()) table, which $purpose. $extra"
    $bodyParts += New-ParagraphXml -Text "Table $($section.Number) $name" -Bold
    $bodyParts += New-ParagraphXml -Text $description
    $bodyParts += New-TableXml -Rows $section.Rows
    $bodyParts += New-ParagraphXml -Text ''
}

$documentXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas"
 xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
 xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math"
 xmlns:v="urn:schemas-microsoft-com:vml"
 xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing"
 xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"
 xmlns:w10="urn:schemas-microsoft-com:office:word"
 xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
 xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"
 xmlns:w15="http://schemas.microsoft.com/office/word/2012/wordml"
 xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup"
 xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk"
 xmlns:wne="http://schemas.microsoft.com/office/2006/wordml"
 xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape"
 mc:Ignorable="w14 w15 wp14">
  <w:body>
    $($bodyParts -join "`n")
    <w:sectPr>
      <w:pgSz w:w="12240" w:h="15840"/>
      <w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="708" w:footer="708" w:gutter="0"/>
    </w:sectPr>
  </w:body>
</w:document>
"@

$contentTypesXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>
"@

$relsXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>
"@

$docRelsXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>
"@

Set-Content -LiteralPath (Join-Path $tempDir '[Content_Types].xml') -Value $contentTypesXml -Encoding UTF8
Set-Content -LiteralPath (Join-Path $tempDir '_rels\.rels') -Value $relsXml -Encoding UTF8
Set-Content -LiteralPath (Join-Path $tempDir 'word\document.xml') -Value $documentXml -Encoding UTF8
Set-Content -LiteralPath (Join-Path $tempDir 'word\_rels\document.xml.rels') -Value $docRelsXml -Encoding UTF8

if (Test-Path $outputPath) {
    Remove-Item -LiteralPath $outputPath -Force
}

Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory($tempDir, $outputPath)

Remove-Item -LiteralPath $tempDir -Recurse -Force

Write-Output "Created $outputPath"
