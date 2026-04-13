<?php

declare(strict_types=1);

namespace Q23\MfaEmail\Service;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handles generation, storage, and verification of MFA codes.
 *
 * Codes are stored as bcrypt hashes in the fe_users table (field: tx_dpvmfaemail_code).
 * Each code is single-use and time-limited.
 */
class CodeService
{
    /**
     * Code validity in seconds (6 minutes)
     */
    public const CODE_VALIDITY_SECONDS = 360;

    /**
     * Maximum failed attempts before temporary lockout
     */
    public const MAX_ATTEMPTS = 5;

    /**
     * Lockout duration in seconds (15 minutes)
     */
    public const LOCKOUT_SECONDS = 900;

    /**
     * Generate a 6-digit code, hash it, and store it for the user.
     *
     * @return string The plaintext code (to be sent via email)
     */
    public function generateCode(int $feUserUid): string
    {
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('fe_users');

        $connection->update(
            'fe_users',
            [
                'tx_dpvmfaemail_code' => password_hash($code, PASSWORD_DEFAULT),
                'tx_dpvmfaemail_code_tstamp' => time(),
            ],
            ['uid' => $feUserUid]
        );

        return $code;
    }

    /**
     * Verify a submitted code against the stored hash.
     * On success, the code is immediately invalidated (single-use).
     */
    public function verifyCode(int $feUserUid, string $submittedCode): bool
    {
        if (trim($submittedCode) === '') {
            $this->incrementAttempts($feUserUid);
            return false;
        }

        $userData = $this->getUserMfaData($feUserUid);
        if ($userData === null) {
            return false;
        }

        $storedHash = (string)($userData['tx_dpvmfaemail_code'] ?? '');
        $codeTimestamp = (int)($userData['tx_dpvmfaemail_code_tstamp'] ?? 0);

        // No code stored
        if ($storedHash === '' || $codeTimestamp === 0) {
            $this->incrementAttempts($feUserUid);
            return false;
        }

        // Code expired
        if ((time() - $codeTimestamp) > self::CODE_VALIDITY_SECONDS) {
            $this->invalidateCode($feUserUid);
            $this->incrementAttempts($feUserUid);
            return false;
        }

        // Wrong code
        if (!password_verify(trim($submittedCode), $storedHash)) {
            $this->incrementAttempts($feUserUid);
            return false;
        }

        // SUCCESS - immediately invalidate (prevents CVE-2026-4208 style bypass)
        $this->invalidateCode($feUserUid);
        $this->resetAttempts($feUserUid);

        return true;
    }

    /**
     * Check if the user is locked out due to too many failed attempts.
     */
    public function isLocked(int $feUserUid): bool
    {
        $userData = $this->getUserMfaData($feUserUid);
        if ($userData === null) {
            return false;
        }

        $attempts = (int)($userData['tx_dpvmfaemail_attempts'] ?? 0);
        if ($attempts < self::MAX_ATTEMPTS) {
            return false;
        }

        $lastAttempt = (int)($userData['tx_dpvmfaemail_last_attempt'] ?? 0);
        if (time() > ($lastAttempt + self::LOCKOUT_SECONDS)) {
            $this->resetAttempts($feUserUid);
            return false;
        }

        return true;
    }

    /**
     * Check if MFA is enabled for this user.
     *
     * If the global setting "enableForAll" is active, MFA applies to ALL frontend users.
     * Otherwise, only users with the per-user checkbox (tx_dpvmfaemail_enabled) are affected.
     */
    public function isMfaEnabled(int $feUserUid): bool
    {
        // Check global toggle first
        try {
            $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)
                ->get('q23_mfa_email');
            if (!empty($extConf['enableForAll'])) {
                return true;
            }
        } catch (\Throwable $e) {
            // Extension configuration not available — fall through to per-user check
        }

        // Per-user check
        $userData = $this->getUserMfaData($feUserUid);
        return (bool)($userData['tx_dpvmfaemail_enabled'] ?? false);
    }

    /**
     * Get remaining seconds until code expires.
     */
    public function getRemainingSeconds(int $feUserUid): int
    {
        $userData = $this->getUserMfaData($feUserUid);
        if ($userData === null) {
            return 0;
        }
        $codeTimestamp = (int)($userData['tx_dpvmfaemail_code_tstamp'] ?? 0);
        return max(0, self::CODE_VALIDITY_SECONDS - (time() - $codeTimestamp));
    }

    private function invalidateCode(int $feUserUid): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('fe_users');

        $connection->update(
            'fe_users',
            [
                'tx_dpvmfaemail_code' => '',
                'tx_dpvmfaemail_code_tstamp' => 0,
            ],
            ['uid' => $feUserUid]
        );
    }

    private function incrementAttempts(int $feUserUid): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('fe_users');

        $userData = $this->getUserMfaData($feUserUid);
        $attempts = (int)($userData['tx_dpvmfaemail_attempts'] ?? 0);

        $connection->update(
            'fe_users',
            [
                'tx_dpvmfaemail_attempts' => $attempts + 1,
                'tx_dpvmfaemail_last_attempt' => time(),
            ],
            ['uid' => $feUserUid]
        );
    }

    private function resetAttempts(int $feUserUid): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('fe_users');

        $connection->update(
            'fe_users',
            [
                'tx_dpvmfaemail_attempts' => 0,
                'tx_dpvmfaemail_last_attempt' => 0,
            ],
            ['uid' => $feUserUid]
        );
    }

    private function getUserMfaData(int $feUserUid): ?array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('fe_users');

        $row = $queryBuilder
            ->select(
                'tx_dpvmfaemail_enabled',
                'tx_dpvmfaemail_code',
                'tx_dpvmfaemail_code_tstamp',
                'tx_dpvmfaemail_attempts',
                'tx_dpvmfaemail_last_attempt'
            )
            ->from('fe_users')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($feUserUid, \PDO::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();

        return $row ?: null;
    }
}
