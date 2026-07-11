<?php

namespace App\Services\Reports\Pdf;

abstract class BasePdfReport
{
    protected BrandedPdf $pdf;
    protected int $rowIndex = 0;

    protected function bootPdf(string $title, string $orientation = 'L'): void
    {
        $this->pdf = new BrandedPdf($orientation, 'mm', 'A4');
        $this->pdf->SetTitle($title);
        $this->pdf->SetAuthor('AniTech Agriculture System');
        $this->pdf->SetCreator('AniTech Agriculture System');
        $this->pdf->SetAutoPageBreak(true, 14);
        $this->pdf->SetMargins(12, 12, 12);
        $this->pdf->AliasNbPages();
        $this->pdf->AddPage();
        $this->pdf->SetFont('Arial', '', 10);
    }

    protected function pageTitle(string $title, string $subtitle): void
    {
        $left = $this->pdf->GetX();
        $top = $this->pdf->GetY();

        $this->pdf->SetFillColor(241, 246, 243);
        $this->pdf->SetDrawColor(210, 220, 214);
        $this->pdf->Rect($left, $top, 273, 24, 'DF');

        $this->pdf->SetXY($left + 4, $top + 4);
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->SetTextColor(27, 77, 62);
        $this->pdf->Cell(0, 4, $this->text('ANITECH AGRICULTURE SYSTEM'), 0, 1, 'L');

        $this->pdf->SetX($left + 4);
        $this->pdf->SetFont('Arial', 'B', 16);
        $this->pdf->SetTextColor(32, 43, 39);
        $this->pdf->Cell(0, 7, $this->text($title), 0, 1, 'L');

        $this->pdf->SetX($left + 4);
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->SetTextColor(92, 107, 101);
        $this->pdf->Cell(0, 5, $this->text($subtitle), 0, 1, 'L');

        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetY($top + 30);
    }

    protected function metadataRow(string $label, string $value, float $labelWidth = 42): void
    {
        $this->pdf->SetFillColor(245, 248, 246);
        $this->pdf->SetDrawColor(226, 232, 228);
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->SetTextColor(27, 77, 62);
        $this->pdf->Cell($labelWidth, 7, $this->text($label), 1, 0, 'L', true);
        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->SetTextColor(32, 43, 39);
        $this->pdf->Cell(0, 7, $this->text($value), 1, 1);
        $this->pdf->SetTextColor(0, 0, 0);
    }

    protected function sectionTitle(string $title): void
    {
        $this->pdf->Ln(2);
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->SetTextColor(27, 77, 62);
        $this->pdf->Cell(0, 6, $this->text(strtoupper($title)), 0, 1, 'L');
        $this->pdf->SetDrawColor(210, 220, 214);
        $this->pdf->Line($this->pdf->GetX(), $this->pdf->GetY(), 285, $this->pdf->GetY());
        $this->pdf->Ln(3);
        $this->pdf->SetTextColor(0, 0, 0);
    }

    protected function tableHeader(array $headers): void
    {
        $this->pdf->SetFillColor(27, 77, 62);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetDrawColor(210, 220, 214);
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->rowIndex = 0;

        foreach ($headers as $header) {
            $this->pdf->Cell($header['width'], 9, $this->text($header['label']), 1, 0, $header['align'] ?? 'L', true);
        }

        $this->pdf->Ln();
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('Arial', '', 8);
    }

    protected function tableRow(array $cells, float $height = 7): void
    {
        $fill = $this->rowIndex % 2 === 1;
        $this->pdf->SetFillColor(247, 250, 248);

        foreach ($cells as $cell) {
            $this->pdf->Cell(
                $cell['width'],
                $height,
                $this->text($cell['value']),
                1,
                0,
                $cell['align'] ?? 'L',
                $cell['fill'] ?? $fill
            );
        }

        $this->pdf->Ln();
        $this->rowIndex++;
    }

    protected function text(string $value): string
    {
        return iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $value) ?: $value;
    }

    public function output(): string
    {
        return $this->pdf->Output('S');
    }
}
