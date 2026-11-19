<?php

declare(strict_types=1);

namespace HydeReadingTime\Tests\Unit;

use Hyde\Markdown\Models\Markdown;
use Hyde\Pages\HtmlPage;
use Hyde\Pages\MarkdownPage;
use HydeReadingTime\ReadingTimeCalculator;
use HydeReadingTime\ReadingTimeResult;
use HydeReadingTime\ReadingTimeService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReadingTimeService::class)]
final class ReadingTimeServiceTest extends TestCase
{
    public function test_eligible_markdown_page_returns_structured_result(): void
    {
        $service = $this->service();
        $page = $this->page('A useful page.');

        self::assertEquals(new ReadingTimeResult(3, 1), $service->forPage($page));
    }

    public function test_missing_or_unsupported_page_returns_null(): void
    {
        $service = $this->service();
        $page = $this->getMockBuilder(HtmlPage::class)->disableOriginalConstructor()->getMock();

        self::assertNull($service->forPage(null));
        self::assertNull($service->forPage($page));
    }

    public function test_empty_page_has_zero_result_but_no_display_text(): void
    {
        $service = $this->service();
        $result = $service->forPage($this->page(''));

        self::assertEquals(new ReadingTimeResult(0, 0), $result);
        self::assertNull($service->format($result));
    }

    public function test_formatting_uses_configured_wording_and_numeric_placeholders(): void
    {
        $service = $this->service(format: ':minutes minutes / :wordCount words');

        self::assertSame('1 minutes / 3 words', $service->format(new ReadingTimeResult(3, 1)));
    }

    public function test_repeated_results_are_cached_and_content_changes_invalidate_the_cache(): void
    {
        $service = $this->service();
        $markdown = new Markdown('One word.');
        $page = $this->pageWithMarkdown($markdown);

        $initial = $service->forPage($page);

        self::assertSame($initial, $service->forPage($page));

        $markdown->body = 'Three separate words.';

        $updated = $service->forPage($page);

        self::assertNotSame($initial, $updated);
        self::assertSame(3, $updated?->wordCount);
    }

    public function test_page_types_must_be_markdown_page_classes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ReadingTimeService(new ReadingTimeCalculator(), [HtmlPage::class]);
    }

    private function service(string $format = ':minutes min read'): ReadingTimeService
    {
        return new ReadingTimeService(new ReadingTimeCalculator(), [MarkdownPage::class], $format);
    }

    private function page(string $content): MarkdownPage
    {
        return $this->pageWithMarkdown(new Markdown($content));
    }

    private function pageWithMarkdown(Markdown $markdown): MarkdownPage
    {
        $page = $this
            ->getMockBuilder(MarkdownPage::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['markdown'])
            ->getMock();

        $page->method('markdown')->willReturn($markdown);

        return $page;
    }
}
