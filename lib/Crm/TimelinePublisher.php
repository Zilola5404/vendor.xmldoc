<?php

namespace Ooofix\Xmlupd\Crm;

use Bitrix\Main\Loader;

/** Публикация события генерации УПД в таймлайн CRM. */
final class TimelinePublisher
{
    public static function publishDocumentGenerated(
        int $entityTypeId,
        int $entityId,
        string $fileName,
        int $fileId,
        int $version
    ): void {
        if (!Loader::includeModule('crm')) {
            return;
        }

        global $USER;
        $authorId = (int)($USER instanceof \CUser ? $USER->GetID() : 0);
        $authorId = $authorId > 0 ? $authorId : 1;

        $url = htmlspecialcharsbx((string)\CFile::GetPath($fileId));
        $text = sprintf(
            'Сформирован документ <a href="%s" target="_blank">%s</a>',
            $url,
            htmlspecialcharsbx($fileName)
        );

        $bindings = [
            ['ENTITY_TYPE_ID' => $entityTypeId, 'ENTITY_ID' => $entityId],
        ];

        if (self::publishLogMessage($text, $authorId, $bindings)) {
            return;
        }

        self::publishComment($text, $authorId, $bindings);
    }

    /** @param list<array{ENTITY_TYPE_ID: int, ENTITY_ID: int}> $bindings */
    private static function publishLogMessage(string $text, int $authorId, array $bindings): bool
    {
        if (!class_exists(\Bitrix\Crm\Timeline\LogMessageEntry::class)) {
            return false;
        }

        try {
            $id = \Bitrix\Crm\Timeline\LogMessageEntry::create([
                'TEXT'      => $text,
                'AUTHOR_ID' => $authorId,
                'BINDINGS'  => $bindings,
            ]);

            return (int)$id > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    /** @param list<array{ENTITY_TYPE_ID: int, ENTITY_ID: int}> $bindings */
    private static function publishComment(string $text, int $authorId, array $bindings): void
    {
        if (!class_exists(\Bitrix\Crm\Timeline\CommentEntry::class)) {
            return;
        }

        try {
            \Bitrix\Crm\Timeline\CommentEntry::create([
                'TEXT'      => $text,
                'AUTHOR_ID' => $authorId,
                'BINDINGS'  => $bindings,
            ]);
        } catch (\Throwable) {
            // Публикация в таймлайн не должна блокировать генерацию
        }
    }
}
