<?php namespace La2ha\Piximage;

use Illuminate\Support\ServiceProvider;

class PiximageServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    public function boot()
    {
        $this->package('la2ha/piximage');
        include __DIR__.'/../../routes.php';
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['piximage'] = $this->app->share(function ($app) {
            return new PixImage();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('piximage');
    }

}