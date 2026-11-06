<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\AuditLogController;
use App\Models\Client;
use App\Models\Credit;
use PHPUnit\Framework\TestCase;

class AuditLogReferenceTest extends TestCase
{
    public function test_business_references_are_formatted_for_audit_rows(): void
    {
        $controller = new AuditLogController();
        $formatter = new \ReflectionMethod($controller, 'formatAuditableReference');

        $this->assertSame(
            'N° crédit : CR-001',
            $formatter->invoke($controller, Credit::class, ['credit_uid' => 'CR-001'])
        );
        $this->assertSame(
            'Awa Mensah · ULID : 01HXYZ',
            $formatter->invoke($controller, Client::class, [
                'nom' => 'Awa',
                'prenom' => 'Mensah',
                'ulid' => '01HXYZ',
            ])
        );
    }
}
