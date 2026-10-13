<?php

declare(strict_types=1);

namespace Q23\MfaEmail\Configuration;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/**
 * Typed access to the extension configuration of q23_mfa_email.
 */
class MfaSettings
{
    public function __construct(private readonly ExtensionConfiguration $extensionConfiguration)
    {
    }

    public function isEnabledForAll(): bool
    {
        try {
            $configuration = $this->extensionConfiguration->get('q23_mfa_email');
        } catch (\Throwable) {
            return false;
        }

        return is_array($configuration) && !empty($configuration['enableForAll']);
    }
}
