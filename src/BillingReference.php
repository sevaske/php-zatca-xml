<?php

namespace Saleh7\Zatca;

use InvalidArgumentException;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/**
 * Class BillingReference
 *
 * Represents a billing reference for an invoice.
 */
class BillingReference implements XmlSerializable
{
    /** @var string|null Identifier for the billing reference. */
    private ?string $id = null;

    private ?string $UUID = null;

    /**
     * Set the billing reference identifier.
     *
     * @param  string  $id  Identifier must not be empty.
     *
     * @throws InvalidArgumentException if the ID is empty.
     */
    public function setId(string $id): self
    {
        $id = trim($id);

        if ($id === '') {
            throw new InvalidArgumentException('ID cannot be empty.');
        }

        $this->id = $id;

        return $this;
    }

    public function setUUID(?string $value): self
    {
        $this->UUID = $value;

        return $this;
    }

    /**
     * Get the billing reference identifier.
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Serializes this object to XML.
     *
     * @param  Writer  $writer  The XML writer.
     */
    public function xmlSerialize(Writer $writer): void
    {
        $data = [];

        if ($this->id !== null) {
            $data[Schema::CBC.'ID'] = $this->id;
        }

        if ($this->UUID !== null) {
            $data[Schema::CBC.'UUID'] = $this->UUID;
        }

        if (! empty($data)) {
            $writer->write([
                Schema::CAC.'InvoiceDocumentReference' => $data,
            ]);
        }
    }
}
