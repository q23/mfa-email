# Configuration

Go to **Admin Tools → Settings → Extension Configuration → mfa_email**.

## Settings

### `enableForAll` (boolean, default: `0`)

When enabled, ALL frontend users must verify their login with a 6-digit email code.
When disabled, 2FA only applies to users with the individual per-user checkbox enabled.

### `siteName` (string, default: localized fallback)

Displayed in:
- The browser tab title of the verification page
- The header of the verification page
- The email header

### `emailSubjectPrefix` (string, default: `"Login"`)

Prefix for the verification email subject line.

Example: setting `emailSubjectPrefix = MyPortal` produces the subject:
`MyPortal: Your verification code`

### `emailSignature` (string, default: `""`)

Optional closing line appended to both the plaintext and HTML verification email.

Example: `Your team at Example Corp.`

Leave empty to omit the signature entirely.

---

## Per-User Toggle

If `enableForAll` is disabled, you can enable 2FA for individual users:

1. Open a frontend user record in the TYPO3 backend
2. Find the email MFA checkbox in the Access tab (after the "Disable" field)
3. Check it and save

Users without the checkbox enabled will not be prompted for a code.

---

## Example: Full Configuration

```
enableForAll = 1
siteName = My Company Portal
emailSubjectPrefix = MyPortal
emailSignature = Your IT Security Team
```
