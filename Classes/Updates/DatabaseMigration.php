<?php

declare(strict_types=1);

namespace Q23\MfaEmail\Updates;

use Q23\MfaEmail\Utility\LocalizationHelper;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Upgrade Wizard that creates the required fe_users columns for email MFA.
 *
 * This is registered as an Install Tool upgrade wizard AND can also be
 * triggered manually via the Upgrade Wizards module in the TYPO3 backend.
 *
 * Additionally, the middleware itself calls ensureColumnsExist() on first
 * request as a failsafe.
 */
class DatabaseMigration implements UpgradeWizardInterface
{
    private const TABLE = 'fe_users';

    /**
     * Column definitions: name => SQL definition
     */
    private const COLUMNS = [
        'tx_dpvmfaemail_enabled' => "tinyint(1) unsigned DEFAULT '0' NOT NULL",
        'tx_dpvmfaemail_code' => "varchar(255) DEFAULT '' NOT NULL",
        'tx_dpvmfaemail_code_tstamp' => "int(11) unsigned DEFAULT '0' NOT NULL",
        'tx_dpvmfaemail_attempts' => "int(11) unsigned DEFAULT '0' NOT NULL",
        'tx_dpvmfaemail_last_attempt' => "int(11) unsigned DEFAULT '0' NOT NULL",
    ];

    public function getIdentifier(): string
    {
        return 'mfaEmailDbMigration';
    }

    public function getTitle(): string
    {
        return LocalizationHelper::translateForBackend('upgradeWizard.title');
    }

    public function getDescription(): string
    {
        return LocalizationHelper::translateForBackend('upgradeWizard.description');
    }

    public function getPrerequisites(): array
    {
        return [];
    }

    /**
     * Check if any required column is missing.
     */
    public function updateNecessary(): bool
    {
        return count($this->getMissingColumns()) > 0;
    }

    /**
     * Execute the migration: add all missing columns.
     */
    public function executeUpdate(): bool
    {
        return $this->ensureColumnsExist();
    }

    /**
     * Public static method that can be called from anywhere (middleware, ext_localconf, etc.)
     * to ensure all columns exist. Safe to call multiple times.
     *
     * @return bool true if all columns exist (or were created), false on error
     */
    public function ensureColumnsExist(): bool
    {
        $missing = $this->getMissingColumns();
        if (empty($missing)) {
            return true;
        }

        try {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable(self::TABLE);

            foreach ($missing as $columnName => $columnDef) {
                $connection->executeStatement(
                    sprintf(
                        'ALTER TABLE %s ADD COLUMN %s %s',
                        $connection->quoteIdentifier(self::TABLE),
                        $connection->quoteIdentifier($columnName),
                        $columnDef
                    )
                );
            }

            return true;
        } catch (\Throwable $e) {
            // Log but don't crash — the middleware will show an error if columns are missing
            if (isset($GLOBALS['BE_USER'])) {
                // In backend context, re-throw so the wizard shows the error
                throw $e;
            }
            return false;
        }
    }

    /**
     * @return array<string, string> Column name => SQL definition for missing columns
     */
    private function getMissingColumns(): array
    {
        try {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable(self::TABLE);

            $schemaManager = $connection->createSchemaManager();
            $existingColumns = $schemaManager->listTableColumns(self::TABLE);

            $existingNames = array_map(
                static fn($col) => strtolower($col->getName()),
                $existingColumns
            );

            $missing = [];
            foreach (self::COLUMNS as $name => $definition) {
                if (!in_array(strtolower($name), $existingNames, true)) {
                    $missing[$name] = $definition;
                }
            }

            return $missing;
        } catch (\Throwable $e) {
            // If we can't even check, assume all are missing
            return self::COLUMNS;
        }
    }
}
