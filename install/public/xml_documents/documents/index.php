<?php

require dirname(__DIR__) . '/bootstrap.php';

use Ooofix\Xmlupd\Admin\DocumentsPageRenderer;
use Ooofix\Xmlupd\Portal\PortalPageController;

PortalPageController::boot('documents', 'Документы', static function (): void {
    DocumentsPageRenderer::render();
});
