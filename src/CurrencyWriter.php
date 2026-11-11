<?php

namespace Saleh7\Zatca;

use LogicException;
use Sabre\Xml\Writer;

/**
 * XML writer that carries the currency of the document being written.
 *
 * The currency lives on the writer instance, so every serialization has its own context and
 * concurrent or interleaved generations cannot affect each other.
 */
class CurrencyWriter extends Writer
{
    /** Currency of the amounts written by this writer. */
    public ?string $currencyID = null;

    /**
     * Currency of the document written by the given writer.
     *
     * @throws LogicException if the writer carries no currency (amounts serialized outside GeneratorInvoice).
     */
    public static function currencyOf(Writer $writer): string
    {
        if (! $writer instanceof self || $writer->currencyID === null || $writer->currencyID === '') {
            throw new LogicException(
                'The document currency is unknown: serialize amounts with a '.self::class.' that has currencyID set '
                .'(GeneratorInvoice::invoice() does this).'
            );
        }

        return $writer->currencyID;
    }
}
