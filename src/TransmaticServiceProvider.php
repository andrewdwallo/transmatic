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
        $this->app->singleton('transmatic', function (): Transmatic {
            return new Transmatic(
                $this->app->make(TranslateService::class),
                $this->app->make(TranslationHandler::class)
            );
        });

        $this->app->bind(TranslationHandler::class, static function (): TranslationHandler {
            $storageType = config('transmatic.storage', 'cache');

            if ($storageType === 'file') {
                return new FileHandler();
            }

            if ($storageType === 'cache') {
                return new CacheHandler();
            }

            throw new InvalidArgumentException("Invalid translation storage type: {$storageType}");
        });

        $this->app->bind(Translator::class, static function (): Translator {
            $translator = config('transmatic.translator', AwsTranslate::class);

            if (! class_exists($translator)) {
                throw new InvalidArgumentException("Invalid translator class: {$translator}");
            }

            return new $translator();
        });

        $this->app->singleton(TranslateService::class, function (): TranslateService {
            return new TranslateService(
                $this->app->make(TranslationHandler::class),
                $this->app->make(Translator::class)
            );
        });
    }
}
