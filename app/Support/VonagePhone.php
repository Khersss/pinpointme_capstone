<?php

namespace App\Support;

class VonagePhone
{
    public static function normalizeToE164(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $cleaned = preg_replace('/\D+/', '', $phone);
        if ($cleaned === '') {
            return null;
        }

        if (str_starts_with($cleaned, '63')) {
            return '+' . $cleaned;
        }

        if (str_starts_with($cleaned, '09') && strlen($cleaned) === 11) {
            return '+63' . substr($cleaned, 1);
        }

        if (str_starts_with($cleaned, '9') && strlen($cleaned) === 10) {
            return '+63' . $cleaned;
        }

        return null;
    }
}
