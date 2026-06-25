<?php

namespace Ooofix\Xmlupd\Runtime;

use Ooofix\Xmlupd\Crm\CrmEntityWriter;
use Ooofix\Xmlupd\DataCollector;
use Ooofix\Xmlupd\FileSaver;

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
