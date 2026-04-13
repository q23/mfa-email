# Contributing to q23_mfa_email

Thank you for your interest in contributing!

## Requirements

- PHP 8.1+
- TYPO3 12.4.x development environment
- Composer

## Branching Model

- **`main`** — stable, released code only
- **`feature/<name>`** — new features (branch off `main`)
- **`fix/<name>`** — bug fixes (branch off `main`)

Every feature or fix is developed in its own branch. Never commit directly to `main`.

## Pull Request Process

1. Fork the repository
2. Create a branch: `git checkout -b feature/my-feature`
3. Make your changes
4. Ensure your code follows PSR-12 and [TYPO3 Coding Guidelines](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/CodingGuidelines/)
5. Update `CHANGELOG.md` under `[Unreleased]`
6. Open a pull request with a clear description

## Coding Standards

- PSR-12 for formatting
- `declare(strict_types=1)` in every PHP file
- Type declarations on all method parameters and return types
- No `@todo` without a linked issue

## Commit Messages

Use [Conventional Commits](https://www.conventionalcommits.org/):

- `feat:` — new feature
- `fix:` — bug fix
- `refactor:` — code change without behavior change
- `chore:` — maintenance (deps, config)
- `docs:` — documentation only

## Reporting Issues

Use the GitHub Issue templates for bug reports and feature requests.
For security issues, see [SECURITY.md](SECURITY.md).
