# DSGVO / GDPR Compliance Notes

This document describes how this extension processes personal data.
It is intended to help site operators complete their Records of Processing Activities
(Verzeichnis von Verarbeitungstätigkeiten, Art. 30 DSGVO).

## Processed Personal Data

| Data | Purpose | Storage location |
|---|---|---|
| Email address | Delivery of the verification code | Not stored — read from `fe_users`, used only for sending |
| Bcrypt hash of 6-digit code | Verification of user-submitted code | `fe_users.tx_dpvmfaemail_code` |
| Code generation timestamp | Expiry enforcement (6-minute window) | `fe_users.tx_dpvmfaemail_code_tstamp` |
| Failed attempt counter | Brute-force protection | `fe_users.tx_dpvmfaemail_attempts` |
| Last failed attempt timestamp | Lockout duration enforcement | `fe_users.tx_dpvmfaemail_last_attempt` |

## What Is NOT Stored

- The plaintext verification code (never persisted, only held in memory briefly)
- The user's email address (only read from `fe_users`, not stored again)
- Any device, browser, or location data

## Legal Basis

**Art. 6(1)(f) DSGVO / GDPR — Legitimate interest**

The processing is necessary to protect user accounts from unauthorised access.
Two-factor authentication is a widely recognised security measure that is proportionate
to the risk of account compromise.

Depending on your site's context, **Art. 6(1)(b)** (performance of a contract)
or **Art. 6(1)(a)** (consent, if 2FA is opt-in per user) may also apply.

## Retention / Automatic Deletion

| Data | Deleted when |
|---|---|
| Verification code hash | Immediately after successful verification; also cleared after 6-minute expiry on the next attempt |
| Code generation timestamp | Same as above |
| Failed attempt counter | Reset to 0 after successful verification, or after the 15-minute lockout expires |
| Last attempt timestamp | Reset to 0 after lockout expires |

All data is cleared automatically by the extension — no cron job or manual cleanup is required.

## Third Parties / Data Processors

This extension uses **no external services**.

Verification emails are sent via the TYPO3 mail configuration, which typically uses
an SMTP provider configured by the site operator. The operator is responsible for
documenting that SMTP provider as a separate data processor in their records.

## Suggested Privacy Policy Notice

Sites using this extension should include a notice such as:

> "When you log in, a one-time verification code is sent to your registered email
> address for security purposes. This code is valid for 6 minutes and is not stored
> in readable form. No additional personal data is collected for this purpose."
