<?php

namespace Laravel\Ui\Auth;

use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class TwoFactorAuthenticator
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a new secret suitable for time-based one-time passwords.
     */
    public static function generateSecret(): string
    {
        $bytes = random_bytes(20);
        $buffer = 0;
        $bits = 0;
        $secret = '';

        foreach (str_split($bytes) as $byte) {
            $buffer = ($buffer << 8) | ord($byte);
            $bits += 8;

            while ($bits >= 5) {
                $bits -= 5;
                $secret .= self::BASE32_ALPHABET[($buffer >> $bits) & 31];
            }

            $buffer &= (1 << $bits) - 1;
        }

        if ($bits > 0) {
            $secret .= self::BASE32_ALPHABET[($buffer << (5 - $bits)) & 31];
        }

        return $secret;
    }

    /**
     * Determine whether a TOTP code matches the secret within the clock window.
     */
    public static function verify(string $secret, string $code, ?int $timestamp = null): bool
    {
        $code = self::normalizeCode($code);

        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $timestamp ??= time();
        $counter = intdiv($timestamp, 30);

        foreach ([-1, 0, 1] as $window) {
            if (hash_equals(self::totp($secret, $counter + $window), $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build an authenticator-app URI for manual entry or QR-code generators.
     */
    public static function provisioningUri(string $secret, string $account, string $issuer): string
    {
        $label = rawurlencode($issuer.':'.$account);
        $query = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => 6,
            'period' => 30,
        ], '', '&', PHP_QUERY_RFC3986);

        return 'otpauth://totp/'.$label.'?'.$query;
    }

    /**
     * Generate one-time recovery codes for the account.
     *
     * @return array<int, string>
     */
    public static function generateRecoveryCodes(): array
    {
        $codes = [];

        for ($index = 0; $index < 8; $index++) {
            $code = strtoupper(bin2hex(random_bytes(4)));
            $codes[] = substr($code, 0, 4).'-'.substr($code, 4, 4);
        }

        return $codes;
    }

    /**
     * Hash recovery codes before storing them in the user's record.
     *
     * @param  array<int, string>  $codes
     * @return array<int, string>
     */
    public static function hashRecoveryCodes(array $codes): array
    {
        return array_map(function (string $code) {
            return Hash::make(self::normalizeCode($code));
        }, $codes);
    }

    /**
     * Verify a recovery code and return the remaining hashes after consuming it.
     *
     * A null result means the code did not match. An empty array means the last
     * recovery code was used successfully.
     *
     * @param  array<int, string>  $hashedCodes
     * @return array<int, string>|null
     */
    public static function consumeRecoveryCode(array $hashedCodes, string $code): ?array
    {
        $normalizedCode = self::normalizeCode($code);

        foreach ($hashedCodes as $index => $hash) {
            if (is_string($hash) && Hash::check($normalizedCode, $hash)) {
                unset($hashedCodes[$index]);

                return array_values($hashedCodes);
            }
        }

        return null;
    }

    /**
     * Normalize an authenticator or recovery code for comparison.
     */
    public static function normalizeCode(string $code): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $code));
    }

    /**
     * Convert a Base32 secret into its raw key bytes.
     */
    private static function decodeSecret(string $secret): string
    {
        $buffer = 0;
        $bits = 0;
        $decoded = '';

        foreach (str_split(strtoupper(rtrim($secret, '='))) as $character) {
            $value = strpos(self::BASE32_ALPHABET, $character);

            if ($value === false) {
                throw new InvalidArgumentException('The two-factor secret is invalid.');
            }

            $buffer = ($buffer << 5) | $value;
            $bits += 5;

            if ($bits >= 8) {
                $bits -= 8;
                $decoded .= chr(($buffer >> $bits) & 255);
            }

            $buffer &= (1 << $bits) - 1;
        }

        return $decoded;
    }

    /**
     * Generate a six-digit TOTP value for a counter.
     */
    private static function totp(string $secret, int $counter): string
    {
        $key = self::decodeSecret($secret);
        $message = pack('N2', ($counter >> 32) & 0xffffffff, $counter & 0xffffffff);
        $hash = hash_hmac('sha1', $message, $key, true);
        $offset = ord($hash[19]) & 0x0f;
        $binary = ((ord($hash[$offset]) & 0x7f) << 24)
            | ((ord($hash[$offset + 1]) & 0xff) << 16)
            | ((ord($hash[$offset + 2]) & 0xff) << 8)
            | (ord($hash[$offset + 3]) & 0xff);

        return str_pad((string) ($binary % 1000000), 6, '0', STR_PAD_LEFT);
    }
}
