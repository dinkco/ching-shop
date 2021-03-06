<?php

namespace ChingShop\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * Class RouteServiceProvider.
 */
class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'ChingShop\Http\Controllers';

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::group(
            [
                'middleware' => 'web',
                'namespace'  => $this->namespace,
            ],
            function () {
                /** @noinspection PhpIncludeInspection */
                require base_path('routes/web.php');
            }
        );
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::group(
            [
                'middleware' => 'api',
                'namespace'  => $this->namespace,
                'prefix'     => 'api',
            ],
            function () {
                /** @noinspection PhpIncludeInspection */
                require base_path('routes/api.php');
            }
        );
    }
}
