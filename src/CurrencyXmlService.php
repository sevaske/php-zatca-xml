<?php

namespace Saleh7\Zatca;

use Sabre\Xml\Service;
use Sabre\Xml\Writer;

/**
 * Sabre XML service that writes with a {@see CurrencyWriter}.
 */
class CurrencyXmlService extends Service
{
    public function __construct(private readonly string $currencyID) {}

    public function getWriter(): Writer
    {
        $writer = new CurrencyWriter;
        $writer->namespaceMap = $this->namespaceMap;
        $writer->classMap = $this->classMap;
        $writer->currencyID = $this->currencyID;

        return $writer;
    }
}
