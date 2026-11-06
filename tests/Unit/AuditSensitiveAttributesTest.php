<?php

namespace Tests\Unit;

use App\Models\Agent;
use App\Models\Retrait;
use App\Models\User;
use Tests\TestCase;

class AuditSensitiveAttributesTest extends TestCase
{
    public function test_sensitive_and_configured_attributes_are_excluded_from_audits(): void
    {
        $exclusions = [
            User::class => ['password', 'remember_token'],
            Agent::class => ['pin_hash'],
            Retrait::class => ['note', 'updated_at'],
        ];

        foreach ($exclusions as $modelClass => $attributes) {
            $model = new $modelClass();

            foreach ($attributes as $attribute) {
                $this->assertContains($attribute, $model->getAuditExclude());
            }
        }
    }
}
