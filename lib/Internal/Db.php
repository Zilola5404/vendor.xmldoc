<?php

namespace Ooofix\Xmlupd\Internal;

use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Type\DateTime;

/** Безопасные INSERT/SELECT для таблиц модуля (без биндингов в queryExecute). */
final class Db
{
    /**
     * @param array<string, scalar|null> $fields
     * @throws SqlQueryException
     */
    public static function insert(string $table, array $fields): void
    {
        $connection = Application::getConnection();
        $helper = $connection->getSqlHelper();

        $columns = [];
        $values = [];

        foreach ($fields as $column => $value) {
            $columns[] = $helper->quote((string)$column);
            if ($value instanceof DateTime) {
                $values[] = $helper->getDateTimeFunction($value);
            } else {
                $values[] = self::sqlValue($helper, $value);
            }
        }

        $connection->queryExecute(
            'INSERT INTO ' . $helper->quote($table)
            . ' (' . implode(', ', $columns) . ')'
            . ' VALUES (' . implode(', ', $values) . ')'
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function fetchAll(string $sql): array
    {
        $rows = [];
        $result = Application::getConnection()->query($sql);

        while ($row = $result->fetch()) {
            $rows[] = $row;
        }

        return $rows;
    }

    public static function nowExpression(): string
    {
        return Application::getConnection()->getSqlHelper()->getDateTimeFunction(new DateTime());
    }

    /** @param mixed $value */
    private static function sqlValue($helper, $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_int($value) || is_float($value)) {
            return (string)(0 + $value);
        }

        return "'" . $helper->forSql((string)$value) . "'";
    }
}
