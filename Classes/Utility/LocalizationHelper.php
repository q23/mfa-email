<?php

declare(strict_types=1);

namespace Q23\MfaEmail\Utility;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class LocalizationHelper
{
    private const LANGUAGE_FILE_PREFIX = 'LLL:EXT:mfa_email/Resources/Private/Language/locallang.xlf:';

    public static function translateForRequest(
        ServerRequestInterface $request,
        string $key,
        array $arguments = []
    ): string {
        $languageServiceFactory = GeneralUtility::makeInstance(LanguageServiceFactory::class);
        $siteLanguage = $request->getAttribute('language');

        if ($siteLanguage instanceof SiteLanguage) {
            $languageService = $languageServiceFactory->createFromSiteLanguage($siteLanguage);
        } else {
            $site = $request->getAttribute('site');
            $defaultLanguage = is_object($site) && method_exists($site, 'getDefaultLanguage')
                ? $site->getDefaultLanguage()
                : null;
            $languageService = $defaultLanguage instanceof SiteLanguage
                ? $languageServiceFactory->createFromSiteLanguage($defaultLanguage)
                : $languageServiceFactory->create('default');
        }

        return self::translate($languageService, $key, $arguments);
    }

    public static function translateForBackend(string $key, array $arguments = []): string
    {
        $languageService = GeneralUtility::makeInstance(LanguageServiceFactory::class)
            ->createFromUserPreferences($GLOBALS['BE_USER'] ?? null);

        return self::translate($languageService, $key, $arguments);
    }

    public static function getHtmlLanguageForRequest(ServerRequestInterface $request): string
    {
        $siteLanguage = $request->getAttribute('language');
        if ($siteLanguage instanceof SiteLanguage) {
            $languageTag = $siteLanguage->getHreflang();
            if ($languageTag !== '') {
                return htmlspecialchars($languageTag);
            }

            $isoCode = $siteLanguage->getTwoLetterIsoCode();
            if ($isoCode !== '') {
                return htmlspecialchars($isoCode);
            }
        }

        return 'en';
    }

    private static function translate(LanguageService $languageService, string $key, array $arguments = []): string
    {
        $translation = $languageService->sL(self::LANGUAGE_FILE_PREFIX . $key);

        if ($arguments === []) {
            return $translation;
        }

        try {
            return vsprintf($translation, $arguments);
        } catch (\Throwable $e) {
            return $translation;
        }
    }
}
