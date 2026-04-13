# Installation

## Requirements

- TYPO3 12.4.x
- `felogin` system extension (included in TYPO3)
- Working mail configuration in `$GLOBALS['TYPO3_CONF_VARS']['MAIL']`

## Composer Mode (recommended)

```bash
composer require q23/mfa-email
```

Then flush all caches: **Maintenance → Flush all caches**.

## Classic Mode (ZIP Upload)

Build the ZIP:

```bash
zip -r ../q23_mfa_email.zip . \
  -x '*.git*' -x '*.DS_Store*' \
  -x 'README.md' -x 'CONTRIBUTING.md' \
  -x 'CHANGELOG.md' -x 'SECURITY.md' \
  -x 'CODE_OF_CONDUCT.md' -x '.gitignore' \
  -x 'docs/*' -x '.github/*'
```

Then in the TYPO3 backend:

1. Go to **Extensions → Upload Extension**
2. Upload the ZIP and activate the extension
3. Flush all caches: **Maintenance → Flush all caches**

## Database

Database columns are added automatically on the first request after activation.
No manual migration step is required.

To run the upgrade wizard manually:
**Admin Tools → Upgrade → Upgrade Wizard → Email 2FA: Create database fields**
