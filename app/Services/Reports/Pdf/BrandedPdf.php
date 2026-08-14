<?php

namespace App\Services\Reports\Pdf;

use FPDF;

class BrandedPdf extends FPDF
{
    public function Footer(): void
    {
        $this->SetY(-10);
        $this->SetDrawColor(210, 220, 214);
        $left = $this->lMargin;
        $right = $this->GetPageWidth() - $this->rMargin;
        $this->Line($left, $this->GetY() - 2, $right, $this->GetY() - 2);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(92, 107, 101);
        $this->Cell(0, 5, 'AniTech Agriculture System', 0, 0, 'L');
        $this->Cell(0, 5, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
        $this->SetTextColor(0, 0, 0);
    }
}
