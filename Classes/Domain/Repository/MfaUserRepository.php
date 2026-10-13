<?php

declare(strict_types=1);

namespace Q23\MfaEmail\Domain\Repository;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Reads and writes the MFA fields of fe_users.
 */
class MfaUserRepository
{
    private const TABLE = 'fe_users';

    public function __construct(private readonly ConnectionPool $connectionPool)
    {
    }

    /**
     * @return array<string, int|string>|null
     */
    public function findMfaData(int $feUserUid): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $row = $queryBuilder
            ->select(
                'tx_dpvmfaemail_enabled',
                'tx_dpvmfaemail_code',
                'tx_dpvmfaemail_code_tstamp',
                'tx_dpvmfaemail_attempts',
                'tx_dpvmfaemail_last_attempt'
            )
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($feUserUid, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();

        return $row ?: null;
    }

    /**
     * @param array<string, int|string> $fields
     */
    public function update(int $feUserUid, array $fields): void
    {
        $this->connectionPool
            ->getConnectionForTable(self::TABLE)
            ->update(self::TABLE, $fields, ['uid' => $feUserUid]);
    }
}
