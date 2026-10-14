<?php

declare(strict_types=1);

namespace Q23\MfaEmail\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Q23\MfaEmail\Service\CodeService;
use Q23\MfaEmail\Tests\Unit\Fixtures\FixedMfaSettings;
use Q23\MfaEmail\Tests\Unit\Fixtures\InMemoryMfaUserRepository;

final class CodeServiceTest extends TestCase
{
    private InMemoryMfaUserRepository $repository;
    private CodeService $subject;

    protected function setUp(): void
    {
        $this->repository = new InMemoryMfaUserRepository();
        $this->repository->rows[1] = [
            'tx_dpvmfaemail_enabled' => 0,
            'tx_dpvmfaemail_code' => '',
            'tx_dpvmfaemail_code_tstamp' => 0,
            'tx_dpvmfaemail_attempts' => 0,
            'tx_dpvmfaemail_last_attempt' => 0,
        ];
        $this->subject = new CodeService($this->repository, new FixedMfaSettings(false));
    }

    #[Test]
    public function generateCodeStoresHashAndTimestamp(): void
    {
        $code = $this->subject->generateCode(1);
        self::assertMatchesRegularExpression('/^\d{6}$/', $code);
        self::assertTrue(password_verify($code, (string)$this->repository->rows[1]['tx_dpvmfaemail_code']));
        self::assertEqualsWithDelta(time(), (int)$this->repository->rows[1]['tx_dpvmfaemail_code_tstamp'], 2);
    }

    #[Test]
    public function verifyCodeAcceptsCorrectCodeOnlyOnceAndResetsAttempts(): void
    {
        $this->repository->rows[1]['tx_dpvmfaemail_attempts'] = 2;
        $code = $this->subject->generateCode(1);
        self::assertTrue($this->subject->verifyCode(1, $code));
        self::assertSame('', $this->repository->rows[1]['tx_dpvmfaemail_code']);
        self::assertSame(0, $this->repository->rows[1]['tx_dpvmfaemail_attempts']);
        self::assertFalse($this->subject->verifyCode(1, $code));
    }

    #[Test]
    public function verifyCodeRejectsWrongCodeAndCountsAttempt(): void
    {
        $code = $this->subject->generateCode(1);
        $wrong = $code === '000000' ? '111111' : '000000';
        self::assertFalse($this->subject->verifyCode(1, $wrong));
        self::assertSame(1, $this->repository->rows[1]['tx_dpvmfaemail_attempts']);
    }

    #[Test]
    public function verifyCodeRejectsExpiredCodeAndInvalidatesIt(): void
    {
        $code = $this->subject->generateCode(1);
        $this->repository->rows[1]['tx_dpvmfaemail_code_tstamp'] = time() - CodeService::CODE_VALIDITY_SECONDS - 1;
        self::assertFalse($this->subject->verifyCode(1, $code));
        self::assertSame('', $this->repository->rows[1]['tx_dpvmfaemail_code']);
        self::assertSame(1, $this->repository->rows[1]['tx_dpvmfaemail_attempts']);
    }

    #[Test]
    public function verifyCodeRejectsBlankInputAndCountsAttempt(): void
    {
        $this->subject->generateCode(1);
        self::assertFalse($this->subject->verifyCode(1, '  '));
        self::assertSame(1, $this->repository->rows[1]['tx_dpvmfaemail_attempts']);
    }

    #[Test]
    public function isLockedAfterMaxAttemptsWithinLockout(): void
    {
        $this->repository->rows[1]['tx_dpvmfaemail_attempts'] = CodeService::MAX_ATTEMPTS;
        $this->repository->rows[1]['tx_dpvmfaemail_last_attempt'] = time();
        self::assertTrue($this->subject->isLocked(1));
    }

    #[Test]
    public function lockExpiresAndResetsAttempts(): void
    {
        $this->repository->rows[1]['tx_dpvmfaemail_attempts'] = CodeService::MAX_ATTEMPTS;
        $this->repository->rows[1]['tx_dpvmfaemail_last_attempt'] = time() - CodeService::LOCKOUT_SECONDS - 1;
        self::assertFalse($this->subject->isLocked(1));
        self::assertSame(0, $this->repository->rows[1]['tx_dpvmfaemail_attempts']);
    }

    #[Test]
    public function remainingSecondsIsZeroWithoutPendingCode(): void
    {
        self::assertSame(0, $this->subject->getRemainingSeconds(1));
    }

    #[Test]
    public function remainingSecondsForFreshCode(): void
    {
        $this->subject->generateCode(1);
        $remaining = $this->subject->getRemainingSeconds(1);
        self::assertGreaterThan(CodeService::CODE_VALIDITY_SECONDS - 3, $remaining);
        self::assertLessThanOrEqual(CodeService::CODE_VALIDITY_SECONDS, $remaining);
    }

    #[Test]
    public function mfaEnabledFollowsUserFlag(): void
    {
        self::assertFalse($this->subject->isMfaEnabled(1));
        $this->repository->rows[1]['tx_dpvmfaemail_enabled'] = 1;
        self::assertTrue($this->subject->isMfaEnabled(1));
    }

    #[Test]
    public function mfaEnabledForAllOverridesUserFlag(): void
    {
        $subject = new CodeService($this->repository, new FixedMfaSettings(true));
        self::assertTrue($subject->isMfaEnabled(1));
    }
}
