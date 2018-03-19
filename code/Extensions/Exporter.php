<?php

namespace Marcz\Algolia\Extensions;

use SilverStripe\Core\Extension;

class Exporter extends Extension
{
    public function updateExport(&$data)
    {
        $data['objectID'] = (int) $data['ID'];
    }
}
