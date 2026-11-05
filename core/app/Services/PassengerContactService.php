<?php

namespace App\Services;

final class PassengerContactService
{
    public function normalize(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $digits = preg_replace('/\D+/', '', (string) $value);

        if (strlen($digits) === 12 && str_starts_with($digits, '63')) {
            return '0'.substr($digits, 2);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            return '0'.$digits;
        }

        return $digits;
    }

    public function isValid(mixed $value): bool
    {
        return preg_match('/^09\d{9}$/', $this->normalize($value)) === 1;
    }
}
