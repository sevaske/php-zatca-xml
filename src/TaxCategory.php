<?php

namespace Saleh7\Zatca;

use InvalidArgumentException;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/**
 * Class TaxCategory
 *
 * Represents a tax category for an invoice with XML serialization.
 */
class TaxCategory implements XmlSerializable
{
    /** @var string|null Tax category identifier. */
    private ?string $id = null;

    /** @var array Attributes for the ID element. */
    private array $idAttributes = [
        'schemeID' => self::UNCL5305,
        'schemeAgencyID' => '6',
    ];

    /** @var string|null Tax category name. */
    private ?string $name = null;

    /** @var float|null Tax percentage. */
    private ?float $percent = null;

    /** @var TaxScheme|null Tax scheme object. */
    private ?TaxScheme $taxScheme = null;

    /** @var array Attributes for the TaxScheme element. */
    private array $taxSchemeAttributes = [
        'schemeID' => self::UNCL5153,
        'schemeAgencyID' => '6',
    ];

    /** @var string|null Tax exemption reason. */
    private ?string $taxExemptionReason = null;

    /** @var string|null Tax exemption reason code. */
    private ?string $taxExemptionReasonCode = null;

    public const UNCL5305 = 'UN/ECE 5305';

    public const UNCL5153 = 'UN/ECE 5153';

    /**
     * Get the tax category identifier.
     *
     * If not explicitly set, it is derived from the percent value.
     */
    public function getId(): ?string
    {
        // Explicit ID always has priority (E, O, custom handling)
        if (! empty($this->id)) {
            return $this->id;
        }

        // Auto-derive only for ZATCA-supported cases
        if ($this->getPercent() !== null) {
            // Any positive VAT rate in KSA is Standard rated
            if ($this->getPercent() > 0) {
                return 'S';
            }

            // Zero percent without explicit exemption = Zero rated
            return 'Z';
        }

        return null;
    }

    /**
     * Set the tax category identifier.
     *
     * @param  array|null  $attributes  Optional attributes to override default ID attributes.
     */
    public function setId(?string $id, ?array $attributes = null): self
    {
        $this->id = $id;
        if ($attributes !== null) {
            $this->idAttributes = $attributes;
        }

        return $this;
    }

    /**
     * Set the tax category name.
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the tax percentage.
     */
    public function getPercent(): ?float
    {
        return $this->percent;
    }

    /**
     * Set the tax percentage.
     */
    public function setPercent(?float $percent): self
    {
        $this->percent = $percent;

        return $this;
    }

    /**
     * Get the tax scheme.
     */
    public function getTaxScheme(): ?TaxScheme
    {
        return $this->taxScheme;
    }

    /**
     * Set the tax scheme.
     *
     * @param  array|null  $attributes  Optional attributes to override default TaxScheme attributes.
     */
    public function setTaxScheme(?TaxScheme $taxScheme, ?array $attributes = null): self
    {
        $this->taxScheme = $taxScheme;
        if ($attributes !== null) {
            $this->taxSchemeAttributes = $attributes;
        }

        return $this;
    }

    /**
     * Get the tax exemption reason.
     */
    public function getTaxExemptionReason(): ?string
    {
        return $this->taxExemptionReason;
    }

    /**
     * Set the tax exemption reason.
     */
    public function setTaxExemptionReason(?string $taxExemptionReason): self
    {
        $this->taxExemptionReason = $taxExemptionReason;

        return $this;
    }

    /**
     * Set the tax exemption reason code.
     */
    public function setTaxExemptionReasonCode(?string $taxExemptionReasonCode): self
    {
        $this->taxExemptionReasonCode = $taxExemptionReasonCode;

        return $this;
    }

    /**
     * Validate required fields before serialization.
     *
     * @throws InvalidArgumentException if required fields are missing.
     */
    public function validate(): void
    {
        if ($this->getId() === null) {
            throw new InvalidArgumentException('Missing tax category id.');
        }
        if ($this->getPercent() === null) {
            throw new InvalidArgumentException('Missing tax category percent.');
        }
    }

    /**
     * Serializes this object to XML.
     */
    public function xmlSerialize(Writer $writer): void
    {
        $this->validate();

        // Write ID element with attributes
        $writer->write([
            [
                'name' => Schema::CBC.'ID',
                'value' => $this->getId(),
                'attributes' => $this->idAttributes,
            ],
        ]);

        if ($this->name !== null) {
            $writer->write([
                Schema::CBC.'Name' => $this->name,
            ]);
        }

        // Write percent without decimals
        $writer->write([
            Schema::CBC.'Percent' => number_format($this->percent, 2, '.', ''),
        ]);

        if ($this->taxExemptionReasonCode !== null) {
            $writer->write([
                Schema::CBC.'TaxExemptionReasonCode' => $this->taxExemptionReasonCode,
            ]);
        }

        if ($this->taxExemptionReason !== null) {
            $writer->write([
                Schema::CBC.'TaxExemptionReason' => $this->taxExemptionReason,
            ]);
        }

        if ($this->taxScheme !== null) {
            $writer->write([
                [
                    'name' => Schema::CAC.'TaxScheme',
                    'value' => [
                        Schema::CBC.'ID' => [
                            'value' => $this->taxScheme->getId(), // Use public getter here.
                            'attributes' => $this->taxSchemeAttributes,
                        ],
                    ],
                ],
            ]);
        } else {
            $writer->write([
                Schema::CAC.'TaxScheme' => null,
            ]);
        }
    }
}
