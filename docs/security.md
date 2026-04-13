# Security Architecture

## Code Generation

- 6-digit code generated via `random_int(0, 999999)` — cryptographically secure CSPRNG
- Zero-padded to always be exactly 6 digits (e.g. `000042`)

## Code Storage

- Codes are **never stored in plaintext**
- Stored as bcrypt hash via `password_hash($code, PASSWORD_DEFAULT)`
- Verified via `password_verify()` — constant-time comparison, resistant to timing attacks

## Code Lifecycle

1. Code is generated and hashed on each new login or resend request
2. Previous code is overwritten — only one valid code exists per user at any time
3. Code expires after **6 minutes** (`CodeService::CODE_VALIDITY_SECONDS = 360`)
4. After successful verification, the code is **immediately deleted** from the database (single-use)
5. Expired codes are invalidated on the next verification attempt

## Brute-Force Protection

- After **5 failed attempts** (`CodeService::MAX_ATTEMPTS = 5`), the user is locked out
- Lockout duration: **15 minutes** (`CodeService::LOCKOUT_SECONDS = 900`)
- Lockout is checked before code verification and before any new code is generated
- After the lockout expires, the attempt counter is automatically reset

## Session Verification Flag

- Verification state is stored in the TYPO3 frontend session under key `tx_dpvmfaemail`
- The flag `verified: true` is set only after a successful code verification
- Session data is persisted immediately via `FrontendUserAuthentication::storeSessionData()`
- On each request, the middleware checks this flag before generating or verifying codes

## Redirect Security (Post/Redirect/Get)

- After successful verification, a **303 redirect** is issued to prevent form resubmission
- The redirect target is the original request path, passed via a hidden form field
- The redirect path is sanitised with `htmlspecialchars()` in the form output

## Database Fields

The stored bcrypt hash (`tx_dpvmfaemail_code`) is computationally infeasible to reverse.
All MFA fields are write-protected from normal frontend user sessions — they are only
modified by the extension's own service classes via direct database calls.
