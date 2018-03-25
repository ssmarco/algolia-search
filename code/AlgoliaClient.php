<?php

namespace Marcz\Algolia;

use SilverStripe\Core\Injector\Injectable;
use AlgoliaSearch\Client;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use Symbiote\QueuedJobs\Services\QueuedJobService;
use Marcz\Algolia\Jobs\JsonExport;
use SilverStripe\ORM\DataList;
use Marcz\Search\Config;

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

    public function createExportJob($className)
    {
        $list       = new DataList($className);
        $total      = $list->count();
        $pageLength = Config::config()->get('page_length');
        $totalPages = ceil($total / $pageLength);

        for ($offset = 0; $offset < $totalPages; $offset++) {
            $job = Injector::inst()->createWithArgs(
                JsonExport::class,
                [$className, $offset * $pageLength]
            );
            singleton(QueuedJobService::class)->queueJob($job);
        }
    }
}
