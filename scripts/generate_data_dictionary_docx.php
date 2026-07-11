<?php

declare(strict_types=1);

$input = __DIR__ . '/../docs/data-dictionary.md';
$outputDir = __DIR__ . '/../tmp/data-dictionary-docx';

if (! is_file($input)) {
    fwrite(STDERR, "Input file not found: {$input}\n");
    exit(1);
}

$markdown = file($input, FILE_IGNORE_NEW_LINES);

function xml(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

function textRun(string $text, bool $bold = false): string
{
    $text = xml($text);
    $boldXml = $bold ? '<w:rPr><w:b/></w:rPr>' : '';

    return "<w:r>{$boldXml}<w:t xml:space=\"preserve\">{$text}</w:t></w:r>";
}

function paragraph(array $runs, string $style = '', string $align = 'both'): string
{
    $stylePart = $style !== '' ? "<w:pStyle w:val=\"{$style}\"/>" : '';
    $alignPart = $align !== '' ? "<w:jc w:val=\"{$align}\"/>" : '';
    $styleXml = "<w:pPr>{$stylePart}{$alignPart}</w:pPr>";

    return '<w:p>' . $styleXml . implode('', $runs) . '</w:p>';
}

function tableCell(string $text, bool $header = false): string
{
    $run = textRun($text, $header);
    $paragraph = '<w:p><w:pPr><w:jc w:val="both"/></w:pPr>' . $run . '</w:p>';

    return '<w:tc><w:tcPr><w:tcW w:w="2200" w:type="dxa"/></w:tcPr>' . $paragraph . '</w:tc>';
}

function tableRow(array $cells, bool $header = false): string
{
    $xml = '<w:tr>';

    foreach ($cells as $cell) {
        $xml .= tableCell($cell, $header);
    }

    return $xml . '</w:tr>';
}

$bodyParts = [];
$tableBuffer = [];

$flushTable = function () use (&$tableBuffer, &$bodyParts): void {
    if ($tableBuffer === []) {
        return;
    }

    $rows = [];
    foreach ($tableBuffer as $index => $row) {
        if ($index === 1 && preg_match('/^\|(?:\s*-+\s*\|)+$/', $row)) {
            continue;
        }

        $trimmed = trim($row);
        $trimmed = trim($trimmed, '|');
        $cells = array_map(static fn (string $cell): string => trim($cell), explode('|', $trimmed));
        $rows[] = tableRow($cells, $index === 0);
    }

    $bodyParts[] = '<w:tbl>'
        . '<w:tblPr><w:tblW w:w="0" w:type="auto"/><w:tblBorders>'
        . '<w:top w:val="single" w:sz="4" w:space="0" w:color="auto"/>'
        . '<w:left w:val="single" w:sz="4" w:space="0" w:color="auto"/>'
        . '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="auto"/>'
        . '<w:right w:val="single" w:sz="4" w:space="0" w:color="auto"/>'
        . '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="auto"/>'
        . '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="auto"/>'
        . '</w:tblBorders></w:tblPr>'
        . implode('', $rows)
        . '</w:tbl>';

    $tableBuffer = [];
};

foreach ($markdown as $line) {
    if (str_starts_with($line, '|')) {
        $tableBuffer[] = $line;
        continue;
    }

    $flushTable();

    if (trim($line) === '') {
        $bodyParts[] = '<w:p/>';
        continue;
    }

    if (preg_match('/^(#{1,3})\s+(.*)$/', $line, $matches)) {
        $level = strlen($matches[1]);
        $style = match ($level) {
            1 => 'Heading1',
            2 => 'Heading2',
            default => 'Heading3',
        };
        $align = $level === 3 ? 'center' : 'left';
        $bodyParts[] = paragraph([textRun($matches[2], true)], $style, $align);
        continue;
    }

    if (str_starts_with($line, '- ')) {
        $bodyParts[] = paragraph([textRun('- ' . substr($line, 2))], '', 'left');
        continue;
    }

    if (preg_match('/^([^:]+):\s*(.*)$/', $line, $matches)) {
        $label = trim($matches[1]) . ': ';
        $value = $matches[2];
        $bodyParts[] = paragraph([textRun($label, true), textRun($value)], '', 'left');
        continue;
    }

    $bodyParts[] = paragraph([textRun($line)], '', 'both');
}

$flushTable();

$documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas"'
    . ' xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006"'
    . ' xmlns:o="urn:schemas-microsoft-com:office:office"'
    . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"'
    . ' xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math"'
    . ' xmlns:v="urn:schemas-microsoft-com:vml"'
    . ' xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing"'
    . ' xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"'
    . ' xmlns:w10="urn:schemas-microsoft-com:office:word"'
    . ' xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"'
    . ' xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"'
    . ' xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup"'
    . ' xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk"'
    . ' xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml"'
    . ' xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape"'
    . ' mc:Ignorable="w14 wp14">'
    . '<w:body>'
    . implode('', $bodyParts)
    . '<w:sectPr>'
    . '<w:pgSz w:w="12240" w:h="15840"/>'
    . '<w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="720" w:footer="720" w:gutter="0"/>'
    . '</w:sectPr>'
    . '</w:body></w:document>';

$contentTypes = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>
XML;

$rels = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>
XML;

$documentRels = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML;

$styles = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:style w:type="paragraph" w:default="1" w:styleId="Normal">
    <w:name w:val="Normal"/>
    <w:qFormat/>
    <w:rPr>
      <w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>
      <w:sz w:val="24"/>
    </w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Heading1">
    <w:name w:val="heading 1"/>
    <w:basedOn w:val="Normal"/>
    <w:qFormat/>
    <w:rPr>
      <w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>
      <w:b/>
      <w:sz w:val="32"/>
    </w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Heading2">
    <w:name w:val="heading 2"/>
    <w:basedOn w:val="Normal"/>
    <w:qFormat/>
    <w:rPr>
      <w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>
      <w:b/>
      <w:sz w:val="28"/>
    </w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Heading3">
    <w:name w:val="heading 3"/>
    <w:basedOn w:val="Normal"/>
    <w:qFormat/>
    <w:rPr>
      <w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>
      <w:b/>
      <w:sz w:val="28"/>
    </w:rPr>
  </w:style>
</w:styles>
XML;

$created = gmdate('Y-m-d\TH:i:s\Z');
$core = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:title>Data Dictionary for new_anitech</dc:title>
  <dc:creator>Codex</dc:creator>
  <cp:lastModifiedBy>Codex</cp:lastModifiedBy>
  <dcterms:created xsi:type="dcterms:W3CDTF">{$created}</dcterms:created>
  <dcterms:modified xsi:type="dcterms:W3CDTF">{$created}</dcterms:modified>
</cp:coreProperties>
XML;

$app = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
  <Application>Codex</Application>
</Properties>
XML;

if (is_dir($outputDir)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($outputDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($outputDir);
}

$directories = [
    $outputDir,
    $outputDir . '/_rels',
    $outputDir . '/word',
    $outputDir . '/word/_rels',
    $outputDir . '/docProps',
];

foreach ($directories as $directory) {
    if (! mkdir($directory, 0777, true) && ! is_dir($directory)) {
        fwrite(STDERR, "Unable to create directory: {$directory}\n");
        exit(1);
    }
}

file_put_contents($outputDir . '/[Content_Types].xml', $contentTypes);
file_put_contents($outputDir . '/_rels/.rels', $rels);
file_put_contents($outputDir . '/word/document.xml', $documentXml);
file_put_contents($outputDir . '/word/_rels/document.xml.rels', $documentRels);
file_put_contents($outputDir . '/word/styles.xml', $styles);
file_put_contents($outputDir . '/docProps/core.xml', $core);
file_put_contents($outputDir . '/docProps/app.xml', $app);

echo "Generated docx source tree: {$outputDir}\n";
