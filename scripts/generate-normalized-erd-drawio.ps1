$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
$outputPath = Join-Path $root 'docs\normalized-erd.drawio'

$entities = @(
    @{
        Id='barangays'; Name='BARANGAYS'; X=60; Y=860; Width=220;
        Fields=@(
            @{Key='PK'; Name='id'; Height=30},
            @{Key=''; Name='name'; Height=30},
            @{Key=''; Name='code'; Height=30},
            @{Key=''; Name='status'; Height=30}
        )
    },
    @{
        Id='associations'; Name='ASSOCIATIONS'; X=60; Y=650; Width=220;
        Fields=@(
            @{Key='PK'; Name='id'; Height=30},
            @{Key='FK'; Name='barangay_id'; Height=30},
            @{Key=''; Name='name'; Height=30},
            @{Key=''; Name='code'; Height=30},
            @{Key=''; Name='status'; Height=30}
        )
    },
    @{
        Id='member_types'; Name='MEMBER_TYPES'; X=60; Y=410; Width=220;
        Fields=@(
            @{Key='PK'; Name='id'; Height=30},
            @{Key=''; Name='code'; Height=30},
            @{Key=''; Name='name'; Height=30},
            @{Key=''; Name='is_new_member'; Height=30},
            @{Key=''; Name='is_senior'; Height=30},
            @{Key=''; Name='requires_membership_fee'; Height=30},
            @{Key=''; Name='mortuary_eligible'; Height=30}
        )
    },
    @{
        Id='farmers'; Name='FARMERS'; X=340; Y=650; Width=250;
        Fields=@(
            @{Key='PK'; Name='id'; Height=28},
            @{Key=''; Name='farmer_code'; Height=28},
            @{Key='FK'; Name='barangay_id'; Height=28},
            @{Key='FK'; Name='association_id'; Height=28},
            @{Key='FK'; Name='member_type_id'; Height=28},
            @{Key=''; Name='status'; Height=28},
            @{Key=''; Name='membership_status'; Height=28},
            @{Key=''; Name='record_origin'; Height=28},
            @{Key=''; Name='is_registry_record'; Height=28},
            @{Key=''; Name='registered_at'; Height=28},
            @{Key=''; Name='activated_at'; Height=28},
            @{Key=''; Name='last_renewal_year'; Height=28},
            @{Key=''; Name='inactive_at'; Height=28},
            @{Key=''; Name='inactive_reason'; Height=28}
        )
    },
    @{
        Id='farmer_profiles'; Name='FARMER_PROFILES'; X=340; Y=1040; Width=250;
        Fields=@(
            @{Key='PK'; Name='id'; Height=28},
            @{Key='FK'; Name='farmer_id'; Height=28},
            @{Key=''; Name='first_name'; Height=28},
            @{Key=''; Name='middle_name'; Height=28},
            @{Key=''; Name='last_name'; Height=28},
            @{Key=''; Name='suffix'; Height=28},
            @{Key=''; Name='sex'; Height=28},
            @{Key=''; Name='birth_date'; Height=28},
            @{Key=''; Name='civil_status'; Height=28},
            @{Key=''; Name='mobile_number'; Height=28},
            @{Key=''; Name='email'; Height=28},
            @{Key=''; Name='address'; Height=28}
        )
    },
    @{
        Id='users'; Name='USERS'; X=340; Y=60; Width=230;
        Fields=@(
            @{Key='PK'; Name='id'; Height=30},
            @{Key=''; Name='email'; Height=30},
            @{Key=''; Name='password'; Height=30},
            @{Key=''; Name='status'; Height=30}
        )
    },
    @{
        Id='office_profiles'; Name='OFFICE_PROFILES'; X=340; Y=240; Width=250;
        Fields=@(
            @{Key='PK'; Name='id'; Height=28},
            @{Key='FK'; Name='user_id'; Height=28},
            @{Key=''; Name='first_name'; Height=28},
            @{Key=''; Name='middle_name'; Height=28},
            @{Key=''; Name='last_name'; Height=28},
            @{Key=''; Name='suffix'; Height=28},
            @{Key=''; Name='employee_id'; Height=28},
            @{Key=''; Name='job_title'; Height=28},
            @{Key=''; Name='contact_number'; Height=28}
        )
    },
    @{
        Id='membership_applications'; Name='MEMBERSHIP_APPLICATIONS'; X=700; Y=40; Width=280;
        Fields=@(
            @{Key='PK'; Name='id'; Height=28},
            @{Key='FK'; Name='farmer_id'; Height=28},
            @{Key=''; Name='application_no'; Height=28},
            @{Key=''; Name='source'; Height=28},
            @{Key=''; Name='status'; Height=28},
            @{Key=''; Name='submitted_at'; Height=28},
            @{Key='FK'; Name='reviewed_by'; Height=28},
            @{Key=''; Name='reviewed_at'; Height=28},
            @{Key=''; Name='approved_at'; Height=28},
            @{Key=''; Name='remarks'; Height=28},
            @{Key=''; Name='rejection_reason'; Height=28},
            @{Key=''; Name='rejection_details'; Height=28}
        )
    },
    @{
        Id='renewal_requests'; Name='RENEWAL_REQUESTS'; X=1020; Y=40; Width=260;
        Fields=@(
            @{Key='PK'; Name='id'; Height=28},
            @{Key='FK'; Name='farmer_id'; Height=28},
            @{Key=''; Name='year'; Height=28},
            @{Key=''; Name='source'; Height=28},
            @{Key=''; Name='status'; Height=28},
            @{Key=''; Name='submitted_at'; Height=28},
            @{Key='FK'; Name='reviewed_by'; Height=28},
            @{Key=''; Name='reviewed_at'; Height=28},
            @{Key=''; Name='approved_at'; Height=28},
            @{Key=''; Name='is_late'; Height=28},
            @{Key=''; Name='remarks'; Height=28}
        )
    },
    @{
        Id='reactivation_requests'; Name='REACTIVATION_REQUESTS'; X=1020; Y=390; Width=260;
        Fields=@(
            @{Key='PK'; Name='id'; Height=28},
            @{Key='FK'; Name='farmer_id'; Height=28},
            @{Key=''; Name='status'; Height=28},
            @{Key=''; Name='reason'; Height=28},
            @{Key=''; Name='submitted_at'; Height=28},
            @{Key='FK'; Name='reviewed_by'; Height=28},
            @{Key=''; Name='reviewed_at'; Height=28},
            @{Key=''; Name='remarks'; Height=28}
        )
    },
    @{
        Id='document_requirements'; Name='DOCUMENT_REQUIREMENTS'; X=700; Y=420; Width=280;
        Fields=@(
            @{Key='PK'; Name='id'; Height=28},
            @{Key=''; Name='workflow'; Height=28},
            @{Key=''; Name='source'; Height=28},
            @{Key=''; Name='document_type'; Height=28},
            @{Key=''; Name='is_active'; Height=28},
            @{Key=''; Name='sort_order'; Height=28}
        )
    },
    @{
        Id='farmer_documents'; Name='FARMER_DOCUMENTS'; X=700; Y=650; Width=280;
        Fields=@(
            @{Key='PK'; Name='id'; Height=28},
            @{Key='FK'; Name='membership_application_id'; Height=28},
            @{Key='FK'; Name='renewal_request_id'; Height=28},
            @{Key=''; Name='document_type'; Height=28},
            @{Key=''; Name='original_name'; Height=28},
            @{Key=''; Name='is_required'; Height=28},
            @{Key=''; Name='is_received'; Height=28},
            @{Key='FK'; Name='received_by'; Height=28},
            @{Key=''; Name='verification_status'; Height=28},
            @{Key='FK'; Name='verified_by'; Height=28},
            @{Key=''; Name='verified_at'; Height=28},
            @{Key=''; Name='remarks'; Height=28}
        )
    },
    @{
        Id='fee_schedules'; Name='FEE_SCHEDULES'; X=1020; Y=700; Width=260;
        Fields=@(
            @{Key='PK'; Name='id'; Height=28},
            @{Key=''; Name='year'; Height=28},
            @{Key=''; Name='membership_fee'; Height=28},
            @{Key=''; Name='annual_due'; Height=28},
            @{Key=''; Name='mortuary_fee'; Height=28},
            @{Key=''; Name='renewal_deadline'; Height=28},
            @{Key=''; Name='is_active'; Height=28},
            @{Key=''; Name='effective_from'; Height=28},
            @{Key=''; Name='effective_to'; Height=28}
        )
    },
    @{
        Id='payment_assessments'; Name='PAYMENT_ASSESSMENTS'; X=700; Y=1030; Width=280;
        Fields=@(
            @{Key='PK'; Name='id'; Height=28},
            @{Key='FK'; Name='membership_application_id'; Height=28},
            @{Key='FK'; Name='renewal_request_id'; Height=28},
            @{Key='FK'; Name='fee_schedule_id'; Height=28},
            @{Key=''; Name='member_type_snapshot'; Height=28},
            @{Key=''; Name='membership_fee'; Height=28},
            @{Key=''; Name='annual_due'; Height=28},
            @{Key=''; Name='mortuary_fee'; Height=28},
            @{Key=''; Name='total_amount_due'; Height=28},
            @{Key=''; Name='due_date'; Height=28},
            @{Key=''; Name='status'; Height=28}
        )
    },
    @{
        Id='payments'; Name='PAYMENTS'; X=1020; Y=1040; Width=260;
        Fields=@(
            @{Key='PK'; Name='id'; Height=28},
            @{Key='FK'; Name='payment_assessment_id'; Height=28},
            @{Key=''; Name='payment_method'; Height=28},
            @{Key=''; Name='reference_no'; Height=28},
            @{Key=''; Name='amount_paid'; Height=28},
            @{Key=''; Name='paid_at'; Height=28},
            @{Key='FK'; Name='verified_by'; Height=28},
            @{Key=''; Name='verified_at'; Height=28},
            @{Key=''; Name='status'; Height=28},
            @{Key=''; Name='receipt_no'; Height=28}
        )
    },
    @{
        Id='membership_ledgers'; Name='MEMBERSHIP_LEDGERS'; X=1360; Y=1030; Width=270;
        Fields=@(
            @{Key='PK'; Name='id'; Height=28},
            @{Key=''; Name='year'; Height=28},
            @{Key='FK'; Name='membership_application_id'; Height=28},
            @{Key='FK'; Name='renewal_request_id'; Height=28},
            @{Key=''; Name='member_type_snapshot'; Height=28},
            @{Key=''; Name='membership_fee'; Height=28},
            @{Key=''; Name='annual_due'; Height=28},
            @{Key=''; Name='mortuary_fee'; Height=28},
            @{Key=''; Name='total_amount_due'; Height=28},
            @{Key=''; Name='amount_paid'; Height=28},
            @{Key=''; Name='paid_at'; Height=28},
            @{Key=''; Name='payment_status'; Height=28},
            @{Key=''; Name='mortuary_eligible'; Height=28},
            @{Key=''; Name='status'; Height=28}
        )
    },
    @{
        Id='mortuary_claims'; Name='MORTUARY_CLAIMS'; X=1360; Y=1430; Width=270;
        Fields=@(
            @{Key='PK'; Name='id'; Height=28},
            @{Key='FK'; Name='membership_ledger_id'; Height=28},
            @{Key=''; Name='claim_reference'; Height=28},
            @{Key=''; Name='claim_amount'; Height=28},
            @{Key=''; Name='claim_date'; Height=28},
            @{Key=''; Name='claimer_name'; Height=28},
            @{Key=''; Name='claimer_relationship'; Height=28},
            @{Key=''; Name='claimer_contact_number'; Height=28},
            @{Key=''; Name='claimer_address'; Height=28},
            @{Key=''; Name='claimer_valid_id_received'; Height=28},
            @{Key=''; Name='proof_of_relationship_received'; Height=28},
            @{Key=''; Name='death_certificate_received'; Height=28},
            @{Key=''; Name='status'; Height=28},
            @{Key='FK'; Name='filed_by'; Height=28},
            @{Key='FK'; Name='approved_by'; Height=28},
            @{Key='FK'; Name='released_by'; Height=28},
            @{Key=''; Name='remarks'; Height=28}
        )
    },
    @{
        Id='advisories'; Name='ADVISORIES'; X=1740; Y=60; Width=250;
        Fields=@(
            @{Key='PK'; Name='id'; Height=28},
            @{Key=''; Name='title'; Height=28},
            @{Key=''; Name='slug'; Height=28},
            @{Key=''; Name='status'; Height=28},
            @{Key=''; Name='audience_type'; Height=28},
            @{Key='FK'; Name='barangay_id'; Height=28},
            @{Key='FK'; Name='association_id'; Height=28},
            @{Key='FK'; Name='published_by'; Height=28},
            @{Key=''; Name='published_at'; Height=28}
        )
    },
    @{
        Id='advisory_attachments'; Name='ADVISORY_ATTACHMENTS'; X=1740; Y=370; Width=250;
        Fields=@(
            @{Key='PK'; Name='id'; Height=30},
            @{Key='FK'; Name='advisory_id'; Height=30},
            @{Key=''; Name='original_name'; Height=30}
        )
    },
    @{
        Id='advisory_member_type'; Name='ADVISORY_MEMBER_TYPE'; X=1740; Y=540; Width=250;
        Fields=@(
            @{Key='PK'; Name='id'; Height=30},
            @{Key='FK'; Name='advisory_id'; Height=30},
            @{Key='FK'; Name='member_type_id'; Height=30}
        )
    },
    @{
        Id='queries'; Name='QUERIES'; X=1740; Y=760; Width=250;
        Fields=@(
            @{Key='PK'; Name='id'; Height=30},
            @{Key='FK'; Name='farmer_id'; Height=30},
            @{Key=''; Name='subject'; Height=30},
            @{Key=''; Name='status'; Height=30},
            @{Key=''; Name='archived_at'; Height=30}
        )
    },
    @{
        Id='query_images'; Name='QUERY_IMAGES'; X=1740; Y=980; Width=250;
        Fields=@(
            @{Key='PK'; Name='id'; Height=30},
            @{Key='FK'; Name='query_id'; Height=30},
            @{Key=''; Name='original_name'; Height=30}
        )
    },
    @{
        Id='query_responses'; Name='QUERY_RESPONSES'; X=1740; Y=1150; Width=250;
        Fields=@(
            @{Key='PK'; Name='id'; Height=30},
            @{Key='FK'; Name='query_id'; Height=30},
            @{Key='FK'; Name='responded_by'; Height=30},
            @{Key=''; Name='message'; Height=30}
        )
    },
    @{
        Id='query_response_attachments'; Name='QUERY_RESPONSE_ATTACHMENTS'; X=1740; Y=1330; Width=270;
        Fields=@(
            @{Key='PK'; Name='id'; Height=30},
            @{Key='FK'; Name='query_response_id'; Height=30},
            @{Key=''; Name='original_name'; Height=30}
        )
    }
)

$relationships = @(
    @{Source='office_profiles'; Target='users'; Label='has office profile'; Points=@(@{X=455;Y=240},@{X=455;Y=180})},
    @{Source='associations'; Target='barangays'; Label='has associations'; Points=@(@{X=170;Y=650},@{X=170;Y=980})},
    @{Source='farmers'; Target='barangays'; Label='contains farmers'; Points=@(@{X=330;Y=790},@{X=290;Y=790})},
    @{Source='farmers'; Target='associations'; Label='groups farmers'; Points=@(@{X=330;Y=720},@{X=290;Y=720})},
    @{Source='farmers'; Target='member_types'; Label='classifies farmers'; Points=@(@{X=330;Y=650},@{X=290;Y=650},@{X=290;Y=560})},
    @{Source='farmer_profiles'; Target='farmers'; Label='has personal profile'; Points=@(@{X=465;Y=1040},@{X=465;Y=1015})},
    @{Source='membership_applications'; Target='farmers'; Label='submits'; Points=@(@{X=690;Y=170},@{X=630;Y=170},@{X=630;Y=760})},
    @{Source='renewal_requests'; Target='farmers'; Label='files'; Points=@(@{X=1010;Y=170},@{X=630;Y=170},@{X=630;Y=790})},
    @{Source='reactivation_requests'; Target='farmers'; Label='requests'; Points=@(@{X=1010;Y=500},@{X=620;Y=500},@{X=620;Y=820})},
    @{Source='farmer_documents'; Target='membership_applications'; Label='has submitted documents'; Points=@(@{X=840;Y=650},@{X=840;Y=390})},
    @{Source='farmer_documents'; Target='renewal_requests'; Label='has submitted documents'; Points=@(@{X=960;Y=650},@{X=960;Y=390})},
    @{Source='payment_assessments'; Target='membership_applications'; Label='generates'; Points=@(@{X=840;Y=1030},@{X=840;Y=390})},
    @{Source='payment_assessments'; Target='renewal_requests'; Label='generates'; Points=@(@{X=960;Y=1030},@{X=960;Y=390})},
    @{Source='payment_assessments'; Target='fee_schedules'; Label='defines fees for'; Points=@(@{X=1110;Y=1030},@{X=1110;Y=980})},
    @{Source='payments'; Target='payment_assessments'; Label='is settled by'; Points=@(@{X=1020;Y=1180},@{X=980;Y=1180})},
    @{Source='membership_ledgers'; Target='membership_applications'; Label='creates ledger entry for'; Points=@(@{X=1350;Y=1170},@{X=1295;Y=1170},@{X=1295;Y=170},@{X=990;Y=170})},
    @{Source='membership_ledgers'; Target='renewal_requests'; Label='creates ledger entry for'; Points=@(@{X=1350;Y=1210},@{X=1310;Y=1210},@{X=1310;Y=170})},
    @{Source='mortuary_claims'; Target='membership_ledgers'; Label='supports'; Points=@(@{X=1495;Y=1430},@{X=1495;Y=1420})},
    @{Source='membership_applications'; Target='users'; Label='reviews'; Points=@(@{X=690;Y=200},@{X=610;Y=200},@{X=610;Y=120})},
    @{Source='renewal_requests'; Target='users'; Label='reviews'; Points=@(@{X=1010;Y=200},@{X=610;Y=200},@{X=610;Y=130})},
    @{Source='reactivation_requests'; Target='users'; Label='reviews'; Points=@(@{X=1010;Y=530},@{X=620;Y=530},@{X=620;Y=140})},
    @{Source='farmer_documents'; Target='users'; Label='receives/verifies'; Points=@(@{X=690;Y=760},@{X=620;Y=760},@{X=620;Y=150})},
    @{Source='payments'; Target='users'; Label='verifies'; Points=@(@{X=1010;Y=1210},@{X=640;Y=1210},@{X=640;Y=160})},
    @{Source='mortuary_claims'; Target='users'; Label='files/approves/releases'; Points=@(@{X=1350;Y=1540},@{X=650;Y=1540},@{X=650;Y=170})},
    @{Source='advisories'; Target='users'; Label='publishes'; Points=@(@{X=1730;Y=185},@{X=640;Y=185})},
    @{Source='advisories'; Target='barangays'; Label='is targeted by'; Points=@(@{X=1730;Y=210},@{X=1650;Y=210},@{X=1650;Y=890},@{X=290;Y=890})},
    @{Source='advisories'; Target='associations'; Label='is targeted by'; Points=@(@{X=1730;Y=235},@{X=1630;Y=235},@{X=1630;Y=680},@{X=290;Y=680})},
    @{Source='advisory_attachments'; Target='advisories'; Label='has attachments'; Points=@(@{X=1865;Y=370},@{X=1865;Y=340})},
    @{Source='advisory_member_type'; Target='advisories'; Label='targets member types through'; Points=@(@{X=1810;Y=540},@{X=1810;Y=340})},
    @{Source='advisory_member_type'; Target='member_types'; Label='is targeted through'; Points=@(@{X=1730;Y=630},@{X=1650;Y=630},@{X=1650;Y=530},@{X=290;Y=530})},
    @{Source='queries'; Target='farmers'; Label='submits'; Points=@(@{X=1730;Y=835},@{X=1650;Y=835},@{X=1650;Y=860},@{X=600;Y=860})},
    @{Source='query_images'; Target='queries'; Label='has images'; Points=@(@{X=1865;Y=980},@{X=1865;Y=940})},
    @{Source='query_responses'; Target='queries'; Label='receives responses'; Points=@(@{X=1865;Y=1150},@{X=1865;Y=940})},
    @{Source='query_responses'; Target='users'; Label='responds to'; Points=@(@{X=1730;Y=1240},@{X=1660;Y=1240},@{X=1660;Y=120},@{X=640;Y=120})},
    @{Source='query_response_attachments'; Target='query_responses'; Label='has attachments'; Points=@(@{X=1880;Y=1330},@{X=1880;Y=1270})}
)

$cells = New-Object System.Collections.Generic.List[string]
$cells.Add('        <mxCell id="0" />')
$cells.Add('        <mxCell id="1" parent="0" />')
$anchorMap = @{}
$edgeLabels = New-Object System.Collections.Generic.List[string]

foreach ($entity in $entities) {
    $fieldHeightSum = 0
    foreach ($field in $entity.Fields) {
        $fieldHeightSum += [int]$field.Height
    }
    $totalHeight = 30 + $fieldHeightSum

    $cells.Add("        <mxCell id=""$($entity.Id)"" parent=""1"" style=""shape=table;startSize=30;container=1;collapsible=1;childLayout=tableLayout;fixedRows=1;rowLines=0;fontStyle=1;align=center;resizeLast=1;html=1;strokeWidth=2;"" value=""$($entity.Name)"" vertex=""1"">")
    $cells.Add("          <mxGeometry x=""$($entity.X)"" y=""$($entity.Y)"" width=""$($entity.Width)"" height=""$totalHeight"" as=""geometry"" />")
    $cells.Add('        </mxCell>')

    $yOffset = 30
    $rowIndex = 1
    foreach ($field in $entity.Fields) {
        $rowId = "$($entity.Id)_row_$rowIndex"
        if (-not $anchorMap.ContainsKey($entity.Id)) {
            $anchorMap[$entity.Id] = $rowId
        }
        $bottomFlag = if ($rowIndex -eq 1) { 1 } else { 0 }
        $cells.Add("        <mxCell id=""$rowId"" parent=""$($entity.Id)"" style=""shape=tableRow;horizontal=0;startSize=0;swimlaneHead=0;swimlaneBody=0;fillColor=none;collapsible=0;dropTarget=0;points=[[0,0.5],[1,0.5]];portConstraint=eastwest;top=0;left=0;right=0;bottom=$bottomFlag;"" value="""" vertex=""1"">")
        $cells.Add("          <mxGeometry y=""$yOffset"" width=""$($entity.Width)"" height=""$($field.Height)"" as=""geometry"" />")
        $cells.Add('        </mxCell>')

        $styleKey = if ($field.Key -eq 'PK' -or $field.Key -eq 'FK') { 'fontStyle=1;' } else { 'editable=1;' }
        $nameStyle = if ($field.Key -eq 'PK') { 'fontStyle=5;' } else { 'fontStyle=1;' }

        $cells.Add("        <mxCell id=""$rowId`_key"" parent=""$rowId"" style=""shape=partialRectangle;connectable=0;fillColor=none;top=0;left=0;bottom=0;right=0;$styleKey overflow=hidden;whiteSpace=wrap;html=1;"" value=""$($field.Key)"" vertex=""1"">")
        $cells.Add("          <mxGeometry width=""30"" height=""$($field.Height)"" as=""geometry""><mxRectangle width=""30"" height=""$($field.Height)"" as=""alternateBounds"" /></mxGeometry>")
        $cells.Add('        </mxCell>')
        $cells.Add("        <mxCell id=""$rowId`_name"" parent=""$rowId"" style=""shape=partialRectangle;connectable=0;fillColor=none;top=0;left=0;bottom=0;right=0;align=left;spacingLeft=6;$nameStyle overflow=hidden;whiteSpace=wrap;html=1;"" value=""$($field.Name)"" vertex=""1"">")
        $cells.Add("          <mxGeometry x=""30"" width=""$($entity.Width - 30)"" height=""$($field.Height)"" as=""geometry""><mxRectangle width=""$($entity.Width - 30)"" height=""$($field.Height)"" as=""alternateBounds"" /></mxGeometry>")
        $cells.Add('        </mxCell>')

        $yOffset += $field.Height
        $rowIndex++
    }
}

$edgeCounter = 1
foreach ($rel in $relationships) {
    $edgeId = "edge_$edgeCounter"
    $sourceId = $anchorMap[$rel.Source]
    $targetId = $anchorMap[$rel.Target]
    $cells.Add("        <mxCell id=""$edgeId"" edge=""1"" parent=""1"" source=""$sourceId"" target=""$targetId"" style=""edgeStyle=orthogonalEdgeStyle;rounded=0;orthogonalLoop=1;jettySize=auto;html=1;strokeWidth=2;startArrow=ERmandOne;startFill=0;endArrow=ERoneToMany;endFill=0;"">")
    $cells.Add('          <mxGeometry relative="1" as="geometry">')
    if ($rel.Points.Count -gt 0) {
        $cells.Add('            <Array as="points">')
        foreach ($pt in $rel.Points) {
            $cells.Add("              <mxPoint x=""$($pt.X)"" y=""$($pt.Y)"" />")
        }
        $cells.Add('            </Array>')
    }
    $cells.Add('          </mxGeometry>')
    $cells.Add('        </mxCell>')

    $labelId = "label_$edgeCounter"
    $edgeLabels.Add("        <mxCell id=""$labelId"" connectable=""0"" parent=""$edgeId"" style=""edgeLabel;html=1;align=center;verticalAlign=middle;resizable=0;points=[];fontStyle=1;labelBackgroundColor=none;"" value=""$([System.Security.SecurityElement]::Escape($rel.Label))"" vertex=""1"">")
    $edgeLabels.Add('          <mxGeometry relative="1" as="geometry"><mxPoint x="0" y="-8" as="offset" /></mxGeometry>')
    $edgeLabels.Add('        </mxCell>')
    $edgeCounter++
}

$allCells = ($cells + $edgeLabels) -join "`r`n"

$xml = @"
<mxfile host="app.diagrams.net" modified="2026-04-27T12:30:00.000Z" agent="Codex" version="24.7.17">
  <diagram name="Page-1" id="normalized-erd">
    <mxGraphModel dx="2600" dy="1800" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="2400" pageHeight="2200" math="0" shadow="0">
      <root>
$allCells
      </root>
    </mxGraphModel>
  </diagram>
</mxfile>
"@

Set-Content -LiteralPath $outputPath -Value $xml -Encoding UTF8
Write-Output "Created $outputPath"
