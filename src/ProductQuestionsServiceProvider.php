<?php

namespace ItmoSoft\ProductQuestions;

use Illuminate\Support\ServiceProvider;

class ProductQuestionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/product-questions.php', 'itmosoft-product-questions');
        $this->app->singleton(ProductQuestionManager::class, fn ($app) => new ProductQuestionManager(
            $app['config']->get('itmosoft-product-questions', [])
        ));
        $this->app->alias(ProductQuestionManager::class, 'itmosoft.product-questions');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/product-questions.php' => config_path('itmosoft-product-questions.php'),
            ], 'itmosoft-product-questions-config');
        }
    }
}
