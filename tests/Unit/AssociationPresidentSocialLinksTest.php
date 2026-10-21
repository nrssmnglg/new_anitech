<?php

namespace Tests\Unit;

use App\Http\Requests\Admin\StoreAssociationRequest;
use App\Http\Requests\Admin\UpdateAssociationRequest;
use App\Models\Association;
use Tests\TestCase;

class AssociationPresidentSocialLinksTest extends TestCase
{
    public function test_association_fillable_includes_president_social_link(): void
    {
        $association = new Association([
            'president_social_link' => 'https://facebook.com/juan.delacruz',
        ]);

        $this->assertSame('https://facebook.com/juan.delacruz', $association->president_social_link);
    }

    public function test_store_request_has_rules_for_president_social_link(): void
    {
        $request = new StoreAssociationRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('president_social_link', $rules);
    }

    public function test_update_request_has_rules_for_president_social_link(): void
    {
        $request = new UpdateAssociationRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('president_social_link', $rules);
    }
}
