# Installation

## Requirements

- TYPO3 12.4.x and 13.4.x
- `felogin` system extension (included in TYPO3)
- Working mail configuration in `$GLOBALS['TYPO3_CONF_VARS']['MAIL']`

## Composer Mode (recommended)

```bash
composer require q23/mfa-email
```

Then:

1. Create the database fields: `vendor/bin/typo3 extension:setup -e q23_mfa_email`
2. Flush all caches: **Maintenance → Flush all caches**

## Classic Mode (ZIP Upload)

Build the ZIP from the extension root directory:

```bash
zip -r -X ../q23_mfa_email.zip Classes Configuration Resources LICENSE \
  composer.json ext_conf_template.txt ext_emconf.php ext_localconf.php \
  ext_tables.sql -x '*.DS_Store'
```

Only the runtime files are listed, so tests and dev files can never end up in the ZIP.

Then in the TYPO3 backend:

1. Go to **Extensions → Upload Extension**
2. Upload the ZIP and activate the extension
3. Flush all caches: **Maintenance → Flush all caches**

## Database

After installing or updating, run `typo3 extension:setup -e q23_mfa_email`
(or Install Tool → Analyze Database Structure). Installations that already have
the `tx_dpvmfaemail_*` fields need no action.

**Important:** After replacing the extension files (on update and on rollback),
flush all caches with `typo3 cache:flush` or in the Install Tool via
**Flush TYPO3 and PHP Cache**. If PHP OPcache runs with
`opcache.validate_timestamps=0`, reset OPcache as well. The compiled DI container
still references the old constructor of `CodeService`, which breaks every
frontend request.

To run the upgrade wizard manually:
**Admin Tools → Upgrade → Upgrade Wizard → Email 2FA: Create database fields**
