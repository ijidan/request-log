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
        $this->app->singleton(RequestLogLogic::class, function ($app) {
            return new RequestLogLogic();
        });
        $this->app->alias(RequestLogLogic::class, 'request-log');
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
        return [RequestLogLogic::class, 'request-log'];
    }
}
