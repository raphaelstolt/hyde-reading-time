# HydePHP Reading Time

Estimate reading times for Hyde's Markdown pages without changing their rendered output or adding per-page metadata.

## Why?

Hyde's built-in reading-time helper counts plain text. It does not account for Markdown structure or offer configurable
reading speed and page-aware results.

This extension parses Markdown with CommonMark and returns a structured word count and minute estimate. Add the optional
Blade directive to display it, or use the service directly when a template needs the numeric values.

## Requirements

- PHP `^8.2`.
- Hyde Framework `^2.0.3`.
- League CommonMark `^2.2`.

The integration fixture is verified against Hyde Framework `v2.0.3`. The Composer constraint is authoritative; newer Hyde
2.x releases will be supported only after the fixture passes and the constraint is updated. Hyde's built-in
`Hyde\Support\ReadingTime` remains useful for arbitrary plain text.

Before `1.0.0`, breaking public API changes may be made in a minor release and will be called out in the changelog.

## Installation

```bash
composer require stolt/hyde-reading-time
```

Composer package discovery registers the service provider automatically; no manual provider registration is needed.

## Usage

Add the opt-in `@readingTime` directive to a layout or Blade view where the current page's estimate should appear:

```blade
<aside class="article-meta" aria-label="Estimated reading time">
    @readingTime
</aside>
```

The directive uses the current Hyde page and configured format. It renders no text for unsupported pages or empty
Markdown; it does not modify any theme or layout for you.

### Access the result

For numeric access, `ReadingTimeService::forPage()` returns a `ReadingTimeResult` with the integer properties
`wordCount` and `minutes`, or `null` for a page excluded by configuration:

```blade
@php($readingTime = app(\HydeReadingTime\ReadingTimeService::class)->forPage($page))

@if ($readingTime !== null)
    <p>{{ $readingTime->minutes }} min read ({{ $readingTime->wordCount }} words)</p>
@endif
```

Hyde shares the current page as `$page` in its Blade views. Empty eligible pages return a `0`/`0` result; the display
directive intentionally hides it.

## Configuration

Defaults are merged automatically, so publishing a configuration file is optional. To publish it in a Hyde site, run:

```sh
php hyde vendor:publish --tag=reading-time-config
```

The configuration is `config/reading-time.php`:

```php
<?php

use Hyde\Pages\DocumentationPage;
use Hyde\Pages\MarkdownPage;
use Hyde\Pages\MarkdownPost;

return [
    'words_per_minute' => 200,
    'page_types' => [
        MarkdownPost::class,
        DocumentationPage::class,
        MarkdownPage::class,
    ],
    'format' => ':minutes min read',
    'include_code' => false,
];
```

`words_per_minute` must be a positive integer. `page_types` accepts Hyde Markdown page classes and their subclasses. The
format string supports `:minutes` and `:wordCount` placeholders. Code is excluded by default; set `include_code` to
`true` to count it.

## Counting and limitations

- Markdown is parsed with CommonMark and counted from its AST. Hyde's Markdown and rendered HTML are not rewritten.
- Front matter, fenced and indented code, inline code, and raw HTML `<pre>`/`<code>` contents are excluded by default. If
  code inclusion is enabled, code text is counted but never executed.
- Markdown syntax and link destinations are ignored. Link labels and image alt text are counted, as are headings, lists,
  blockquotes, and GFM table text.
- Unicode letters and numbers are counted. Internal apostrophes and hyphens join a token, so `don't` and `long-term`
  each count as one word.
- HTML tags and comments do not count. Text in raw HTML blocks is preserved, while `script`, `style`, `template`, and
  `noscript` contents are excluded. CSS-hidden text, dynamic Blade content, remote embeds, and other runtime-only text
  cannot be inferred reliably from Markdown source.
- Languages that do not separate words with whitespace are not segmented by locale. A contiguous run of CJK characters
  counts as one token and may be undercounted.
- Empty Markdown returns `wordCount: 0` and `minutes: 0`. Nonempty content uses
  `ceil(wordCount / words_per_minute)`, with a minimum estimate of one minute.
- Results are deterministic and offline. A per-service weak cache uses the page object and Markdown source hash, so
  edited content is recalculated without retaining stale results.

## AI disclaimer

This extension is developed with the assistance of Artificial Intelligence (AI). AI tools are used throughout the
development process, including code generation, documentation, testing, and maintenance.

While reasonable care is taken to review and validate AI-generated contributions, AI-generated code is not inherently
correct, secure, or free of defects. All contributions remain subject to human review and project quality standards.

As with any software, use this package at your own risk. No guarantee is made regarding its fitness for a particular
purpose, and the maintainers assume no liability for damages resulting from its use.

## Contributing

If you're considering contributing to this project, have a look at this repository's [CONTRIBUTING.md](CONTRIBUTING.md)
for more advice.

## License

This extension is licensed under the MIT license. Please see [LICENSE.md](LICENSE.md) for more details.

## Changelog

All noteworthy changes are documented in the [CHANGELOG.md](CHANGELOG.md).