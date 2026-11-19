<?php

declare(strict_types=1);

namespace HydeReadingTime;

use Hyde\Foundation\HydeKernel;
use Hyde\Pages\Concerns\BaseMarkdownPage;
use Hyde\Pages\Concerns\HydePage;
use InvalidArgumentException;
use WeakMap;

final class ReadingTimeService
{
    /** @var WeakMap<BaseMarkdownPage, array{sourceHash: string, result: ReadingTimeResult}> */
    private WeakMap $pageCache;

    /** @var list<class-string<BaseMarkdownPage>> */
    private readonly array $pageTypes;

    /**
     * @param  array<array-key, mixed>  $pageTypes
     * @throws InvalidArgumentException
     */
    public function __construct(
        private readonly ReadingTimeCalculator $calculator,
        array $pageTypes,
        private readonly string $format = ':minutes min read',
    ) {
        $validatedPageTypes = [];

        foreach ($pageTypes as $pageType) {
            if (!is_string($pageType) || !is_a($pageType, BaseMarkdownPage::class, true)) {
                throw new InvalidArgumentException(
                    'Configured page types must extend ' . BaseMarkdownPage::class . '.',
                );
            }

            $validatedPageTypes[] = $pageType;
        }

        $this->pageTypes = $validatedPageTypes;
        $this->pageCache = new WeakMap();
    }

    /**
     * @throws InvalidArgumentException
     * @throws \League\CommonMark\Exception\CommonMarkException
     */
    public function forPage(?HydePage $page): ?ReadingTimeResult
    {
        if (!$page instanceof BaseMarkdownPage || !$this->isEligible($page)) {
            return null;
        }

        $markdown = $page->markdown()->body();
        $sourceHash = hash('sha256', $markdown);
        $cached = $this->pageCache[$page] ?? null;

        if ($cached !== null && $cached['sourceHash'] === $sourceHash) {
            return $cached['result'];
        }

        $result = $this->calculator->calculate($markdown);
        $this->pageCache[$page] = [
            'sourceHash' => $sourceHash,
            'result' => $result,
        ];

        return $result;
    }

    public function format(?ReadingTimeResult $result): ?string
    {
        if ($result === null || $result->wordCount === 0) {
            return null;
        }

        return strtr($this->format, [
            ':minutes' => (string) $result->minutes,
            ':wordCount' => (string) $result->wordCount,
        ]);
    }

    /**
     * @throws InvalidArgumentException
     * @throws \League\CommonMark\Exception\CommonMarkException
     */
    public function formatCurrentPage(): ?string
    {
        return $this->format($this->forPage(HydeKernel::getInstance()->currentPage()));
    }

    private function isEligible(BaseMarkdownPage $page): bool
    {
        foreach ($this->pageTypes as $pageType) {
            if ($page instanceof $pageType) {
                return true;
            }
        }

        return false;
    }
}
