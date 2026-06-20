<?php

namespace Vendor\Xmldoc;

use Vendor\Xmldoc\Contract\ConfigInterface;

/** Настройки модуля из b_option (фасад над ConfigInterface). */
class Config
{
    private static ?ConfigInterface $instance = null;

    public static function setInstance(?ConfigInterface $instance): void
    {
        self::$instance = $instance;
    }

    private static function i(): ConfigInterface
    {
        return self::$instance ??= new ModuleConfig();
    }

    public static function dadataApiKey(): string
    {
        return self::i()->dadataApiKey();
    }

    public static function sellerRequisiteId(): int
    {
        return self::i()->sellerRequisiteId();
    }

    public static function signatoryUserId(): int
    {
        return self::i()->signatoryUserId();
    }

    public static function signatoryMode(): string
    {
        return self::i()->signatoryMode();
    }

    public static function signatoryPosition(): string
    {
        return self::i()->signatoryPosition();
    }

    public static function smartInvoiceTypeId(): int
    {
        return self::i()->smartInvoiceTypeId();
    }

    public static function publishTimeline(): bool
    {
        return self::i()->publishTimeline();
    }

    public static function xsdPath(): string
    {
        return self::i()->xsdPath();
    }

    public static function updFunction(): string
    {
        return self::i()->updFunction();
    }

    public static function fileEncoding(): string
    {
        return self::i()->fileEncoding();
    }

    public static function mappingPath(): string
    {
        return self::i()->mappingPath();
    }
}
