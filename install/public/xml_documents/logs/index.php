<?php

require dirname(__DIR__) . '/bootstrap.php';

use Ooofix\Xmlupd\Admin\LogPageRenderer;
use Ooofix\Xmlupd\Portal\PortalPageController;

PortalPageController::boot('logs', 'Логи', static function (): void {
    LogPageRenderer::render();
});
