<?php

declare(strict_types=1);

namespace HydeReadingTime;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

final class ReadingTimeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/reading-time.php', 'reading-time');

        $this->app->singleton(
            ReadingTimeCalculator::class,
            /**
             * @throws \Illuminate\Contracts\Container\BindingResolutionException
             * @throws \InvalidArgumentException
             * @throws \League\CommonMark\Exception\AlreadyInitializedException
             */
            static function (Application $app): ReadingTimeCalculator {
                /** @var Repository $config */
                $config = $app->make('config');

                return new ReadingTimeCalculator(
                    wordsPerMinute: $config->get('reading-time.words_per_minute', 200),
                    includeCode: $config->get('reading-time.include_code', false),
                );
            },
        );

        $this->app->singleton(
            ReadingTimeService::class,
            /**
             * @throws \Illuminate\Contracts\Container\BindingResolutionException
             * @throws \InvalidArgumentException
             */
            static function (Application $app): ReadingTimeService {
                /** @var Repository $config */
                $config = $app->make('config');

                return new ReadingTimeService(
                    calculator: $app->make(ReadingTimeCalculator::class),
                    pageTypes: $config->get('reading-time.page_types', []),
                    format: $config->get('reading-time.format', ':minutes min read'),
                );
            },
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/reading-time.php' => $this->app->configPath('reading-time.php'),
        ], 'reading-time-config');

        Blade::directive('readingTime', static function (): string {
            return '<?php echo e(app(\\HydeReadingTime\\ReadingTimeService::class)->formatCurrentPage() ?? \'\'); ?>';
        });
    }
}
