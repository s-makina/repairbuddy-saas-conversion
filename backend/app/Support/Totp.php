<?php

namespace App\Support;

class Totp
{
    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    public static function provisioningUri(string $issuer, string $accountName, string $secret): string
    {
        $label = rawurlencode($issuer).':'.rawurlencode($accountName);
        $query = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => 6,
            'period' => 30,
        ]);

        return "otpauth://totp/{$label}?{$query}";
    }

    public static function verify(string $secretBase32, string $code, int $window = 1, int $timestamp = null): bool
    {
        $code = preg_replace('/\s+/', '', $code ?? '');
        if (! is_string($code) || ! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $timestamp ??= time();
        $counter = (int) floor($timestamp / 30);

        for ($i = -$window; $i <= $window; $i++) {
            $candidate = self::generateCode($secretBase32, $counter + $i);
            if (hash_equals($candidate, $code)) {
                return true;
            }
        }

        return false;
    }

    private static function generateCode(string $secretBase32, int $counter): string
    {
        $secret = self::base32Decode($secretBase32);

        $binCounter = pack('N*', 0).pack('N*', $counter);
        $hash = hash_hmac('sha1', $binCounter, $secret, true);

        $offset = ord(substr($hash, -1)) & 0x0F;
        $part = substr($hash, $offset, 4);
        $value = unpack('N', $part)[1] & 0x7FFFFFFF;

        $mod = $value % 1000000;

        return str_pad((string) $mod, 6, '0', STR_PAD_LEFT);
    }

    private static function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';

        $len = strlen($data);
        for ($i = 0; $i < $len; $i++) {
            $binary .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
        }

        $chunks = str_split($binary, 5);
        $out = '';

        foreach ($chunks as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }
            $out .= $alphabet[bindec($chunk)];
        }

        return $out;
    }

    private static function base32Decode(string $base32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $base32));

        $binary = '';
        $len = strlen($base32);
        for ($i = 0; $i < $len; $i++) {
            $index = strpos($alphabet, $base32[$i]);
            if ($index === false) {
                continue;
            }
            $binary .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }

        $bytes = str_split($binary, 8);
        $out = '';

        foreach ($bytes as $byte) {
            if (strlen($byte) !== 8) {
                continue;
            }
            $out .= chr(bindec($byte));
        }

        return $out;
    }
}
