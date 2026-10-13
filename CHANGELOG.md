# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] – 2026-10-13

### Added
- Support for TYPO3 13.4 LTS in addition to 12.4 LTS
- Unit tests for code generation, verification, lockout and language detection

### Changed
- Database access moved to `MfaUserRepository`, extension settings to `MfaSettings`
- Upgrade wizard is registered via the `#[UpgradeWizard]` attribute
- HTML language falls back to the site language locale (no removed ISO getter)

### Removed
- Schema check and `ALTER TABLE` on every request (`ext_localconf.php` and middleware); fields are created from `ext_tables.sql` via `typo3 extension:setup`

### Upgrade notes
- After updating, run `typo3 extension:setup -e q23_mfa_email` (or Install Tool → Analyze Database Structure) so the `tx_dpvmfaemail_*` fields exist. Installations that already have the fields need no action.

## [1.0.4] – 2026-04-28

### Fixed
- Clarified the backend per-user 2FA checkbox so it no longer appears to disable MFA when the global "enable for all" setting is active.

## [1.0.3] – 2026-04-21

### Fixed
- Prevented non-form requests during a pending MFA challenge from generating new codes and invalidating the code that was just emailed.

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

[Unreleased]: https://github.com/q23-medien/mfa-email/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/q23-medien/mfa-email/compare/v1.0.4...v1.1.0
[1.0.4]: https://github.com/q23-medien/mfa-email/compare/v1.0.3...v1.0.4
[1.0.3]: https://github.com/q23-medien/mfa-email/compare/v1.0.2...v1.0.3
[1.0.0]: https://github.com/q23-medien/mfa-email/releases/tag/v1.0.0
