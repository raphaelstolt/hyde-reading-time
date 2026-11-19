<?php

declare(strict_types=1);

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
