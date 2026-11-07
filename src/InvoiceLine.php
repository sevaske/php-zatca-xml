<?php

namespace Saleh7\Zatca;

use InvalidArgumentException;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/**
 * Class InvoiceLine
 *
 * Represents an invoice line item and provides XML serialization.
 */
class InvoiceLine implements XmlSerializable
{
    /** @var string|null Identifier for the invoice line. */
    private ?string $id = null;

    /** @var float|null Invoiced quantity. */
    private ?float $invoicedQuantity = null;

    /** @var float|null Line extension amount. */
    private ?float $lineExtensionAmount = null;

    /** @var AllowanceCharge[]|null Array of allowance charge objects. */
    private ?array $allowanceCharges = null;

    /** @var DocumentReference|null Document Reference */
    private ?DocumentReference $documentReference = null;

    /** @var string Unit code (default 'MON'). */
    private string $unitCode = 'MON';

    /** @var TaxTotal|null Tax total details for the line. */
    private ?TaxTotal $taxTotal = null;

    // todo
    // private ?InvoicePeriod $invoicePeriod = null;

    /** @var string|null Note for the line. */
    private ?string $note = null;

    /** @var Item|null Item details. */
    private ?Item $item = null;

    /** @var Price|null Price details. */
    private ?Price $price = null;

    /** @var string|null Accounting cost code. */
    private ?string $accountingCostCode = null;

    /** @var string|null Accounting cost. */
    private ?string $accountingCost = null;

    /**
     * Get the invoice line ID.
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Set the invoice line ID.
     *
     * @throws InvalidArgumentException if ID is empty.
     */
    public function setId(?string $id): self
    {
        if ($id !== null && trim($id) === '') {
            throw new InvalidArgumentException('Invoice line ID cannot be empty.');
        }
        $this->id = $id;

        return $this;
    }

    /**
     * Get the invoiced quantity.
     */
    public function getInvoicedQuantity(): ?float
    {
        return $this->invoicedQuantity;
    }

    /**
     * Set the invoiced quantity.
     *
     * @throws InvalidArgumentException if quantity is negative.
     */
    public function setInvoicedQuantity(?float $invoicedQuantity): self
    {
        if ($invoicedQuantity !== null && $invoicedQuantity < 0) {
            throw new InvalidArgumentException('Invoiced quantity must be non-negative.');
        }
        $this->invoicedQuantity = $invoicedQuantity;

        return $this;
    }

    /**
     * Get the line extension amount.
     */
    public function getLineExtensionAmount(): ?float
    {
        return $this->lineExtensionAmount;
    }

    /**
     * Set the line extension amount.
     *
     * @throws InvalidArgumentException if amount is negative.
     */
    public function setLineExtensionAmount(?float $lineExtensionAmount): self
    {
        if ($lineExtensionAmount !== null && $lineExtensionAmount < 0) {
            throw new InvalidArgumentException('Line extension amount must be non-negative.');
        }
        $this->lineExtensionAmount = $lineExtensionAmount;

        return $this;
    }

    /**
     * Get the allowance charges.
     *
     * @return AllowanceCharge[]|null
     */
    public function getAllowanceCharges(): ?array
    {
        return $this->allowanceCharges;
    }

    /**
     * Set the allowance charges.
     *
     * @param  AllowanceCharge[]|null  $allowanceCharges
     */
    public function setAllowanceCharges(?array $allowanceCharges): self
    {
        $this->allowanceCharges = $allowanceCharges;

        return $this;
    }

    /**
     * Get the document reference
     */
    public function getDocumentReference(): ?DocumentReference
    {
        return $this->documentReference;
    }

    /**
     * Set the document reference
     */
    public function setDocumentReference(?DocumentReference $documentReference): self
    {
        $this->documentReference = $documentReference;

        return $this;
    }

    /**
     * Get the unit code.
     */
    public function getUnitCode(): string
    {
        return $this->unitCode;
    }

    /**
     * Set the unit code.
     *
     * @throws InvalidArgumentException if unit code is empty.
     */
    public function setUnitCode(?string $unitCode): self
    {
        if ($unitCode !== null && trim($unitCode) === '') {
            throw new InvalidArgumentException('Unit code cannot be empty.');
        }
        $this->unitCode = $unitCode ?? $this->unitCode;

        return $this;
    }

    /**
     * Get the tax total.
     */
    public function getTaxTotal(): ?TaxTotal
    {
        return $this->taxTotal;
    }

    /**
     * Set the tax total.
     */
    public function setTaxTotal(?TaxTotal $taxTotal): self
    {
        $this->taxTotal = $taxTotal;

        return $this;
    }

    /**
     * Get the note.
     */
    public function getNote(): ?string
    {
        return $this->note;
    }

    /**
     * Set the note.
     *
     * @throws InvalidArgumentException if note is provided as an empty string.
     */
    public function setNote(?string $note): self
    {
        if ($note !== null && trim($note) === '') {
            throw new InvalidArgumentException('Note cannot be empty if provided.');
        }
        $this->note = $note;

        return $this;
    }

    /**
     * todo
     * Get the invoice period.
     */
    //    public function getInvoicePeriod(): ?InvoicePeriod
    //    {
    //        return $this->invoicePeriod;
    //    }

    /**
     * todo
     * Set the invoice period.
     */
    //    public function setInvoicePeriod(?InvoicePeriod $invoicePeriod): self
    //    {
    //        $this->invoicePeriod = $invoicePeriod;
    //
    //        return $this;
    //    }

    /**
     * Get the item.
     */
    public function getItem(): ?Item
    {
        return $this->item;
    }

    /**
     * Set the item.
     */
    public function setItem(Item $item): self
    {
        $this->item = $item;

        return $this;
    }

    /**
     * Get the price.
     */
    public function getPrice(): ?Price
    {
        return $this->price;
    }

    /**
     * Set the price.
     */
    public function setPrice(?Price $price): self
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get the accounting cost code.
     */
    public function getAccountingCostCode(): ?string
    {
        return $this->accountingCostCode;
    }

    /**
     * Set the accounting cost code.
     *
     * @throws InvalidArgumentException if provided as an empty string.
     */
    public function setAccountingCostCode(?string $accountingCostCode): self
    {
        if ($accountingCostCode !== null && trim($accountingCostCode) === '') {
            throw new InvalidArgumentException('Accounting cost code cannot be empty.');
        }
        $this->accountingCostCode = $accountingCostCode;

        return $this;
    }

    /**
     * Get the accounting cost.
     */
    public function getAccountingCost(): ?string
    {
        return $this->accountingCost;
    }

    /**
     * Set the accounting cost.
     *
     * @throws InvalidArgumentException if provided as an empty string.
     */
    public function setAccountingCost(?string $accountingCost): self
    {
        if ($accountingCost !== null && trim($accountingCost) === '') {
            throw new InvalidArgumentException('Accounting cost cannot be empty.');
        }
        $this->accountingCost = $accountingCost;

        return $this;
    }

    /**
     * XML serialize the invoice line.
     *
     * @param  Writer  $writer  The XML writer.
     */
    public function xmlSerialize(Writer $writer): void
    {
        // Write ID element
        $writer->write([
            Schema::CBC.'ID' => $this->id,
        ]);

        // Write Note element if provided
        if (! empty($this->getNote())) {
            $writer->write([
                Schema::CBC.'Note' => $this->getNote(),
            ]);
        }

        // Write InvoicedQuantity and LineExtensionAmount elements with attributes
        $writer->write([
            [
                'name' => Schema::CBC.'InvoicedQuantity',
                'value' => number_format($this->invoicedQuantity ?? 0, 6, '.', ''),
                'attributes' => [
                    'unitCode' => $this->unitCode,
                ],
            ],
            [
                'name' => Schema::CBC.'LineExtensionAmount',
                'value' => number_format($this->lineExtensionAmount ?? 0, 2, '.', ''),
                'attributes' => [
                    'currencyID' => GeneratorInvoice::$currencyID,
                ],
            ],
        ]);

        // Element order follows the UBL 2.1 InvoiceLine schema.

        // Write AccountingCostCode if available
        if ($this->accountingCostCode !== null) {
            $writer->write([
                Schema::CBC.'AccountingCostCode' => $this->accountingCostCode,
            ]);
        }

        // Write AccountingCost if available
        if ($this->accountingCost !== null) {
            $writer->write([
                Schema::CBC.'AccountingCost' => $this->accountingCost,
            ]);
        }

        // Write DocumentReference element if available
        if ($this->documentReference !== null) {
            $writer->write([
                Schema::CAC.'DocumentReference' => $this->documentReference,
            ]);
        }

        // Write each AllowanceCharge element if available
        if ($this->allowanceCharges !== null) {
            foreach ($this->allowanceCharges as $allowanceCharge) {
                $writer->write([
                    Schema::CAC.'AllowanceCharge' => $allowanceCharge,
                ]);
            }
        }

        // Write TaxTotal element if available
        if ($this->taxTotal !== null) {
            $writer->write([
                Schema::CAC.'TaxTotal' => $this->taxTotal,
            ]);
        }

        // Write Item element (mandatory)
        $writer->write([
            Schema::CAC.'Item' => $this->item,
        ]);

        // Write Price element (mandatory: BR-26, every invoice line has an item net price)
        if ($this->price === null) {
            throw new InvalidArgumentException(sprintf('Invoice line "%s" has no price.', (string) $this->id));
        }

        $writer->write([
            Schema::CAC.'Price' => $this->price,
        ]);
    }
}
