<?php

namespace Vendor\Xmldoc\Runtime;

use Vendor\Xmldoc\Crm\CrmEntityWriter;
use Vendor\Xmldoc\DataCollector;
use Vendor\Xmldoc\FileSaver;

/** Runtime генерации УПД для коробочной установки Bitrix24. */
final class BoxGenerateRuntime extends AbstractGenerateRuntime
{
    public function __construct(
        private readonly DataCollector $collector = new DataCollector(),
        private readonly FileSaver $fileSaver = new FileSaver(CrmEntityWriter::class),
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
