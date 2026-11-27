<?php
namespace Tests\Unit;

use App\Services\Totp;
use PHPUnit\Framework\TestCase;

class TotpTest extends TestCase
{
    private const RFC_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';     // ASCII "12345678901234567890"

    public function test_matches_rfc6238_vectors(): void
    {
        $t = new Totp();
        $this->assertSame('287082', $t->codeAt(self::RFC_SECRET, intdiv(59, 30)));
        $this->assertSame('081804', $t->codeAt(self::RFC_SECRET, intdiv(1111111109, 30)));
        $this->assertSame('050471', $t->codeAt(self::RFC_SECRET, intdiv(1111111111, 30)));
    }

    public function test_verify_window_and_replay_protection(): void
    {
        $t = new Totp();
        $time = 1111111109;
        $step = intdiv($time, 30);

        $this->assertSame($step, $t->verify(self::RFC_SECRET, $t->codeAt(self::RFC_SECRET, $step), null, 1, $time));
        $this->assertSame($step + 1, $t->verify(self::RFC_SECRET, $t->codeAt(self::RFC_SECRET, $step + 1), null, 1, $time));   // drift
        $this->assertNull($t->verify(self::RFC_SECRET, $t->codeAt(self::RFC_SECRET, $step + 5), null, 1, $time));              // out of window
        $this->assertNull($t->verify(self::RFC_SECRET, $t->codeAt(self::RFC_SECRET, $step), $step, 1, $time));                  // replay
        $this->assertNull($t->verify(self::RFC_SECRET, 'abc123', null, 1, $time));
    }

    public function test_generated_secret_is_valid_base32_and_unique(): void
    {
        $t = new Totp();
        $a = $t->generateSecret();
        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $a);
        $this->assertNotSame($a, $t->generateSecret());
    }
}

/*
SYSTEM DESIGN:
Deterministic RFC 6238 test vectors prove the TOTP implementation is interoperable with Google/Microsoft Authenticator.

DATA STRUCTURE:

*/
