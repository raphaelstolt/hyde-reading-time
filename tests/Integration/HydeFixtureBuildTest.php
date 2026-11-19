<?php

declare(strict_types=1);

namespace HydeReadingTime\Tests\Integration;

use Hyde\Pages\MarkdownPage;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversNothing]
final class HydeFixtureBuildTest extends TestCase
{
    private const SITE_PATH = __DIR__ . '/../fixtures/hyde-site';

    private string $compiledViewsPath;

    protected function setUp(): void
    {
        parent::setUp();

        $viewsDirectory = self::SITE_PATH . '/app/storage/framework/views';

        if (!is_dir($viewsDirectory) && !mkdir($viewsDirectory, 0777, true) && !is_dir($viewsDirectory)) {
            throw new RuntimeException('Unable to create the fixture compiled-view directory.');
        }

        $this->compiledViewsPath = $viewsDirectory . '/reading-time-' . bin2hex(random_bytes(8));

        if (!mkdir($this->compiledViewsPath)) {
            throw new RuntimeException('Unable to create an isolated compiled-view directory.');
        }
    }

    protected function tearDown(): void
    {
        if (isset($this->compiledViewsPath) && is_dir($this->compiledViewsPath)) {
            foreach (glob($this->compiledViewsPath . '/*') ?: [] as $compiledView) {
                if (is_file($compiledView) && !unlink($compiledView)) {
                    throw new RuntimeException('Unable to remove a compiled fixture view.');
                }
            }

            if (!rmdir($this->compiledViewsPath)) {
                throw new RuntimeException('Unable to remove the isolated compiled-view directory.');
            }
        }

        parent::tearDown();
    }

    public function test_official_hyde_build_renders_reading_time_for_all_markdown_page_types_and_remains_stable(): void
    {
        $postPath = self::SITE_PATH . '/_posts/reading-time.md';
        $originalSource = file_get_contents($postPath);
        self::assertNotFalse($originalSource);

        try {
            self::assertSame([], glob($this->compiledViewsPath . '/*.php'));
            $this->buildSite();
            self::assertNotEmpty(glob($this->compiledViewsPath . '/*.php'));

            $postHtml = $this->readBuiltHtml('posts/reading-time.html');
            $docsHtml = $this->readBuiltHtml('docs/getting-started.html');
            $pageHtml = $this->readBuiltHtml('about.html');

            self::assertStringContainsString('<aside data-reading-time>1 min read</aside>', $postHtml);
            self::assertStringContainsString('<aside data-reading-time>1 min read</aside>', $docsHtml);
            self::assertStringContainsString('<aside data-reading-time>1 min read</aside>', $pageHtml);
            self::assertStringContainsString('<span data-reading-time-words>23</span>', $postHtml);
            self::assertStringContainsString('<span data-reading-time-words>15</span>', $docsHtml);
            self::assertStringContainsString('<span data-reading-time-words>20</span>', $pageHtml);
            self::assertStringContainsString('<h1>Reading time post</h1>', $postHtml);
            self::assertStringContainsString('<p>This Markdown blog post proves', $postHtml);
            self::assertStringContainsString('<pre', $postHtml);
            self::assertStringContainsString('class="language-php"', $postHtml);
            self::assertStringContainsString('rendered code', $postHtml);
            self::assertStringContainsString('<h1>Getting started</h1>', $docsHtml);
            self::assertStringContainsString('<h1>About this fixture</h1>', $pageHtml);

            $this->buildSite();
            self::assertSame($postHtml, $this->readBuiltHtml('posts/reading-time.html'));

            $updatedSource =
                "---\ntitle: Reading time post\ndate: \"2025-01-01\"\n---\n\n# Reading time post\n\n"
                . implode(' ', array_fill(0, 205, 'updated'))
                . "\n";
            self::assertSame(strlen($updatedSource), file_put_contents($postPath, $updatedSource));

            $this->buildSite();
            self::assertStringContainsString(
                '<aside data-reading-time>2 min read</aside>',
                $this->readBuiltHtml('posts/reading-time.html'),
            );
        } finally {
            self::assertSame(strlen($originalSource), file_put_contents($postPath, $originalSource));
            $this->buildSite();
        }

        self::assertSame($originalSource, file_get_contents($postPath));
    }

    public function test_config_can_customize_wording_and_exclude_page_types(): void
    {
        $configPath = self::SITE_PATH . '/config/reading-time.php';
        $originalConfig = is_file($configPath) ? file_get_contents($configPath) : null;

        if ($originalConfig === false) {
            self::fail('Unable to read the fixture reading-time configuration.');
        }

        $config =
            "<?php\n\nreturn "
            . var_export([
                'words_per_minute' => 200,
                'page_types' => [MarkdownPage::class],
                'format' => 'Custom: :wordCount words',
                'include_code' => false,
            ], true)
            . ";\n";

        try {
            $this->publishConfig();
            self::assertFileExists($configPath);
            $publishedConfig = file_get_contents($configPath);
            self::assertNotFalse($publishedConfig);
            self::assertStringContainsString("'words_per_minute' => 200", $publishedConfig);

            self::assertSame(strlen($config), file_put_contents($configPath, $config));
            $this->buildSite();

            self::assertStringContainsString('Custom: ', $this->readBuiltHtml('about.html'));
            self::assertStringContainsString(
                '<aside data-reading-time></aside>',
                $this->readBuiltHtml('posts/reading-time.html'),
            );
            self::assertStringContainsString(
                '<aside data-reading-time></aside>',
                $this->readBuiltHtml('docs/getting-started.html'),
            );
        } finally {
            if ($originalConfig === null) {
                if (is_file($configPath) && !unlink($configPath)) {
                    throw new RuntimeException('Unable to remove the temporary fixture configuration.');
                }
            } else {
                self::assertSame(strlen($originalConfig), file_put_contents($configPath, $originalConfig));
            }

            $this->buildSite();
        }
    }

    private function buildSite(): void
    {
        $this->runHydeCommand(['build', '--no-interaction']);
    }

    private function publishConfig(): void
    {
        $this->runHydeCommand(['vendor:publish', '--tag=reading-time-config', '--no-interaction']);
    }

    /**
     * @param  list<string>  $arguments
     */
    private function runHydeCommand(array $arguments): void
    {
        $process = proc_open(
            array_merge([PHP_BINARY, 'hyde'], $arguments),
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            self::SITE_PATH,
            array_merge(getenv(), ['VIEW_COMPILED_PATH' => $this->compiledViewsPath]),
        );

        if (!is_resource($process)) {
            self::fail('Unable to start the fixture Hyde build process.');
        }

        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        self::assertSame(0, proc_close($process), $output);
    }

    private function readBuiltHtml(string $relativePath): string
    {
        $path = self::SITE_PATH . '/_site/' . $relativePath;
        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertNotFalse($content);

        return $content;
    }
}
