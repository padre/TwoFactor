<?php

namespace Laragear\TwoFactor;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Validation\Factory as ValidatorContract;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class TwoFactorServiceProvider extends ServiceProvider
{
    public const CONFIG = __DIR__ . '/../config/twofactor.php';
    public const VIEWS = __DIR__ . '/../resources/views';
    public const LANG = __DIR__.'/../lang';
    public const DB = __DIR__.'/../database/migrations';

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(static::CONFIG, 'twofactor');
    }

    /**
     * Bootstrap the application services.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function boot(Router $router): void
    {
        $this->loadViewsFrom(static::VIEWS, 'twofactor');
        $this->loadTranslationsFrom(static::LANG, 'twofactor');
        $this->loadMigrationsFrom(static::DB);

        $this->registerMiddleware($router);
        $this->registerRules();

        if ($this->app->runningInConsole()) {
            $this->publishFiles();
        }
    }

    /**
     * Register the middleware.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    protected function registerMiddleware(Router $router): void
    {
        $router->aliasMiddleware('2fa.enabled', Http\Middleware\RequireTwoFactorEnabled::class);
        $router->aliasMiddleware('2fa.confirm', Http\Middleware\ConfirmTwoFactorCode::class);
    }

    /**
     * Register custom validation rules.
     *
     * @return void
     */
    protected function registerRules(): void
    {
        $this->callAfterResolving('validator', function (ValidatorContract $validator, Application $app): void {
            $validator->extendImplicit(
                'totp',
                Rules\Totp::class,
                $app->make('translator')->get('twofactor::validation.totp_code')
            );
        });
    }

    /**
     * Publish config, view and migrations files.
     *
     * @return void
     */
    protected function publishFiles(): void
    {
        $this->publishes([static::CONFIG => $this->app->configPath('twofactor.php')], 'config');
        $this->publishes([static::VIEWS => $this->app->viewPath('vendor/twofactor')], 'views');
        $this->publishes([static::LANG => $this->app->langPath('vendor/twofactor')], 'translations');
    }
}