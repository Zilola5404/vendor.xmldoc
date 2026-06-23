<?php

namespace Vendor\Xmldoc\Cloud;

use Vendor\Xmldoc\DataCollector;
use Vendor\Xmldoc\FileSaver;
use Vendor\Xmldoc\Runtime\AbstractGenerateRuntime;
use Vendor\Xmldoc\Cloud\Crm\CloudCrmEntityWriter;

/** Runtime генерации УПД для облачного Bitrix24. */
final class CloudGenerateRuntime extends AbstractGenerateRuntime
{
    public function __construct(
        private readonly CloudDataCollector $collector = new CloudDataCollector(),
        private readonly FileSaver $fileSaver = new FileSaver(CloudCrmEntityWriter::class),
    ) {
        parent::__construct();
    }

    protected function getCollector(): DataCollector
    {
        return $this->collector;
    }

    protected function getFileSaver(): FileSaver
    {
        return $this->fileSaver;
    }
}
