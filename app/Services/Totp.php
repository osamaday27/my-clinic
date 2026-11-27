<?php
namespace App\Services;

/** RFC 6238 TOTP (HMAC-SHA1, 30 s step, 6 digits) with RFC 4648 Base32 - no third-party package. */
class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(int $bytes = 20): string
    {
        return $this->base32Encode(random_bytes($bytes));           // 160-bit CSPRNG secret
    }

    public function codeAt(string $secret, int $step, int $digits = 6): string
    {
        $hash = hash_hmac('sha1', pack('J', $step), $this->base32Decode($secret), true);
        $o = ord($hash[19]) & 0x0F;                                  // dynamic truncation (RFC 4226)
        $value = ((ord($hash[$o]) & 0x7F) << 24) | ((ord($hash[$o + 1]) & 0xFF) << 16) | ((ord($hash[$o + 2]) & 0xFF) << 8) | (ord($hash[$o + 3]) & 0xFF);

        return str_pad((string) ($value % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
    }

    /** Returns the matched time-step (so the caller can store it) or null. Window +-1 step tolerates clock drift. */
    public function verify(string $secret, string $code, ?int $lastStep = null, int $window = 1, ?int $time = null): ?int
    {
        $code = preg_replace('/\s+/', '', $code);
        if (! preg_match('/^\d{6}$/', $code)) {
            return null;
        }
        $now = intdiv($time ?? time(), 30);
        for ($i = -$window; $i <= $window; $i++) {
            $step = $now + $i;
            if ($lastStep !== null && $step <= $lastStep) {
                continue;                                            // replay protection
            }
            if (hash_equals($this->codeAt($secret, $step), $code)) {    // constant-time compare
                return $step;
            }
        }

        return null;
    }

    public function uri(string $issuer, string $account, string $secret): string
    {
        return 'otpauth://totp/'.rawurlencode($issuer.':'.$account).'?secret='.$secret.'&issuer='.rawurlencode($issuer).'&algorithm=SHA1&digits=6&period=30';
    }

    private function base32Encode(string $bin): string
    {
        $bits = '';
        foreach (str_split($bin) as $c) {
            $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::ALPHABET[bindec(str_pad($chunk, 5, '0'))];
        }

        return $out;
    }

    private function base32Decode(string $s): string
    {
        $s = preg_replace('/[^A-Z2-7]/', '', strtoupper($s));
        $bits = '';
        foreach (str_split($s) as $c) {
            $bits .= str_pad(decbin(strpos(self::ALPHABET, $c)), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }

        return $out;
    }
}

/*
SYSTEM DESIGN:
Self-contained TOTP engine (RFC 6238/4226). hash_equals avoids timing leaks; step counter returned to the caller enables replay rejection; window of +-1 step handles clock skew.

DATA STRUCTURE:
Time-step counter T = floor(unix/30). HMAC-SHA1 over 8-byte big-endian counter -> 31-bit dynamic truncation -> mod 10^6. Base32 = 5-bit groups <-> 8-bit bytes via bit-string.
*/
