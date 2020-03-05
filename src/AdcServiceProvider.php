<?php namespace Tjphippen\Adc;

use Illuminate\Support\ServiceProvider;

class AdcServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/config.php' => config_path('adc.php'),
        ]);
    }

    public function register()
    {
        $this->app->bind('adc', function($app)
        {
            return new Adc($app->config->get('adc', array()));
        });

        $this->app->booting(function()
        {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('Adc', 'Phippen\Adc\Facades\Adc');
        });
    }
}
