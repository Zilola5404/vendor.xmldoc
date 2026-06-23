<?php

namespace Vendor\Xmldoc\Cloud\Crm;

use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Vendor\Xmldoc\Config;

/** Вызов REST через входящий webhook (облако B24). */
final class RestWebhookClient
{
    public static function call(string $method, array $params = []): ?array
    {
        $webhook = trim(Config::cloudRestWebhook());
        if ($webhook === '') {
            return null;
        }

        $url = rtrim($webhook, '/') . '/' . ltrim($method, '/') . '/';

        try {
            $client = new HttpClient(['socketTimeout' => 15, 'streamTimeout' => 15]);
            $response = $client->post($url, Json::encode($params));
            if ($response === false || $response === '') {
                return null;
            }

            $data = Json::decode($response);
            if (!is_array($data)) {
                return null;
            }

            if (isset($data['error'])) {
                return null;
            }

            return $data;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function executeAutomationTrigger(string $code, int $ownerTypeId, int $ownerId): bool
    {
        $result = self::call('crm.automation.trigger', [
            'CODE'          => $code,
            'OWNER_TYPE_ID' => $ownerTypeId,
            'OWNER_ID'      => $ownerId,
        ]);

        return is_array($result) && !empty($result['result']);
    }
}
