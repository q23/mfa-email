# Installation

## Requirements

- TYPO3 12.4.x and 13.4.x
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
zip -r -X ../q23_mfa_email.zip Classes Configuration Resources LICENSE \
  composer.json ext_conf_template.txt ext_emconf.php ext_localconf.php \
  ext_tables.sql -x '*.DS_Store'
```

Only the runtime files are listed, so `Tests/`, `phpunit.xml.dist`, `.Build/`
and `.superpowers/` cannot end up in the ZIP.

Then in the TYPO3 backend:

1. Go to **Extensions → Upload Extension**
2. Upload the ZIP and activate the extension
3. Flush all caches: **Maintenance → Flush all caches**

## Database

After installing or updating, run `typo3 extension:setup -e q23_mfa_email`
(or Install Tool → Analyze Database Structure). Installations that already have
the `tx_dpvmfaemail_*` fields need no action.

To run the upgrade wizard manually:
**Admin Tools → Upgrade → Upgrade Wizard → Email 2FA: Create database fields**
