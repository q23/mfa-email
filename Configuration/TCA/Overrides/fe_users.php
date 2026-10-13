<?php

defined('TYPO3') or die();

/**
 * Add the per-user MFA checkbox to fe_users records in the TYPO3 backend.
 * Global MFA enforcement from extension configuration takes precedence.
 */

$temporaryColumns = [
    'tx_dpvmfaemail_enabled' => [
        'exclude' => true,
        'label' => 'LLL:EXT:q23_mfa_email/Resources/Private/Language/locallang_db.xlf:fe_users.tx_dpvmfaemail_enabled',
        'description' => 'LLL:EXT:q23_mfa_email/Resources/Private/Language/locallang_db.xlf:fe_users.tx_dpvmfaemail_enabled.description',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'items' => [
                [
                    'label' => '',
                    'invertStateDisplay' => false,
                ],
            ],
        ],
    ],
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users', $temporaryColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'fe_users',
    'tx_dpvmfaemail_enabled',
    '',
    'after:disable'
);
