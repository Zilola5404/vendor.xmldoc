<?php

declare(strict_types=1);

namespace Vendor\Xmldoc\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Vendor\Xmldoc\ValidationMessages;

final class ValidationMessagesTest extends TestCase
{
    public function testGetKnownKey(): void
    {
        $message = ValidationMessages::get('buyer_inn');

        $this->assertStringContainsString('ИНН', $message);
    }

    public function testFromMapKey(): void
    {
        $message = ValidationMessages::fromMapKey('products');

        $this->assertStringContainsString('товар', mb_strtolower($message));
    }

    public function testFromLabel(): void
    {
        $message = ValidationMessages::fromLabel('ИНН покупателя');

        $this->assertStringContainsString('ИНН', $message);
    }

    public function testFormatList(): void
    {
        $formatted = ValidationMessages::formatList(['Ошибка 1', 'Ошибка 2']);

        $this->assertStringContainsString('Ошибка 1', $formatted);
        $this->assertStringContainsString('Ошибка 2', $formatted);
    }

    public function testProductQuantityMessage(): void
    {
        $message = ValidationMessages::productQuantity(3, 'Болт М8');

        $this->assertStringContainsString('3', $message);
        $this->assertStringContainsString('Болт М8', $message);
    }
}
