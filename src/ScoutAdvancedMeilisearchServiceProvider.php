<?php

namespace Omure\ScoutAdvancedMeilisearch;

use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;
use MeiliSearch\Client;
use Omure\ScoutAdvancedMeilisearch\Engines\CollectionMeiliSearchTestEngine;
use Omure\ScoutAdvancedMeilisearch\Engines\MeiliSearchExtendedEngine;
use Omure\ScoutAdvancedMeilisearch\Facades\MeiliSearch\MeiliSearchService;

class ScoutAdvancedMeilisearchServiceProvider extends ServiceProvider
{
    public function register()
    {
        if (config('scout.driver') !== 'meilisearch_advanced') {
            return;
        }

        $this->app->bind('meiliSearch', function () {
            return new MeiliSearchService(app()->make(Client::class));
        });
    }

    public function boot()
    {
        if (config('scout.driver') !== 'meilisearch_advanced') {
            return;
        }

        resolve(EngineManager::class)->extend('collection_advanced', function () {
            return new CollectionMeiliSearchTestEngine();
        });

        resolve(EngineManager::class)->extend('meilisearch_advanced', function () {
            return new MeiliSearchExtendedEngine(
                new Client(
                    config('scout.meilisearch.host'), config('scout.meilisearch.key')
                ),
                config('scout.soft_delete', false)
            );
        });
    }
}
