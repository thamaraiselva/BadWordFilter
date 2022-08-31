<?php

namespace JCrowe\BadWordFilter\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\LaravelPackageTools\Package;

class BadWordFilterServiceProvider extends ServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('bad-word-filter')
            ->hasConfigFile('bad-word-filter')
            ->hasMigration('create_spam_keywords_table')
            // ->hasCommand(LovelyPackageCommand::class)
        ;
    }

    /**
     * Boot the package
     */
    // public function boot()
    // {
    //     if (method_exists($this, 'package')) {
    //         $namespace = 'bad-word-filter';
    //         $path = __DIR__ . '/../../..';
    //         $this->package('jcrowe/bad-word-filter', $namespace, $path);
    //     }
    // }


    // /**
    //  * Register the service provider.
    //  *
    //  * @return void
    //  */
    // public function register()
    // {
    //     $this->app->bind('bad-word-filter', function ($app) {
    //         $config = $app->make('config');
    //         /** @var array $defaults */
    //         $defaults = $config->get('bad-word-filter');

    //         return new \JCrowe\BadWordFilter\BadWordFilter($defaults?:[]);
    //     });
    // }

    // /**
    //  * Get the services provided by the provider.
    //  *
    //  * @return array
    //  */
    // public function provides()
    // {
    //     return array('bad-word-filter');
    // }
}
