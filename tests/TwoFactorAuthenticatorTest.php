<?php

namespace Laravel\Ui\Tests;

use Laravel\Ui\Auth\TwoFactorAuthenticator;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TwoFactorAuthenticatorTest extends TestCase
{
    #[Test]
    public function it_verifies_totp_codes_using_rfc_6238_values()
    {
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

        $this->assertTrue(TwoFactorAuthenticator::verify($secret, '287082', 59));
        $this->assertFalse(TwoFactorAuthenticator::verify($secret, '123456', 59));
        $this->assertFalse(TwoFactorAuthenticator::verify($secret, 'recovery-code', 59));
    }

    #[Test]
    public function it_generates_base32_secrets_and_unique_recovery_codes()
    {
        $secret = TwoFactorAuthenticator::generateSecret();
        $codes = TwoFactorAuthenticator::generateRecoveryCodes();

        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $secret);
        $this->assertCount(8, $codes);
        $this->assertCount(8, array_unique($codes));

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[A-F0-9]{4}-[A-F0-9]{4}$/', $code);
        }
    }

    #[Test]
    public function it_builds_an_authenticator_provisioning_uri()
    {
        $uri = TwoFactorAuthenticator::provisioningUri(
            'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',
            'person@example.com',
            'Example App'
        );

        $this->assertStringStartsWith('otpauth://totp/Example%20App%3Aperson%40example.com?', $uri);
        $this->assertStringContainsString('secret=GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', $uri);
        $this->assertStringContainsString('issuer=Example%20App', $uri);
    }

    #[Test]
    public function recovery_codes_are_hashed_and_consumed_only_once()
    {
        $codes = ['A1B2-C3D4', 'E5F6-A7B8'];
        $hashedCodes = TwoFactorAuthenticator::hashRecoveryCodes($codes);

        $this->assertCount(2, $hashedCodes);
        $this->assertNotSame($codes[0], $hashedCodes[0]);
        $remainingCodes = TwoFactorAuthenticator::consumeRecoveryCode($hashedCodes, 'a1b2 c3d4');

        $this->assertSame([$hashedCodes[1]], $remainingCodes);
        $this->assertNull(TwoFactorAuthenticator::consumeRecoveryCode($remainingCodes, $codes[0]));
        $this->assertSame([], TwoFactorAuthenticator::consumeRecoveryCode([$hashedCodes[0]], $codes[0]));
    }
}
