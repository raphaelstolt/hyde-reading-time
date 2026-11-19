<?php

declare(strict_types=1);

namespace HydeReadingTime;

use InvalidArgumentException;

final readonly class ReadingTimeResult
{
    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        public int $wordCount,
        public int $minutes,
    ) {
        if ($wordCount < 0 || $minutes < 0) {
            throw new InvalidArgumentException('Reading-time results cannot contain negative values.');
        }
    }
}
