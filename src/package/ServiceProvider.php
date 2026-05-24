<?php

namespace PragmaRX\CountriesLaravel\Package;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use PragmaRX\Countries\Package\Countries as CountriesService;
use PragmaRX\Countries\Package\Data\Repository;
use PragmaRX\Countries\Package\Services\Cache\Service as Cache;
use PragmaRX\Countries\Package\Services\Config;
use PragmaRX\Countries\Package\Services\Helper;
use PragmaRX\Countries\Package\Services\Hydrator;
use PragmaRX\CountriesLaravel\Package\Console\Commands\Update;
use PragmaRX\CountriesLaravel\Package\Facade as CountriesFacade;
use PragmaRX\CountriesLaravel\Package\Http\Controllers\Flag;

class ServiceProvider extends IlluminateServiceProvider implements DeferrableProvider
{
    protected function configurePaths(): void
    {
        $this->publishes([
            $this->getPackageConfigFile() => config_path('countries.php'),
        ], 'config');
    }

    protected function getPackageConfigFile(): string
    {
        return __DIR__.'/../config/countries.php';
    }

    protected function mergeConfig(): void
    {
        $this->mergeConfigFrom(
            $this->getPackageConfigFile(), 'countries'
        );
    }

    public function boot(): void
    {
        if (config('countries.validation.enabled')) {
            $this->addValidators();
        }
    }

    public function register(): void
    {
        $this->configurePaths();

        $this->mergeConfig();

        $this->registerService();

        $this->registerUpdateCommand();

        if (config('countries.routes.enabled')) {
            $this->registerRoutes();
        }
    }

    protected function registerRoutes(): void
    {
        Route::get('/pragmarx/countries/flag/file/{cca3}.svg', [Flag::class, 'file'])
            ->name('pragmarx.countries.flag.file');

        Route::get('/pragmarx/countries/flag/download/{cca3}.svg', [Flag::class, 'download'])
            ->name('pragmarx.countries.flag.download');
    }

    protected function registerService(): void
    {
        $this->app->singleton('pragmarx.countries', function () {
            $hydrator = new Hydrator($config = new Config(config()));

            $cache = new Cache($config, app(config('countries.cache.service')));

            $helper = new Helper($config);

            $repository = new Repository($cache, $hydrator, $helper, $config);

            $hydrator->setRepository($repository);

            return new CountriesService($config, $cache, $helper, $hydrator, $repository);
        });
    }

    protected function addValidators(): void
    {
        foreach (config('countries.validation.rules') as $ruleName => $countryAttribute) {
            if (is_int($ruleName)) {
                $ruleName = $countryAttribute;
            }

            Validator::extend($ruleName, function ($attribute, $value) use ($countryAttribute) {
                return ! CountriesFacade::where($countryAttribute, $value)->isEmpty();
            }, 'The :attribute must be a valid '.$ruleName.'.');
        }
    }

    protected function registerUpdateCommand(): void
    {
        $this->app->singleton($command = 'countries.update.command', function () {
            return new Update();
        });

        $this->commands($command);
    }

    public function provides(): array
    {
        return [
            'pragmarx.countries',
            'countries.update.command',
        ];
    }
}
