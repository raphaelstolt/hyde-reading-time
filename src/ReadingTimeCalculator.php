<?php

declare(strict_types=1);

namespace HydeReadingTime;

use InvalidArgumentException;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\HtmlBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
use League\CommonMark\Extension\CommonMark\Node\Inline\HtmlInline;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;
use League\CommonMark\Parser\MarkdownParser;

final class ReadingTimeCalculator
{
    private const WORD_PATTERN = "~[\\p{L}\\p{N}][\\p{L}\\p{M}\\p{N}]*(?:['’\\p{Pd}][\\p{L}\\p{M}\\p{N}]+)*~u";

    private MarkdownParser $parser;

    /**
     * @throws InvalidArgumentException
     * @throws \League\CommonMark\Exception\AlreadyInitializedException
     */
    public function __construct(
        private readonly int $wordsPerMinute = 200,
        private readonly bool $includeCode = false,
    ) {
        if ($wordsPerMinute < 1) {
            throw new InvalidArgumentException('Words per minute must be a positive integer.');
        }

        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $this->parser = new MarkdownParser($environment);
    }

    /**
     * @throws InvalidArgumentException
     * @throws \League\CommonMark\Exception\CommonMarkException
     */
    public function calculate(string $markdown): ReadingTimeResult
    {
        if (preg_match('//u', $markdown) !== 1) {
            throw new InvalidArgumentException('Markdown must be valid UTF-8.');
        }

        $document = $this->parser->parse($this->withoutFrontMatter($markdown));
        $text = html_entity_decode($this->extractText($document), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $matches = [];

        if (preg_match_all(self::WORD_PATTERN, $text, $matches) === false) {
            throw new InvalidArgumentException('Markdown text could not be tokenized.');
        }

        $wordCount = count($matches[0]);
        $minutes = $wordCount === 0 ? 0 : (int) ceil($wordCount / $this->wordsPerMinute);

        return new ReadingTimeResult($wordCount, $minutes);
    }

    private function withoutFrontMatter(string $markdown): string
    {
        $markdown = preg_replace('/^\xEF\xBB\xBF/', '', $markdown) ?? $markdown;
        $lines = preg_split('/\r\n|\n|\r/', $markdown);

        if ($lines === false || ($lines[0] ?? null) !== '---') {
            return $markdown;
        }

        for ($index = 1, $lineCount = count($lines); $index < $lineCount; $index++) {
            if (in_array(trim($lines[$index]), ['---', '...'], true)) {
                return implode("\n", array_slice($lines, $index + 1));
            }
        }

        return $markdown;
    }

    private function extractText(Node $node): string
    {
        if ($node instanceof HtmlInline) {
            return '';
        }

        if ($node instanceof HtmlBlock) {
            return $this->htmlText($node->getLiteral());
        }

        if ($node instanceof FencedCode || $node instanceof IndentedCode || $node instanceof Code) {
            return $this->includeCode ? $node->getLiteral() : '';
        }

        if ($node instanceof Text) {
            return $node->getLiteral();
        }

        $text = '';

        $hiddenHtmlElement = null;
        $hiddenHtmlDepth = 0;

        foreach ($node->children() as $child) {
            if ($child instanceof HtmlInline) {
                $tag = $this->nonVisibleHtmlTag($child->getLiteral());

                if ($hiddenHtmlElement !== null) {
                    if ($tag !== null && $tag['name'] === $hiddenHtmlElement) {
                        if ($tag['closing']) {
                            $hiddenHtmlDepth--;
                        } elseif (!$tag['selfClosing']) {
                            $hiddenHtmlDepth++;
                        }

                        if ($hiddenHtmlDepth === 0) {
                            $hiddenHtmlElement = null;
                        }
                    }

                    continue;
                }

                if ($tag !== null && !$tag['closing'] && !$tag['selfClosing']) {
                    $hiddenHtmlElement = $tag['name'];
                    $hiddenHtmlDepth = 1;
                }

                continue;
            }

            if ($hiddenHtmlElement !== null) {
                continue;
            }

            $text .= ' ' . $this->extractText($child);
        }

        return $text;
    }

    /**
     * @return array{name: string, closing: bool, selfClosing: bool}|null
     */
    private function nonVisibleHtmlTag(string $literal): ?array
    {
        $nonVisibleElements = 'head|script|style|title|template|noscript';

        if (!$this->includeCode) {
            $nonVisibleElements .= '|pre|code';
        }

        $matches = [];

        if (preg_match('~^<\s*(/?)\s*(' . $nonVisibleElements . ')\b[^>]*>$~i', $literal, $matches) !== 1) {
            return null;
        }

        return [
            'name' => strtolower($matches[2]),
            'closing' => $matches[1] === '/',
            'selfClosing' => preg_match('~/\s*>$~', $literal) === 1,
        ];
    }

    private function htmlText(string $html): string
    {
        $html = preg_replace('~<!--.*?-->~s', ' ', $html) ?? $html;
        $html =
            preg_replace('~<(head|script|style|title|template|noscript)\b[^>]*>.*?</\1\s*>~is', ' ', $html) ?? $html;

        if (!$this->includeCode) {
            $html = preg_replace('~<(pre|code)\b[^>]*>.*?</\1\s*>~is', ' ', $html) ?? $html;
        }

        return strip_tags($html);
    }
}
