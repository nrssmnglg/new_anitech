<?php

namespace App\Services\Routing;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class PublicRouteKeyService
{
    public function encode(int|string $value): string
    {
        $encrypted = Crypt::encryptString((string) $value);

        return rtrim(strtr(base64_encode($encrypted), '+/', '-_'), '=');
    }

    public function decode(string $value): ?int
    {
        $decoded = base64_decode(
            strtr($value, '-_', '+/') . str_repeat('=', (4 - strlen($value) % 4) % 4),
            true
        );

        if ($decoded === false) {
            return null;
        }

        try {
            $id = Crypt::decryptString($decoded);
        } catch (DecryptException) {
            return null;
        }

        return ctype_digit($id) ? (int) $id : null;
    }
}
