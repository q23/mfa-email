# mfa_email — Email Two-Factor Authentication for TYPO3

[![TYPO3](https://img.shields.io/badge/TYPO3-12.4-orange.svg)](https://typo3.org)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green.svg)](LICENSE)
[![Packagist](https://img.shields.io/packagist/v/q23/mfa-email)](https://packagist.org/packages/q23/mfa-email)

A TYPO3 12.4 extension that adds **email-based two-factor authentication** to the
frontend login. After a user logs in with username and password, a 6-digit code is
sent to their registered email address. The user must enter this code to complete login.

## Features

- **PSR-15 middleware** — integrates cleanly into the TYPO3 request pipeline
- **Global or per-user toggle** — enable for everyone or selectively per `fe_users` record
- **Brute-force protection** — lockout after 5 failed attempts (15 minutes)
- **Time-limited codes** — expire after 6 minutes
- **Bcrypt storage** — codes are never stored in plaintext
- **Single-use codes** — immediately invalidated after successful verification
- **Auto-migration** — required database fields are created on first load
- **Configurable branding** — site name, email subject, and signature via extension settings
- **No dependencies** — uses only TYPO3 core APIs

## Quick Start

```bash
composer require q23/mfa-email
```

1. Flush caches: **Maintenance → Flush all caches**
2. Go to **Admin Tools → Settings → Extension Configuration → mfa_email**
3. Enable **"Enable 2FA for all frontend users"** — or configure per user

## Requirements

- TYPO3 12.4.x
- `felogin` system extension (included with TYPO3)
- Working TYPO3 mail configuration (`$GLOBALS['TYPO3_CONF_VARS']['MAIL']`)

## Documentation

- [Installation](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Security Architecture](docs/security.md)
- [DSGVO / GDPR Notes](docs/dsgvo.md)

## How It Works

1. User submits username and password via `felogin`
2. TYPO3's authentication middleware authenticates the credentials
3. This extension's middleware intercepts the request
4. A 6-digit code is generated, bcrypt-hashed, and stored; the plaintext code is emailed
5. The user enters the code in the verification form
6. On success: session is marked as verified, user is redirected (303) to the original page
7. The code is immediately deleted from the database

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).
For security vulnerabilities, see [SECURITY.md](SECURITY.md) — please do not use public issues.

## License

GPL-2.0-or-later — see [LICENSE](LICENSE).

Developed by [q23.medien GmbH](https://q23.de).
