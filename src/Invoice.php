<?php

namespace Saleh7\Zatca;

use DateTime;
use InvalidArgumentException;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/**
 * Class Invoice
 *
 * Represents an invoice and provides methods to serialize it to XML.
 */
class Invoice implements XmlSerializable
{
    /** @var UBLExtensions|null UBL extensions. */
    private ?UBLExtensions $UBLExtensions = null;

    /** @var string Profile ID. */
    private string $profileID = 'reporting:1.0';

    /** @var string|null Invoice identifier. */
    private ?string $id = null;

    /** @var string|null Invoice UUID. */
    private ?string $UUID = null;

    /** @var DateTime|null Issue date. */
    private ?DateTime $issueDate = null;

    /** @var DateTime|null Issue time. */
    private ?DateTime $issueTime = null;

    /** @var InvoiceType|null Invoice type. */
    private ?InvoiceType $invoiceType = null;

    /** @var string|null Note. */
    private ?string $note = null;

    private string $languageID = 'en';

    /** @var string Invoice currency code. */
    private string $invoiceCurrencyCode = 'SAR';

    /** @var string Tax currency code. */
    private string $taxCurrencyCode = 'SAR';

    /** @var string Document currency code. */
    private string $documentCurrencyCode = 'SAR';

    /** @var OrderReference|null Order reference. */
    private ?OrderReference $orderReference = null;

    /** @var BillingReference[]|null Array of billing references. */
    private ?array $billingReferences = null;

    /** @var Contract|null Contract reference. */
    private ?Contract $contract = null;

    /** @var AdditionalDocumentReference[]|null Additional document references. */
    private ?array $additionalDocumentReferences = null;

    /** @var Party|null Accounting supplier party. */
    private ?Party $accountingSupplierParty = null;

    /** @var Party|null Accounting customer party. */
    private ?Party $accountingCustomerParty = null;

    /** @var Delivery|null Delivery details. */
    private ?Delivery $delivery = null;

    /** @var PaymentMeans|null Payment means. */
    private ?PaymentMeans $paymentMeans = null;

    /** @var AllowanceCharge[]|null Array of allowance/charge details. */
    private ?array $allowanceCharges = null;

    /** @var TaxTotal|null Tax total details. */
    private ?TaxTotal $taxTotal = null;

    /** @var LegalMonetaryTotal|null Legal monetary total. */
    private ?LegalMonetaryTotal $legalMonetaryTotal = null;

    /** @var InvoiceLine[]|null Array of invoice lines. */
    private ?array $invoiceLines = null;

    /** @var Signature|null Signature information. */
    private ?Signature $signature = null;

    // Getters

    public function getUBLExtensions(): ?UBLExtensions
    {
        return $this->UBLExtensions;
    }

    public function getProfileID(): string
    {
        return $this->profileID;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUUID(): ?string
    {
        return $this->UUID;
    }

    public function getIssueDate(): ?DateTime
    {
        return $this->issueDate;
    }

    public function getIssueTime(): ?DateTime
    {
        return $this->issueTime;
    }

    public function getInvoiceType(): ?InvoiceType
    {
        return $this->invoiceType;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function getInvoiceCurrencyCode(): string
    {
        return $this->invoiceCurrencyCode;
    }

    public function getTaxCurrencyCode(): string
    {
        return $this->taxCurrencyCode;
    }

    public function getDocumentCurrencyCode(): string
    {
        return $this->documentCurrencyCode;
    }

    public function getOrderReference(): ?OrderReference
    {
        return $this->orderReference;
    }

    /**
     * @return BillingReference[]|null
     */
    public function getBillingReferences(): ?array
    {
        return $this->billingReferences;
    }

    public function getContract(): ?Contract
    {
        return $this->contract;
    }

    /**
     * @return AdditionalDocumentReference[]|null
     */
    public function getAdditionalDocumentReferences(): ?array
    {
        return $this->additionalDocumentReferences;
    }

    public function getAccountingSupplierParty(): ?Party
    {
        return $this->accountingSupplierParty;
    }

    public function getAccountingCustomerParty(): ?Party
    {
        return $this->accountingCustomerParty;
    }

    public function getDelivery(): ?Delivery
    {
        return $this->delivery;
    }

    public function getPaymentMeans(): ?PaymentMeans
    {
        return $this->paymentMeans;
    }

    /**
     * @return AllowanceCharge[]|null
     */
    public function getAllowanceCharges(): ?array
    {
        return $this->allowanceCharges;
    }

    public function getTaxTotal(): ?TaxTotal
    {
        return $this->taxTotal;
    }

    public function getLegalMonetaryTotal(): ?LegalMonetaryTotal
    {
        return $this->legalMonetaryTotal;
    }

    /**
     * @return InvoiceLine[]|null
     */
    public function getInvoiceLines(): ?array
    {
        return $this->invoiceLines;
    }

    public function getSignature(): ?Signature
    {
        return $this->signature;
    }

    // Setters

    /**
     * Set UBLExtensions.
     */
    public function setUBLExtensions(UBLExtensions $UBLExtensions): self
    {
        $this->UBLExtensions = $UBLExtensions;

        return $this;
    }

    /**
     * Set invoice ID.
     *
     * @throws InvalidArgumentException if ID is empty.
     */
    public function setId(?string $id): self
    {
        if ($id !== null && trim($id) === '') {
            throw new InvalidArgumentException('Missing invoice id.');
        }
        $this->id = $id;

        return $this;
    }

    /**
     * Set invoice UUID.
     *
     * @throws InvalidArgumentException if UUID is empty.
     */
    public function setUUID(?string $UUID): self
    {
        if ($UUID !== null && trim($UUID) === '') {
            throw new InvalidArgumentException('Invoice UUID cannot be empty.');
        }
        $this->UUID = $UUID;

        return $this;
    }

    /**
     * Set issue date.
     */
    public function setIssueDate(DateTime $issueDate): self
    {
        $this->issueDate = $issueDate;

        return $this;
    }

    /**
     * Set issue time.
     */
    public function setIssueTime(DateTime $issueTime): self
    {
        $this->issueTime = $issueTime;

        return $this;
    }

    /**
     * Set invoice type.
     */
    public function setInvoiceType(InvoiceType $invoiceType): self
    {
        $this->invoiceType = $invoiceType;

        return $this;
    }

    /**
     * Set note.
     */
    public function setNote(?string $note): self
    {
        if ($note !== null) {
            $this->note = $note;
        }

        return $this;
    }

    public function setLanguageID(string $languageID = 'en'): self
    {
        if (trim($languageID) === '') {
            throw new InvalidArgumentException('languageID cannot be empty.');
        }

        $this->languageID = $languageID;

        return $this;
    }

    /**
     * Set invoice currency code.
     *
     * @throws InvalidArgumentException if currency code is empty.
     */
    public function setInvoiceCurrencyCode(string $currencyCode = 'SAR'): self
    {
        if (trim($currencyCode) === '') {
            throw new InvalidArgumentException('Invoice currency code cannot be empty.');
        }
        $this->invoiceCurrencyCode = $currencyCode;

        return $this;
    }

    /**
     * Set tax currency code.
     *
     * @throws InvalidArgumentException if currency code is empty.
     */
    public function setTaxCurrencyCode(string $currencyCode = 'SAR'): self
    {
        if (trim($currencyCode) === '') {
            throw new InvalidArgumentException('Tax currency code cannot be empty.');
        }
        $this->taxCurrencyCode = $currencyCode;

        return $this;
    }

    /**
     * Set document currency code.
     *
     * @throws InvalidArgumentException if currency code is empty.
     */
    public function setDocumentCurrencyCode(string $currencyCode = 'SAR'): self
    {
        if (trim($currencyCode) === '') {
            throw new InvalidArgumentException('Document currency code cannot be empty.');
        }

        $this->documentCurrencyCode = $currencyCode;

        return $this;
    }

    /**
     * Set order reference.
     */
    public function setOrderReference(OrderReference $orderReference): self
    {
        $this->orderReference = $orderReference;

        return $this;
    }

    /**
     * Set billing references.
     *
     * @param  BillingReference[]  $billingReferences
     */
    public function setBillingReferences(array $billingReferences): self
    {
        $this->billingReferences = $billingReferences;

        return $this;
    }

    /**
     * Set contract.
     */
    public function setContract(Contract $contract): self
    {
        $this->contract = $contract;

        return $this;
    }

    /**
     * Set additional document references.
     *
     * @param  AdditionalDocumentReference[]  $additionalDocumentReferences
     */
    public function setAdditionalDocumentReferences(array $additionalDocumentReferences): self
    {
        $this->additionalDocumentReferences = $additionalDocumentReferences;

        return $this;
    }

    /**
     * Set accounting supplier party.
     */
    public function setAccountingSupplierParty(Party $accountingSupplierParty): self
    {
        $this->accountingSupplierParty = $accountingSupplierParty;

        return $this;
    }

    /**
     * Set accounting customer party.
     */
    public function setAccountingCustomerParty(Party $accountingCustomerParty): self
    {
        $this->accountingCustomerParty = $accountingCustomerParty;

        return $this;
    }

    /**
     * Set delivery details.
     */
    public function setDelivery(Delivery $delivery): self
    {
        // Check if at least one field in Delivery is not empty.
        if (
            $delivery->getActualDeliveryDate() !== null ||
            $delivery->getLatestDeliveryDate() !== null ||
            $delivery->getDeliveryLocation() !== null
        ) {
            $this->delivery = $delivery;
        }

        return $this;
    }

    /**
     * Set payment means.
     */
    public function setPaymentMeans(PaymentMeans $paymentMeans): self
    {
        $this->paymentMeans = $paymentMeans;

        return $this;
    }

    /**
     * Set allowance charges.
     *
     * @param  AllowanceCharge[]  $allowanceCharges
     */
    public function setAllowanceCharges(array $allowanceCharges): self
    {
        $this->allowanceCharges = $allowanceCharges;

        return $this;
    }

    /**
     * Set signature.
     */
    public function setSignature(Signature $signature): self
    {
        $this->signature = $signature;

        return $this;
    }

    /**
     * Set tax total.
     */
    public function setTaxTotal(TaxTotal $taxTotal): self
    {
        $this->taxTotal = $taxTotal;

        return $this;
    }

    /**
     * Set legal monetary total.
     */
    public function setLegalMonetaryTotal(LegalMonetaryTotal $legalMonetaryTotal): self
    {
        $this->legalMonetaryTotal = $legalMonetaryTotal;

        return $this;
    }

    /**
     * Set invoice lines.
     *
     * @param  InvoiceLine[]  $invoiceLines
     */
    public function setInvoiceLines(array $invoiceLines): self
    {
        $this->invoiceLines = $invoiceLines;

        return $this;
    }

    /**
     * Validates required invoice data before XML serialization.
     *
     * @throws InvalidArgumentException if required data is missing.
     */
    public function validate(): void
    {
        if ($this->id === null) {
            throw new InvalidArgumentException('Missing invoice id.');
        }
        if (! $this->issueDate instanceof DateTime) {
            throw new InvalidArgumentException('Invalid invoice issueDate.');
        }
        if (! $this->issueTime instanceof DateTime) {
            throw new InvalidArgumentException('Invalid invoice issueTime.');
        }
        if ($this->accountingSupplierParty === null) {
            throw new InvalidArgumentException('Missing invoice accountingSupplierParty.');
        }
        if ($this->accountingCustomerParty === null) {
            throw new InvalidArgumentException('Missing invoice accountingCustomerParty.');
        }
        if ($this->additionalDocumentReferences === null) {
            throw new InvalidArgumentException('Missing invoice additionalDocumentReferences.');
        }
        if ($this->invoiceLines === null) {
            throw new InvalidArgumentException('Missing invoice lines.');
        }
        if ($this->legalMonetaryTotal === null) {
            throw new InvalidArgumentException('Missing invoice LegalMonetaryTotal.');
        }
    }

    /**
     * Serializes the invoice to XML.
     *
     * @param  Writer  $writer  The XML writer.
     */
    public function xmlSerialize(Writer $writer): void
    {
        $this->validate();

        // UBLExtensions
        if ($this->UBLExtensions !== null) {
            $writer->write([
                Schema::EXT.'UBLExtensions' => $this->UBLExtensions,
            ]);
        }

        // ProfileID
        $writer->write([Schema::CBC.'ProfileID' => $this->profileID]);

        // ID
        if ($this->id !== null) {
            $writer->write([Schema::CBC.'ID' => $this->id]);
        }

        // UUID
        if ($this->UUID !== null) {
            $writer->write([Schema::CBC.'UUID' => $this->UUID]);
        }

        // IssueDate
        if ($this->issueDate !== null) {
            $writer->write([Schema::CBC.'IssueDate' => $this->issueDate->format('Y-m-d')]);
        }

        // IssueTime
        if ($this->issueTime !== null) {
            $writer->write([Schema::CBC.'IssueTime' => $this->issueTime->format('H:i:s')]);
        }

        // InvoiceType
        if ($this->invoiceType !== null) {
            $writer->write([$this->invoiceType]);
        }

        // Note
        if ($this->note !== null) {
            $writer->write([
                [
                    'name' => Schema::CBC.'Note',
                    'value' => $this->note,
                    'attributes' => [
                        'languageID' => $this->languageID,
                    ],
                ],
            ]);
        }

        // DocumentCurrencyCode
        if ($this->invoiceCurrencyCode) {
            $writer->write([Schema::CBC.'DocumentCurrencyCode' => $this->invoiceCurrencyCode]);
        }

        // TaxCurrencyCode
        if ($this->taxCurrencyCode) {
            $writer->write([Schema::CBC.'TaxCurrencyCode' => $this->taxCurrencyCode]);
        }

        // OrderReference
        if ($this->orderReference !== null) {
            $writer->write([Schema::CAC.'OrderReference' => $this->orderReference]);
        }

        // BillingReference(s)
        if ($this->billingReferences !== null) {
            foreach ($this->billingReferences as $billingReference) {
                $writer->write([
                    Schema::CAC.'BillingReference' => $billingReference,
                ]);
            }
        }

        // ContractDocumentReference
        if ($this->contract !== null) {
            $writer->write([
                Schema::CAC.'ContractDocumentReference' => $this->contract,
            ]);
        }

        // AdditionalDocumentReference(s)
        if ($this->additionalDocumentReferences !== null) {
            foreach ($this->additionalDocumentReferences as $additionalDocumentReference) {
                $writer->write([
                    Schema::CAC.'AdditionalDocumentReference' => $additionalDocumentReference,
                ]);
            }
        }

        // Signature
        if ($this->signature !== null) {
            $writer->write([Schema::CAC.'Signature' => $this->signature]);
        }

        // AccountingSupplierParty / Party
        if ($this->accountingSupplierParty !== null) {
            $writer->write([
                Schema::CAC.'AccountingSupplierParty' => [
                    Schema::CAC.'Party' => $this->accountingSupplierParty,
                ],
            ]);
        }

        // AccountingCustomerParty / Party
        if ($this->accountingCustomerParty !== null) {
            $writer->write([
                Schema::CAC.'AccountingCustomerParty' => [
                    Schema::CAC.'Party' => $this->accountingCustomerParty,
                ],
            ]);
        }

        // Delivery
        if ($this->delivery !== null) {
            $writer->write([Schema::CAC.'Delivery' => $this->delivery]);
        }

        // PaymentMeans
        if ($this->paymentMeans !== null) {
            $writer->write([Schema::CAC.'PaymentMeans' => $this->paymentMeans]);
        }

        // AllowanceCharge(s)
        if ($this->allowanceCharges !== null) {
            foreach ($this->allowanceCharges as $allowanceCharge) {
                $writer->write([
                    Schema::CAC.'AllowanceCharge' => $allowanceCharge,
                ]);
            }
        }

        // TaxTotal
        if ($this->taxTotal !== null) {
            // If taxAmount is separately formatted
            if (isset($this->taxTotal->taxAmount) && $this->taxTotal->taxAmount !== null) {
                $writer->write([
                    Schema::CAC.'TaxTotal' => [
                        Schema::CBC.'TaxAmount' => [
                            'value' => number_format($this->taxTotal->taxAmount, 2, '.', ''),
                            'attributes' => [
                                'currencyID' => GeneratorInvoice::$currencyID,
                            ],
                        ],
                    ],
                ]);
            }
            $writer->write([Schema::CAC.'TaxTotal' => $this->taxTotal]);
        }

        // LegalMonetaryTotal
        if ($this->legalMonetaryTotal !== null) {
            $writer->write([Schema::CAC.'LegalMonetaryTotal' => $this->legalMonetaryTotal]);
        }

        // InvoiceLine(s)
        if ($this->invoiceLines !== null) {
            foreach ($this->invoiceLines as $invoiceLine) {
                $writer->write([
                    Schema::CAC.'InvoiceLine' => $invoiceLine,
                ]);
            }
        }
    }
}
