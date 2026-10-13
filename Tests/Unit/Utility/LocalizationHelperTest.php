<?php

declare(strict_types=1);

namespace Q23\MfaEmail\Tests\Unit\Utility;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Q23\MfaEmail\Utility\LocalizationHelper;

final class LocalizationHelperTest extends TestCase
{
    #[Test]
    public function languageTagPrefersHreflang(): void
    {
        self::assertSame('de-AT', LocalizationHelper::languageTag('de-AT', 'de'));
    }

    #[Test]
    public function languageTagFallsBackToLanguageCode(): void
    {
        self::assertSame('de', LocalizationHelper::languageTag('', 'de'));
    }

    #[Test]
    public function languageTagFallsBackToEnglish(): void
    {
        self::assertSame('en', LocalizationHelper::languageTag('', ''));
    }

    #[Test]
    public function languageTagEscapesHtml(): void
    {
        self::assertSame('de&quot;x', LocalizationHelper::languageTag('de"x', ''));
    }
}
