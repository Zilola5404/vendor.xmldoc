<?php

namespace Vendor\Xmldoc\Cloud\Crm;

use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Vendor\Xmldoc\Config;
use Vendor\Xmldoc\Logger;

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
                self::logError($method . ': пустой ответ');

                return null;
            }

            $data = Json::decode($response);
            if (!is_array($data)) {
                self::logError($method . ': некорректный JSON');

                return null;
            }

            if (isset($data['error'])) {
                self::logError(
                    $method . ': ' . (string)($data['error_description'] ?? $data['error'])
                );

                return null;
            }

            return $data;
        } catch (\Throwable $e) {
            self::logError($method . ': ' . $e->getMessage());

            return null;
        }
    }

    public static function executeAutomationTrigger(string $code, int $ownerTypeId, int $ownerId): bool
    {
        $params = [
            'CODE'          => $code,
            'OWNER_TYPE_ID' => $ownerTypeId,
            'OWNER_ID'      => $ownerId,
        ];

        foreach (['crm.automation.trigger.execute', 'crm.automation.trigger'] as $method) {
            $result = self::call($method, $params);
            if (is_array($result) && array_key_exists('result', $result)) {
                return true;
            }
        }

        return false;
    }

    private static function logError(string $message): void
    {
        Logger::write('system', 0, Logger::STATUS_ERROR, 'REST webhook: ' . $message);
    }
}
