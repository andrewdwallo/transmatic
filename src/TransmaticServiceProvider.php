<?php

namespace Wallo\Transmatic;

use InvalidArgumentException;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Wallo\Transmatic\Contracts\TranslationHandler;
use Wallo\Transmatic\Contracts\Translator;
use Wallo\Transmatic\Services\TranslateService;
use Wallo\Transmatic\Services\Translation\CacheHandler;
use Wallo\Transmatic\Services\Translation\FileHandler;
use Wallo\Transmatic\Services\Translators\AwsTranslate;

class TransmaticServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('transmatic')
            ->hasConfigFile()
            ->hasInstallCommand(static function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->askToStarRepoOnGitHub('andrewdwallo/transmatic');
            });
    }

    public function packageRegistered(): void
    {
        $this->app->scoped('transmatic', function (): Transmatic {
            return new Transmatic(
                $this->app->make(TranslateService::class),
                $this->app->make(TranslationHandler::class)
            );
        });

        $this->app->bind(TranslationHandler::class, static function (): TranslationHandler {
            $storageMap = [
                'file' => FileHandler::class,
                'cache' => CacheHandler::class,
            ];

            $storageType = config('transmatic.storage', 'file');

            if (array_key_exists($storageType, $storageMap)) {
                return new $storageMap[$storageType]();
            }

            throw new InvalidArgumentException("Invalid translation storage type: {$storageType}");
        });

        $this->app->bind(Translator::class, static function (): Translator {
            $translator = config('transmatic.translator.default', AwsTranslate::class);

            if (! class_exists($translator)) {
                throw new InvalidArgumentException("Invalid translator class: {$translator}");
            }

            $instance = new $translator();

            if (! $instance instanceof Translator) {
                throw new InvalidArgumentException("The class {$translator} must implement Wallo\Transmatic\Contracts\Translator");
            }

            return $instance;
        });

        $this->app->bind(TranslateService::class, TranslateService::class);
    }
}
