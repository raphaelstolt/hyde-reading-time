# Contributing

Thank you for your interest in contributing to `hyde-reading-time`.

## Prerequisites

- PHP 8.2 or later.
- Composer.

## Getting Started

After cloning the repository, install the development dependencies:

```bash
composer install
```

## Code Quality

Run all checks before submitting a change:

```bash
composer check
composer validate --strict
```

`composer check` runs Mago linting and analysis, PHPStan, the non-mutating formatting check, and the test suite. The
tests install the isolated Hyde fixture and build it with Hyde's CLI. Individual scripts are also available:
`composer lint`, `composer analyze`, `composer fmt`, `composer analyse` (PHPStan), `composer format:check`, and
`composer test`. `composer fmt` applies formatting; `composer format:check` only checks it.

## Pull Requests

Please open pull requests against the `main` branch from a dedicated feature branch. Use descriptive branch names that
reflect the change, such as `feat/add-option`, `fix/link-formatting`, or `docs/update-readme`.

Before opening a PR, ensure:

1. `composer check` passes locally.
2. `CHANGELOG.md` is updated under the `[Unreleased]` section with a summary of notable changes.
3. Commit messages follow [Conventional Commits](https://www.conventionalcommits.org/).

## Commit Messages

Please follow [Conventional Commits](https://www.conventionalcommits.org/) for all commit messages. Common types include:

- `feat:` — new features or behavior changes
- `fix:` — bug fixes
- `docs:` — documentation updates
- `refactor:` — code changes that neither fix bugs nor add features
- `test:` — adding or updating tests
- `chore:` — maintenance tasks (dependencies, config, etc.)