<?php

namespace Ooofix\Xmlupd\Edo;

use Ooofix\Xmlupd\Contract\EdoGatewayInterface;

/** Заглушка: отправка в ЭДО будет на этапе 2 */
class NullEdoGateway implements EdoGatewayInterface
{
    public function send(string $xml, array $context): array
    {
        return [
            'success' => false,
            'message' => 'Интеграция с ЭДО не подключена',
        ];
    }
}
