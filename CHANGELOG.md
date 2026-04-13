# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] – 2026-04-13

### Added
- Email-based two-factor authentication for TYPO3 12.4 frontend users
- PSR-15 middleware integrating after `typo3/cms-frontend/authentication`
- Global toggle: enable 2FA for all frontend users via extension configuration
- Per-user toggle: enable 2FA individually via backend checkbox on `fe_users`
- 6-digit codes sent via TYPO3 mail API (HTML + plaintext)
- Brute-force protection: lockout after 5 failed attempts (15 minutes)
- Time-limited codes: expire after 6 minutes
- Bcrypt code storage — never stored in plaintext
- Single-use codes: immediately invalidated after successful verification
- Auto-migration: required `fe_users` columns created automatically on first load
- Configurable site name, email subject prefix, and email signature via extension settings
- TYPO3 upgrade wizard for manual database migration

[Unreleased]: https://github.com/q23-medien/mfa-email/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/q23-medien/mfa-email/releases/tag/v1.0.0
