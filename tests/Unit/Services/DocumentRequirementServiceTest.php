<?php

namespace Tests\Unit\Services;

use App\Enums\DocumentType;
use App\Services\Documents\DocumentRequirementService;
use PHPUnit\Framework\TestCase;

class DocumentRequirementServiceTest extends TestCase
{
    public function test_walk_in_application_requires_office_membership_form(): void
    {
        $service = new DocumentRequirementService();

        $required = array_map(
            static fn (DocumentType $type): string => $type->value,
            $service->requiredFor('application', ['source' => 'walk_in']),
        );

        $this->assertSame([
            DocumentType::BIRTH_CERTIFICATE->value,
            DocumentType::CEDULA->value,
            DocumentType::TWO_BY_TWO_PICTURE->value,
            DocumentType::OFFICE_MEMBERSHIP_FORM->value,
        ], $required);
    }

    public function test_mobile_application_does_not_require_office_membership_form(): void
    {
        $service = new DocumentRequirementService();

        $required = array_map(
            static fn (DocumentType $type): string => $type->value,
            $service->requiredFor('application', ['source' => 'mobile']),
        );

        $this->assertSame([
            DocumentType::BIRTH_CERTIFICATE->value,
            DocumentType::CEDULA->value,
            DocumentType::TWO_BY_TWO_PICTURE->value,
        ], $required);
    }
}
