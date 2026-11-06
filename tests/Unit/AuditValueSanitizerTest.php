<?php

namespace Tests\Unit;

use App\Support\AuditValueSanitizer;
use PHPUnit\Framework\TestCase;

class AuditValueSanitizerTest extends TestCase
{
    public function test_sensitive_fields_are_masked_recursively(): void
    {
        $sanitized = (new AuditValueSanitizer())->sanitize([
            'name' => 'Agent',
            'pin_hash' => 'sensitive-hash',
            'nested' => [
                'api_key' => 'sensitive-key',
                'amount' => 2500,
            ],
        ]);

        $this->assertSame('Agent', $sanitized['name']);
        $this->assertSame('[MASQUÉ]', $sanitized['pin_hash']);
        $this->assertSame('[MASQUÉ]', $sanitized['nested']['api_key']);
        $this->assertSame(2500, $sanitized['nested']['amount']);
    }
}
