# PROMPTS.md — hyde-reading-time

## Mission

Build `hyde-reading-time`, a small, maintainable HydePHP extension that calculates estimated reading time for Markdown-driven pages and makes it available to Blade templates without requiring authors to maintain the value manually.

**Repository description:** A HydePHP extension for calculating and displaying estimated reading times for Markdown content.

## Working principles

- Follow the currently supported HydePHP extension APIs. Inspect HydePHP's actual version, documentation, source, and lifecycle before implementing integration points; do not assume hook names or page model methods.
- Prefer a focused package over modifying HydePHP core or creating a general-purpose Markdown parser.
- Use PHP and Composer conventions compatible with the selected HydePHP version; determine the minimum PHP version from actual dependencies.
- Keep reading-time calculations deterministic, offline, and inexpensive.
- Preserve existing Markdown rendering, route generation, and cache/build behavior.
- Write automated tests for all core behavior and document trade-offs.
- Work through the phases below in order; check items off only after verifying them.

## Phase 1 — Research and project scaffolding

- [x] Inspect current HydePHP version(s), supported PHP/Laravel versions, plugin registration mechanisms, page model APIs, and build/render lifecycle.
- [x] Check whether HydePHP or its dependencies already expose reading-time metadata; avoid duplicating built-in functionality.
- [x] Decide the supported HydePHP version range and document compatibility.
- [x] Determine the most appropriate way to expose reading time to templates (page metadata, view composer, helper, directive, or another documented extension point). Prefer a documented public API.
- [x] Create a Composer library package named `hyde-reading-time` with appropriate package metadata, PSR-4 autoloading, license, and a concise README.
- [x] Add an automated test runner and CI workflow for the supported PHP/HydePHP matrix.
- [x] Add a minimal fixture HydePHP site or integration harness for end-to-end verification.

**Acceptance:** The package installs into a clean compatible HydePHP project without manual modifications to vendor code.

## Phase 2 — Reading-time calculation engine

- [x] Implement a small, independently testable calculator accepting Markdown or normalized readable text.
- [x] Set a configurable default reading speed (e.g. 200 words per minute), with validation that the value is positive.
- [x] Count human-readable prose while excluding fenced code blocks, indented code blocks, inline code, front matter, HTML comments, and non-visible markup where practical.
- [x] Preserve visible link labels and image alt text according to a documented counting policy; ignore link destinations and Markdown syntax.
- [x] Handle headings, lists, blockquotes, tables, Unicode text, and whitespace consistently.
- [x] Prefer the Markdown parser already used by the supported HydePHP version or a compatible AST/HTML-to-text approach; do not rely on fragile regex-only Markdown parsing.
- [x] Define a consistent rounding policy: nonempty content yields at least one minute; empty content yields zero minutes (unless the final UI explicitly hides it).
- [x] Return a structured result with `wordCount` and `minutes` rather than a hard-coded English phrase.
- [x] Document limitations for languages without whitespace-delimited words; avoid claiming universal multilingual accuracy.

**Acceptance:** Calculation has no network dependency, is deterministic, and is covered by edge-case tests.

## Phase 3 — HydePHP integration

- [x] Discover eligible Markdown-backed page types using public HydePHP APIs.
- [x] Calculate reading time from the correct source content at a lifecycle stage that does not mutate rendered HTML.
- [x] Expose the result to Blade templates using the extension mechanism verified in Phase 1.
- [x] Ensure static pages, documentation pages, and blog posts are handled appropriately; document any exclusions.
- [x] Avoid recalculating the same page unnecessarily within a build/request; keep any cache scoped and invalidated correctly.
- [x] Ensure the extension does not break pages without Markdown source or custom page implementations.
- [x] Verify that existing highlighting, Markdown extensions, and other HydePHP plugins still work.

**Acceptance:** A fixture site can render reading-time information for supported pages with no manual per-page metadata.

## Phase 4 — Configuration and presentation

- [x] Provide a publishable or otherwise conventional HydePHP/Laravel configuration file (verify the framework's current conventions first).
- [x] Support `words_per_minute` with a sensible default.
- [x] Support choosing which page types receive reading-time data, if feasible through public APIs.
- [x] Provide an opt-in, accessible Blade snippet/component/helper for display; do not forcibly modify themes or page layouts.
- [x] Make display wording customizable/localizable (e.g. `3 min read`), keeping the numeric result independent from presentation.
- [x] Consider a toggle to include or exclude code blocks; default to excluding them.
- [x] Ensure default configuration works without publishing a config file.

**Acceptance:** A user can install the package, add a documented template snippet, and display a reading-time estimate.

## Phase 5 — Testing and quality

- [x] Unit-test empty input, one word, exactly one minute, slightly over one minute, and invalid reading speeds.
- [x] Unit-test fenced/indented/inline code, front matter, HTML comments, links, images, headings, lists, tables, and blockquotes.
- [x] Unit-test UTF-8 punctuation, multiple whitespace characters, and non-Latin examples; document known segmentation limits.
- [x] Integration-test a Markdown blog post and documentation page in a real or realistic HydePHP site.
- [x] Test disabled/excluded page types and pages with missing source content.
- [x] Test template access, formatting customization, and compatibility with existing Markdown rendering.
- [x] Verify repeated builds produce stable output and do not create stale reading-time values after content edits.
- [ ] Run static analysis, coding standards, tests, and Composer validation in CI.

**Acceptance:** CI is green, integration is verified, and known limitations are documented.

## Phase 6 — Documentation and first release

- [x] Write a README with motivation, supported versions, installation, configuration, usage, examples, and limitations.
- [x] Show an actual Blade template example matching the implemented API; do not invent helper names in documentation.
- [x] Document the word-count and rounding rules, including how code and Markdown are treated.
- [x] Provide a minimal fixture/example site and an upgrade/compatibility policy.
- [x] Add a changelog, license, contributing instructions, and GitHub Actions CI.
- [x] Verify Composer installation in a fresh HydePHP site and run a production build.
- [ ] Tag `v0.1.0` and publish to Packagist when the MVP acceptance criteria are met.

**Verification note:** The workflow is configured; tests, PHPStan, Mago formatting, and strict Composer validation pass locally on PHP 8.3, but GitHub Actions has not been run remotely. A PHP 8.2 production-only Composer dry run and calculator smoke test passed; the local PHP 8.2 CLI lacks `mbstring`, `dom`, and `xml`, so its Hyde build and dev test suite could not be run. CI now explicitly installs those extensions. Tagging and Packagist publication remain pending repository and release authorization.

## MVP definition of done

- [x] Installs cleanly via Composer into a supported HydePHP project.
- [x] Computes deterministic reading times for Markdown-backed content.
- [x] Excludes code and markup from counts according to documented rules.
- [x] Makes numeric reading time available to Blade templates through a stable, documented API.
- [x] Supports configurable reading speed and opt-in display.
- [ ] Has unit and integration tests passing in CI.
- [x] Does not alter rendered Markdown or require a third-party service.
- [x] README demonstrates real installation and usage.

## Post-MVP backlog (do not block v0.1.0)

- [ ] Optional automatic theme integration, if maintainers endorse an extension point.
- [ ] Optional separate estimated time for code-heavy tutorials.
- [ ] Better language-specific word segmentation with configurable locale.
- [ ] Reading-time badges and additional accessible display variants.
- [ ] Optional metadata exposure for feeds, cards, or structured content summaries.
- [ ] Performance benchmarking for large documentation sites.

## Instructions for a coding agent

1. Begin by inspecting the actual HydePHP codebase and its documented extension APIs. Report relevant versions, lifecycle hooks, and existing functionality before proposing implementation details.
2. Implement one phase at a time. For each phase, explain the changes, tests, and any deviations from this plan.
3. Prefer small, reviewable commits with conventional commit messages.
4. Do not fabricate HydePHP APIs, Blade directives, commands, or compatibility claims. Verify each against installed dependencies.
5. Ask before adding runtime dependencies, changing the public API, or expanding scope beyond the MVP.
6. Keep this checklist updated as work is completed.
