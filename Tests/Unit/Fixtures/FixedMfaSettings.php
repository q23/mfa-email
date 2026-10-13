<?php

declare(strict_types=1);

namespace Q23\MfaEmail\Tests\Unit\Fixtures;

use Q23\MfaEmail\Configuration\MfaSettings;

final class FixedMfaSettings extends MfaSettings
{
    public function __construct(private readonly bool $enabledForAll)
    {
    }

    public function isEnabledForAll(): bool
    {
        return $this->enabledForAll;
    }
}
