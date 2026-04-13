<?php

declare(strict_types=1);

namespace Q23\MfaEmail\Service;

use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Sends MFA verification codes via email using TYPO3's mail API.
 */
class MailService
{
    /**
     * Send a verification code to the user's email address.
     */
    public function sendCode(string $email, string $name, string $code, int $validMinutes): bool
    {
        if ($email === '') {
            return false;
        }

        $subject = 'DPV-PSA: Ihr Anmelde-Bestaetigungscode';

        $textBody = sprintf(
            "Guten Tag%s,\n\n"
            . "Ihr Bestaetigungscode fuer die Anmeldung bei DPV-PSA lautet:\n\n"
            . "    %s\n\n"
            . "Dieser Code ist %d Minuten gueltig.\n\n"
            . "Falls Sie diese Anmeldung nicht durchgefuehrt haben, "
            . "ignorieren Sie bitte diese E-Mail und aendern Sie "
            . "sicherheitshalber Ihr Passwort.\n\n"
            . "Mit freundlichen Gruessen\n"
            . "DPV - Deutsche Psychoanalytische Vereinigung",
            $name !== '' ? ' ' . $name : '',
            $code,
            $validMinutes
        );

        $htmlBody = sprintf(
            '<!DOCTYPE html>'
            . '<html><head><meta charset="utf-8"></head>'
            . '<body style="font-family: Arial, Helvetica, sans-serif; color: #333; '
            . 'max-width: 500px; margin: 0 auto; padding: 20px;">'
            . '<div style="border-bottom: 3px solid #003366; padding-bottom: 15px; margin-bottom: 20px;">'
            . '<strong style="color: #003366; font-size: 18px;">DPV-PSA</strong>'
            . '</div>'
            . '<p>Guten Tag%s,</p>'
            . '<p>Ihr Bestaetigungscode fuer die Anmeldung lautet:</p>'
            . '<div style="font-size: 36px; font-weight: bold; letter-spacing: 10px; '
            . 'text-align: center; padding: 25px; background: #f0f4f8; '
            . 'border: 2px solid #003366; border-radius: 8px; margin: 25px 0; '
            . 'color: #003366;">%s</div>'
            . '<p>Dieser Code ist <strong>%d Minuten</strong> gueltig.</p>'
            . '<hr style="border: none; border-top: 1px solid #ddd; margin: 25px 0;">'
            . '<p style="color: #888; font-size: 12px;">Falls Sie diese Anmeldung '
            . 'nicht durchgefuehrt haben, ignorieren Sie bitte diese E-Mail '
            . 'und aendern Sie sicherheitshalber Ihr Passwort.</p>'
            . '</body></html>',
            $name !== '' ? ' ' . htmlspecialchars($name) : '',
            htmlspecialchars($code),
            $validMinutes
        );

        try {
            $mail = GeneralUtility::makeInstance(MailMessage::class);
            $mail
                ->to(new Address($email, $name))
                ->subject($subject)
                ->text($textBody)
                ->html($htmlBody)
                ->send();
            return true;
        } catch (\Throwable $e) {
            GeneralUtility::makeInstance(LogManager::class)
                ->getLogger(self::class)
                ->error('Failed to send MFA verification email', [
                    'exception' => $e->getMessage(),
                    'email' => $email,
                ]);
            return false;
        }
    }
}
