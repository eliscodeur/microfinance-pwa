<?php

namespace App\Support;

class AuditValueSanitizer
{
    private const SENSITIVE_KEY_PATTERN = '/password|passphrase|pin|secret|token|credential|api[_-]?key|authorization|remember[_-]?token/i';

    public function sanitize(array $values): array
    {
        $sanitized = [];

        foreach ($values as $key => $value) {
            if (preg_match(self::SENSITIVE_KEY_PATTERN, (string) $key)) {
                $sanitized[$key] = '[MASQUÉ]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitize($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
