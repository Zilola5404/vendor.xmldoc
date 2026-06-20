<?php

namespace Vendor\Xmldoc;

use DOMDocument;

/** XSD-валидация готовой XML-строки (DOM только здесь) */
class XmlValidator
{
    /**
     * @return array{valid: bool, errors: string[]}
     */
    public function validateDetailed(string $xml, ?string $xsdPath = null): array
    {
        $xsdPath = $xsdPath ?? Config::xsdPath();
        if ($xsdPath === '' || !is_file($xsdPath)) {
            $valid = $this->validateWellFormed($xml);

            return ['valid' => $valid, 'errors' => $valid ? [] : ['XML не well-formed']];
        }

        $dom = new DOMDocument();
        if (@$dom->loadXML($xml) === false) {
            return ['valid' => false, 'errors' => ['Не удалось разобрать XML']];
        }

        libxml_use_internal_errors(true);
        $valid = (bool)$dom->schemaValidate($xsdPath);
        $errors = [];
        if (!$valid) {
            foreach (libxml_get_errors() as $error) {
                $errors[] = trim($error->message);
            }
        }
        libxml_clear_errors();

        return ['valid' => $valid, 'errors' => $errors];
    }

    public function validate(string $xml, ?string $xsdPath = null): bool
    {
        return $this->validateDetailed($xml, $xsdPath)['valid'];
    }

    private function validateWellFormed(string $xml): bool
    {
        $dom = new DOMDocument();

        return @$dom->loadXML($xml) !== false;
    }
}
