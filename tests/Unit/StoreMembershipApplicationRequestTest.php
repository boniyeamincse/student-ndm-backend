<?php

namespace Tests\Unit;

use App\Http\Requests\Api\V1\StoreMembershipApplicationRequest;
use PHPUnit\Framework\TestCase;

class StoreMembershipApplicationRequestTest extends TestCase
{
    public function test_desired_committee_id_rule_requires_existing_committee(): void
    {
        $rules = (new StoreMembershipApplicationRequest)->rules();

        $this->assertArrayHasKey('desired_committee_id', $rules);
        $this->assertContains('exists:committees,id', $rules['desired_committee_id']);
    }

    public function test_photo_max_message_matches_the_configured_limit(): void
    {
        $messages = (new StoreMembershipApplicationRequest)->messages();

        $this->assertSame(
            'Photo file size must not exceed 5MB.',
            $messages['photo.max']
        );
    }
}
