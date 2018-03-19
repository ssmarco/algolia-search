<?php

namespace Marcz\Algolia;

use SilverStripe\Core\Injector\Injectable;
use AlgoliaSearch\Client;
use SilverStripe\Core\Environment;

class AlgoliaClient
{
    use Injectable;

    public function update($data)
    {
        $appName = Environment::getEnv('SS_ALGOLIA_APP_NAME');
        $appKey  = Environment::getEnv('SS_ALGOLIA_SEARCH_KEY');
        $client  = new Client($appName, $appKey);
        $index   = $client->initIndex('ss_products');

        $index->saveObject($data);
    }
}
