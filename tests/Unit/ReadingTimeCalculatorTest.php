<?php

declare(strict_types=1);

namespace HydeReadingTime\Tests\Unit;

use HydeReadingTime\ReadingTimeCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReadingTimeCalculator::class)]
final class ReadingTimeCalculatorTest extends TestCase
{
    public function test_empty_markdown_has_no_words_or_minutes(): void
    {
        $result = (new ReadingTimeCalculator())->calculate('');

        self::assertSame(0, $result->wordCount);
        self::assertSame(0, $result->minutes);
    }

    public function test_non_empty_text_has_at_least_one_minute(): void
    {
        $result = (new ReadingTimeCalculator())->calculate('One.');

        self::assertSame(1, $result->wordCount);
        self::assertSame(1, $result->minutes);
    }

    public function test_rounds_up_at_the_selected_reading_speed(): void
    {
        $calculator = new ReadingTimeCalculator(wordsPerMinute: 200);

        self::assertSame(1, $calculator->calculate(str_repeat('word ', 200))->minutes);
        self::assertSame(2, $calculator->calculate(str_repeat('word ', 201))->minutes);
        self::assertSame(
            1,
            (new ReadingTimeCalculator(wordsPerMinute: 250))->calculate(str_repeat('word ', 249))->minutes,
        );
    }

    public function test_markdown_syntax_and_destinations_are_not_counted_but_visible_labels_are(): void
    {
        $markdown = 'A [useful link](https://example.test/path) and ![alt words](image.png). `hidden inline`';

        $result = (new ReadingTimeCalculator())->calculate($markdown);

        self::assertSame(6, $result->wordCount);
    }

    public function test_code_blocks_and_inline_code_are_excluded_by_default(): void
    {
        $markdown = <<<'MARKDOWN'
            Visible prose.

            ```php
            hidden code words
            ```

                hidden indented words

            `hidden inline words`
            MARKDOWN;

        $result = (new ReadingTimeCalculator())->calculate($markdown);

        self::assertSame(2, $result->wordCount);
    }

    public function test_code_can_be_included_explicitly(): void
    {
        $markdown = "Visible prose. `inline code`\n\n    indented code\n";

        $result = (new ReadingTimeCalculator(includeCode: true))->calculate($markdown);

        self::assertSame(6, $result->wordCount);
    }

    public function test_headings_lists_and_blockquotes_count_their_visible_text(): void
    {
        $markdown = <<<'MARKDOWN'
            # Heading words

            - List item

            > Quoted prose
            MARKDOWN;

        $result = (new ReadingTimeCalculator())->calculate($markdown);

        self::assertSame(6, $result->wordCount);
    }

    public function test_inline_html_comments_are_ignored_while_their_surrounding_text_is_counted(): void
    {
        $result = (new ReadingTimeCalculator())->calculate('Before <!-- hidden comment --> after.');

        self::assertSame(2, $result->wordCount);
    }

    public function test_inline_script_text_is_not_counted(): void
    {
        $result = (new ReadingTimeCalculator())->calculate('Before <script>hidden script text</script> after.');

        self::assertSame(2, $result->wordCount);
    }

    public function test_html_entities_are_decoded_before_counting(): void
    {
        $result = (new ReadingTimeCalculator())->calculate('<div>Read &amp; write</div>');

        self::assertSame(2, $result->wordCount);
    }

    public function test_raw_html_code_respects_the_code_inclusion_setting(): void
    {
        $markdown = '<pre><code>hidden code words</code></pre>';

        self::assertSame(0, (new ReadingTimeCalculator())->calculate($markdown)->wordCount);
        self::assertSame(3, (new ReadingTimeCalculator(includeCode: true))->calculate($markdown)->wordCount);
    }

    public function test_front_matter_is_excluded_and_html_text_is_preserved_without_comments_or_scripts(): void
    {
        $markdown = <<<'MARKDOWN'
            ---
            title: Metadata words
            ---
            <div>Visible HTML words</div>
            <head><title>hidden title words</title></head>
            <!-- hidden comment words -->
            <script>hidden script words</script>
            MARKDOWN;

        $result = (new ReadingTimeCalculator())->calculate($markdown);

        self::assertSame(3, $result->wordCount);
    }

    public function test_unicode_words_and_gfm_tables_are_counted(): void
    {
        $markdown = <<<'MARKDOWN'
            naïve café Ελληνικά déjà-vu

            | Heading | Value |
            | --- | --- |
            | table | cell |
            MARKDOWN;

        $result = (new ReadingTimeCalculator())->calculate($markdown);

        self::assertSame(8, $result->wordCount);
    }

    public function test_unicode_punctuation_and_whitespace_are_handled(): void
    {
        $result = (new ReadingTimeCalculator())->calculate("“Bonjour,”\u{2003}Привет\t世界!");

        self::assertSame(3, $result->wordCount);
    }

    public function test_reading_speed_must_be_positive(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ReadingTimeCalculator(wordsPerMinute: 0);
    }

    public function test_invalid_utf8_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ReadingTimeCalculator())->calculate("\xC3\x28");
    }
}
