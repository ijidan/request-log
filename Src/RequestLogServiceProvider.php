<?php

namespace IJidan\RequestLog;

use Illuminate\Support\ServiceProvider;


/**
 * Class RequestLogServiceProvider
 * @package IJidan\RequestLog
 */
class RequestLogServiceProvider extends ServiceProvider {
    /**
     * Register services.
     * @return void
     */
    public function register() {
        $this->app->singleton('request-log', function ($app) {
            return new RequestLogLogic();
        });
    }

    /**
     * Bootstrap services.
     * @return void
     */
    public function boot() {

        $this->publishes([
            __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'request-log.php' => config_path('request-log.php')
        ]);
    }

    /**
     * @return string[]
     */
    public function provides(): array {
        return ['request-log'];
    }
}
