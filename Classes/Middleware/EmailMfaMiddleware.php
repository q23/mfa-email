<?php

declare(strict_types=1);

namespace Q23\MfaEmail\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Q23\MfaEmail\Service\CodeService;
use Q23\MfaEmail\Service\MailService;
use Q23\MfaEmail\Updates\DatabaseMigration;
use Q23\MfaEmail\Utility\LocalizationHelper;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * PSR-15 Middleware that adds email-based 2FA to the frontend login flow.
 *
 * Flow:
 * 1. User logs in with username/password (handled by felogin)
 * 2. This middleware detects the fresh login
 * 3. It generates a 6-digit code, sends it via email, and shows the entry form
 * 4. User submits the code
 * 5. If valid: session continues normally. If invalid: user stays on the form.
 *
 * The middleware uses a session variable 'tx_dpvmfaemail_verified' to track
 * whether the user has completed the 2FA step in the current session.
 */
class EmailMfaMiddleware implements MiddlewareInterface
{
    private CodeService $codeService;
    private MailService $mailService;

    public function __construct(CodeService $codeService, MailService $mailService)
    {
        $this->codeService = $codeService;
        $this->mailService = $mailService;
    }

    private static bool $dbChecked = false;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Failsafe: ensure DB columns exist on first request
        if (!self::$dbChecked) {
            self::$dbChecked = true;
            try {
                GeneralUtility::makeInstance(DatabaseMigration::class)->ensureColumnsExist();
            } catch (\Throwable $e) {
                // Silently continue — columns might already exist
            }
        }

        /** @var FrontendUserAuthentication|null $feUser */
        $feUser = $request->getAttribute('frontend.user');

        // No frontend user object or not logged in -> pass through
        if ($feUser === null || !isset($feUser->user['uid']) || (int)$feUser->user['uid'] === 0) {
            return $handler->handle($request);
        }

        $feUserUid = (int)$feUser->user['uid'];

        // Check if MFA is enabled for this user
        if (!$this->codeService->isMfaEnabled($feUserUid)) {
            return $handler->handle($request);
        }

        // Check if already verified in this session
        $sessionData = $feUser->getSessionData('tx_dpvmfaemail');
        if (is_array($sessionData) && ($sessionData['verified'] ?? false) === true) {
            return $handler->handle($request);
        }

        // User is logged in, MFA enabled, but NOT yet verified -> handle MFA
        return $this->handleMfa($request, $feUser, $feUserUid);
    }

    private function handleMfa(
        ServerRequestInterface $request,
        FrontendUserAuthentication $feUser,
        int $feUserUid
    ): ResponseInterface {
        // Check lockout
        if ($this->codeService->isLocked($feUserUid)) {
            return new HtmlResponse($this->renderPage($request, $this->getLockedHtml($request)));
        }

        $parsedBody = $request->getParsedBody() ?? [];
        $submittedCode = $parsedBody['tx_dpvmfaemail_code'] ?? null;
        $isResend = ($parsedBody['tx_dpvmfaemail_resend'] ?? '') === '1';

        // User submitted a code -> verify
        if ($submittedCode !== null && is_string($submittedCode) && !$isResend) {
            if ($this->codeService->verifyCode($feUserUid, $submittedCode)) {
                // Mark as verified in session
                $feUser->setSessionData('tx_dpvmfaemail', ['verified' => true]);
                // Store session to persist the flag
                $feUser->storeSessionData();

                // Redirect to the originally requested page (prevent form resubmission)
                $redirectUrl = $parsedBody['tx_dpvmfaemail_redirect'] ?? '/';
                return new \TYPO3\CMS\Core\Http\RedirectResponse($redirectUrl, 303);
            }

            // Verification failed
            $remaining = $this->codeService->getRemainingSeconds($feUserUid);
            return new HtmlResponse(
                $this->renderPage(
                    $request,
                    $this->getCodeFormHtml($request, true, $remaining, $request->getUri()->getPath())
                )
            );
        }

        // No code submitted or resend requested -> generate and send code
        $email = $feUser->user['email'] ?? '';
        $name = trim(($feUser->user['first_name'] ?? '') . ' ' . ($feUser->user['last_name'] ?? ''));

        $code = $this->codeService->generateCode($feUserUid);
        $validMinutes = (int)ceil(CodeService::CODE_VALIDITY_SECONDS / 60);
        $this->mailService->sendCode($request, $email, $name, $code, $validMinutes);

        $remaining = CodeService::CODE_VALIDITY_SECONDS;
        return new HtmlResponse(
            $this->renderPage(
                $request,
                $this->getCodeFormHtml($request, false, $remaining, $request->getUri()->getPath())
            )
        );
    }

    private function getSiteName(ServerRequestInterface $request): string
    {
        try {
            $conf = GeneralUtility::makeInstance(ExtensionConfiguration::class)
                ->get('mfa_email') ?? [];
            $name = trim((string)($conf['siteName'] ?? ''));
            if ($name !== '') {
                return $name;
            }
        } catch (\Throwable $e) {
            // Fall back to the localized default below.
        }

        return LocalizationHelper::translateForRequest($request, 'defaults.siteName');
    }

    /**
     * Wrap the form content in a full HTML page.
     */
    private function renderPage(ServerRequestInterface $request, string $content): string
    {
        $siteName = $this->getSiteName($request);
        $pageTitle = htmlspecialchars(LocalizationHelper::translateForRequest($request, 'page.title', [$siteName]));
        $htmlLanguage = LocalizationHelper::getHtmlLanguageForRequest($request);
        return <<<HTML
<!DOCTYPE html>
<html lang="{$htmlLanguage}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{$pageTitle}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
            background: #f0f2f5;
            color: #333;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .mfa-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            max-width: 440px;
            width: 100%;
            padding: 40px;
        }
        .mfa-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #003366;
        }
        .mfa-header h1 {
            color: #003366;
            font-size: 22px;
            margin-bottom: 5px;
        }
        .mfa-header p {
            color: #666;
            font-size: 14px;
        }
        .mfa-description {
            color: #555;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 25px;
            text-align: center;
        }
        .mfa-icon {
            text-align: center;
            font-size: 48px;
            margin-bottom: 15px;
        }
        .mfa-input {
            width: 100%;
            padding: 15px;
            font-size: 28px;
            letter-spacing: 12px;
            text-align: center;
            border: 2px solid #ccd0d5;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.2s;
            margin-bottom: 8px;
        }
        .mfa-input:focus {
            border-color: #003366;
            box-shadow: 0 0 0 3px rgba(0,51,102,0.1);
        }
        .mfa-timer {
            text-align: center;
            color: #888;
            font-size: 13px;
            margin-bottom: 20px;
        }
        .mfa-btn {
            width: 100%;
            padding: 14px;
            background: #003366;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .mfa-btn:hover { background: #004488; }
        .mfa-resend {
            display: block;
            text-align: center;
            margin-top: 15px;
            background: none;
            border: none;
            color: #003366;
            cursor: pointer;
            font-size: 14px;
            text-decoration: underline;
            width: 100%;
        }
        .mfa-resend:hover { color: #004488; }
        .mfa-error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
        .mfa-locked {
            text-align: center;
            padding: 30px 0;
        }
        .mfa-locked h2 { color: #b91c1c; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="mfa-container">
        {$content}
    </div>
</body>
</html>
HTML;
    }

    /**
     * HTML for the code entry form.
     */
    private function getCodeFormHtml(
        ServerRequestInterface $request,
        bool $hasError,
        int $remainingSeconds,
        string $redirectPath
    ): string
    {
        $siteName = htmlspecialchars($this->getSiteName($request));
        $errorHtml = '';
        if ($hasError) {
            $errorHtml = '<div class="mfa-error">'
                . htmlspecialchars(LocalizationHelper::translateForRequest($request, 'form.error.invalidCode'))
                . '</div>';
        }

        $minutes = (int)floor($remainingSeconds / 60);
        $seconds = $remainingSeconds % 60;
        $timerText = htmlspecialchars(LocalizationHelper::translateForRequest($request, 'form.timer', [$minutes, $seconds]));
        $escapedRedirect = htmlspecialchars($redirectPath);
        $subtitle = htmlspecialchars(LocalizationHelper::translateForRequest($request, 'form.subtitle'));
        $description = htmlspecialchars(LocalizationHelper::translateForRequest($request, 'form.description'));
        $codeLabel = htmlspecialchars(LocalizationHelper::translateForRequest($request, 'form.label.code'));
        $verifyLabel = htmlspecialchars(LocalizationHelper::translateForRequest($request, 'form.button.verify'));
        $resendLabel = htmlspecialchars(LocalizationHelper::translateForRequest($request, 'form.button.resend'));

        return <<<HTML
        <div class="mfa-header">
            <h1>{$siteName}</h1>
            <p>{$subtitle}</p>
        </div>
        <div class="mfa-icon">&#128274;</div>
        <p class="mfa-description">{$description}</p>
        {$errorHtml}
        <form method="post" action="">
            <input type="hidden" name="tx_dpvmfaemail_redirect" value="{$escapedRedirect}">
            <label for="mfa-code" style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px;">
                {$codeLabel}
            </label>
            <input type="text" name="tx_dpvmfaemail_code" id="mfa-code"
                   class="mfa-input"
                   placeholder="------"
                   maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
                   autocomplete="one-time-code" autofocus
                   required>
            <p class="mfa-timer">{$timerText}</p>
            <button type="submit" class="mfa-btn">{$verifyLabel}</button>
            <button type="submit" name="tx_dpvmfaemail_resend" value="1" class="mfa-resend">
                {$resendLabel}
            </button>
        </form>
HTML;
    }

    /**
     * HTML for the lockout message.
     */
    private function getLockedHtml(ServerRequestInterface $request): string
    {
        $siteName = htmlspecialchars($this->getSiteName($request));
        $subtitle = htmlspecialchars(LocalizationHelper::translateForRequest($request, 'form.subtitle'));
        $lockTitle = htmlspecialchars(LocalizationHelper::translateForRequest($request, 'lock.title'));
        $lockDescription = htmlspecialchars(LocalizationHelper::translateForRequest(
            $request,
            'lock.description',
            [(int)ceil(CodeService::LOCKOUT_SECONDS / 60)]
        ));
        return <<<HTML
        <div class="mfa-header">
            <h1>{$siteName}</h1>
            <p>{$subtitle}</p>
        </div>
        <div class="mfa-locked">
            <div style="font-size: 48px; margin-bottom: 15px;">&#9888;</div>
            <h2>{$lockTitle}</h2>
            <p style="color: #666; line-height: 1.6;">{$lockDescription}</p>
        </div>
HTML;
    }
}
