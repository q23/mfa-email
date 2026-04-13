# Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| 1.x     | ✅ Yes    |

## Reporting a Vulnerability

**Please do NOT open a public GitHub issue for security vulnerabilities.**

Report security issues via email to: **technik@q23.de**

Please include:
- A description of the vulnerability
- Steps to reproduce
- Potential impact
- Any suggested fix (optional)

## Response Timeline

| Step | Timeframe |
|---|---|
| Acknowledgement | Within 72 hours |
| Status update | Within 14 days |
| Patch release | Depends on severity |

## Security Architecture

This extension implements the following security measures:

- **Bcrypt hashing** — verification codes are never stored in plaintext
- **Single-use codes** — immediately invalidated after successful verification
- **Time-limited codes** — expire after 6 minutes (`CodeService::CODE_VALIDITY_SECONDS`)
- **Brute-force protection** — lockout after 5 failed attempts for 15 minutes
- **No third-party dependencies** — only TYPO3 core APIs are used
- **Post-redirect-get** — 303 redirect after successful verification prevents form resubmission
- **CSPRNG** — codes generated via `random_int()`, not `rand()` or `mt_rand()`
