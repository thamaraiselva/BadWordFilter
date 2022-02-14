<?php
namespace JCrowe\BadWordFilter\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use VendorName\Skeleton\SkeletonServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use JCrowe\BadWordFilter\Providers\BadWordFilterServiceProvider;
use Symfony\Component\Translation\Dumper\DumperInterface;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        /*  Factory::guessFactoryNamesUsing(
             fn (string $modelName) => 'JCrowe\\BadWordFilter\\Database\\Factories\\'.class_basename($modelName).'Factory'
         ); */
    }

    protected function getPackageProviders($app)
    {
        return [
            BadWordFilterServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        
        $migration = include __DIR__.'/../database/migrations/create_spam_keywords_table.php.stub';
        $migration->up();
       
    }
}
