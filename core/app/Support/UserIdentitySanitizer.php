<?php

namespace App\Support;

use Illuminate\Support\Str;

class UserIdentitySanitizer
{
    public static function sanitize(array $data): array
    {
        foreach (['firstname', 'lastname'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = self::name($data[$field]);
            }
        }

        if (array_key_exists('username', $data)) {
            $data['username'] = self::username($data['username']);
        }

        return $data;
    }

    public static function name(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_scalar($value) && !$value instanceof \Stringable) {
            return '';
        }

        $cleaned = preg_replace('/[^\p{L}\p{M}\s]/u', '', (string) $value) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $cleaned) ?? $cleaned);
    }

    public static function username(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_scalar($value) && !$value instanceof \Stringable) {
            return '';
        }

        $cleaned = preg_replace('/[^a-z0-9_]/', '', Str::lower(Str::ascii((string) $value)));

        return $cleaned ?? '';
    }

    public static function nameRules(int $maxLength = 40): array
    {
        return [
            'required',
            'string',
            "max:$maxLength",
            'regex:/^[\p{L}\p{M}]+(?:\s+[\p{L}\p{M}]+)*$/u',
        ];
    }

    public static function usernameRules(int $minLength = 6, int $maxLength = 40): array
    {
        return [
            'required',
            'string',
            "min:$minLength",
            "max:$maxLength",
            'regex:/^[a-z0-9_]+$/',
        ];
    }
}
