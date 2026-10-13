<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Email 2FA',
    'description' => 'Email-based two-factor authentication for TYPO3 frontend users. Sends a 6-digit code after login. Standalone – no additional extensions required.',
    'category' => 'services',
    'author' => 'q23.medien GmbH',
    'author_email' => 'technik@q23.de',
    'author_company' => 'q23.medien GmbH',
    'state' => 'stable',
    'version' => '1.1.0',
    'constraints' => [
        'depends' => [
            'php' => '8.1.0-8.4.99',
            'typo3' => '12.4.0-13.4.99',
            'felogin' => '12.4.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
