<?php

namespace Vendor\Xmldoc\Xml;

/**
 * Лёгкая замена PHP XMLWriter на DOMDocument (ext-dom обычно есть на B24).
 * API повторяет только те методы, которые использует UpdXmlWriter.
 */
final class WriterBuffer
{
    private \DOMDocument $dom;

    /** @var \DOMElement[] */
    private array $stack = [];

    public function openMemory(): void
    {
        $this->stack = [];
    }

    public function startDocument(string $version, string $encoding): void
    {
        if (!class_exists(\DOMDocument::class)) {
            throw new \RuntimeException(
                'На сервере не установлено расширение PHP DOM (ext-dom). Обратитесь к администратору портала.'
            );
        }

        $this->dom = new \DOMDocument($version, $encoding);
        $this->dom->encoding = $encoding;
        $this->dom->formatOutput = false;
    }

    public function startElement(string $name): void
    {
        $element = $this->dom->createElement($name);

        if ($this->stack === []) {
            $this->dom->appendChild($element);
        } else {
            $this->current()->appendChild($element);
        }

        $this->stack[] = $element;
    }

    public function writeAttribute(string $name, string $value): void
    {
        if ($this->stack === []) {
            return;
        }

        $this->current()->setAttribute($name, $value);
    }

    public function text(string $content): void
    {
        if ($this->stack === []) {
            return;
        }

        $this->current()->appendChild($this->dom->createTextNode($content));
    }

    public function endElement(): void
    {
        if ($this->stack !== []) {
            array_pop($this->stack);
        }
    }

    public function outputMemory(): string
    {
        $xml = $this->dom->saveXML();

        return is_string($xml) ? $xml : '';
    }

    private function current(): \DOMElement
    {
        return $this->stack[count($this->stack) - 1];
    }
}
