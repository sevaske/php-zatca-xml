<?php

namespace Saleh7\Zatca;

use InvalidArgumentException;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/**
 * Class Address
 *
 * Represents an address for XML serialization.
 */
class Address implements XmlSerializable
{
    /** @var string|null Street name. */
    private ?string $streetName = null;

    /** @var string|null Additional street name. */
    private ?string $additionalStreetName = null;

    /** @var string|null Building number. */
    private ?string $buildingNumber = null;

    /** @var string|null Plot identification. */
    private ?string $plotIdentification = null;

    /** @var string|null City name. */
    private ?string $cityName = null;

    /** @var string|null Postal zone. */
    private ?string $postalZone = null;

    /** @var string|null Country code. */
    private ?string $country = null;

    /** @var string|null Country subentity. */
    private ?string $countrySubentity = null;

    /** @var string|null City subdivision name. */
    private ?string $citySubdivisionName = null;

    /**
     * Set the street name.
     *
     * @throws InvalidArgumentException if $streetName is an empty string.
     */
    public function setStreetName(?string $streetName): self
    {
        if ($streetName !== null && trim($streetName) === '') {
            throw new InvalidArgumentException('Street name cannot be empty.');
        }
        $this->streetName = $streetName;

        return $this;
    }

    /**
     * Set the additional street name.
     */
    public function setAdditionalStreetName(?string $additionalStreetName): self
    {
        if ($additionalStreetName !== null && trim($additionalStreetName) === '') {
            throw new InvalidArgumentException('Additional street name cannot be empty.');
        }
        $this->additionalStreetName = $additionalStreetName;

        return $this;
    }

    /**
     * Set the building number.
     */
    public function setBuildingNumber(?string $buildingNumber): self
    {
        if ($buildingNumber !== null && trim($buildingNumber) === '') {
            throw new InvalidArgumentException('Building number cannot be empty.');
        }
        $this->buildingNumber = $buildingNumber;

        return $this;
    }

    /**
     * Set the plot identification.
     */
    public function setPlotIdentification(?string $plotIdentification): self
    {
        if ($plotIdentification !== null && trim($plotIdentification) === '') {
            throw new InvalidArgumentException('Plot identification cannot be empty.');
        }
        $this->plotIdentification = $plotIdentification;

        return $this;
    }

    /**
     * Set the city name.
     */
    public function setCityName(?string $cityName): self
    {
        if ($cityName !== null && trim($cityName) === '') {
            throw new InvalidArgumentException('City name cannot be empty.');
        }
        $this->cityName = $cityName;

        return $this;
    }

    /**
     * Set the postal zone.
     */
    public function setPostalZone(?string $postalZone): self
    {
        if ($postalZone !== null && trim($postalZone) === '') {
            throw new InvalidArgumentException('Postal zone cannot be empty.');
        }
        $this->postalZone = $postalZone;

        return $this;
    }

    /**
     * Set the country code.
     */
    public function setCountry(?string $country): self
    {
        if ($country !== null && trim($country) === '') {
            throw new InvalidArgumentException('Country cannot be empty.');
        }
        $this->country = $country;

        return $this;
    }

    /**
     * Set the country subentity.
     */
    public function setCountrySubentity(?string $countrySubentity): self
    {
        if ($countrySubentity !== null && trim($countrySubentity) === '') {
            throw new InvalidArgumentException('Country subentity cannot be empty.');
        }
        $this->countrySubentity = $countrySubentity;

        return $this;
    }

    /**
     * Set the city subdivision name.
     */
    public function setCitySubdivisionName(?string $citySubdivisionName): self
    {
        if ($citySubdivisionName !== null && trim($citySubdivisionName) === '') {
            throw new InvalidArgumentException('City subdivision name cannot be empty.');
        }
        $this->citySubdivisionName = $citySubdivisionName;

        return $this;
    }

    /**
     * Get the street name.
     */
    public function getStreetName(): ?string
    {
        return $this->streetName;
    }

    /**
     * Get the additional street name.
     */
    public function getAdditionalStreetName(): ?string
    {
        return $this->additionalStreetName;
    }

    /**
     * Get the building number.
     */
    public function getBuildingNumber(): ?string
    {
        return $this->buildingNumber;
    }

    /**
     * Get the plot identification.
     */
    public function getPlotIdentification(): ?string
    {
        return $this->plotIdentification;
    }

    /**
     * Get the city name.
     */
    public function getCityName(): ?string
    {
        return $this->cityName;
    }

    /**
     * Get the postal zone.
     */
    public function getPostalZone(): ?string
    {
        return $this->postalZone;
    }

    /**
     * Get the country code.
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * Get the country subentity.
     */
    public function getCountrySubentity(): ?string
    {
        return $this->countrySubentity;
    }

    /**
     * Get the city subdivision name.
     */
    public function getCitySubdivisionName(): ?string
    {
        return $this->citySubdivisionName;
    }

    /**
     * Serializes this object to XML.
     */
    public function xmlSerialize(Writer $writer): void
    {
        // Element order follows the UBL 2.1 AddressType schema.
        $elements = [
            'StreetName' => $this->streetName,
            'AdditionalStreetName' => $this->additionalStreetName,
            'BuildingNumber' => $this->buildingNumber,
            'PlotIdentification' => $this->plotIdentification,
            'CitySubdivisionName' => $this->citySubdivisionName,
            'CityName' => $this->cityName,
            'PostalZone' => $this->postalZone,
            'CountrySubentity' => $this->countrySubentity,
        ];

        foreach ($elements as $name => $value) {
            if ($value !== null) {
                $writer->write([
                    Schema::CBC.$name => $value,
                ]);
            }
        }

        if ($this->country !== null) {
            $writer->write([
                Schema::CAC.'Country' => [
                    Schema::CBC.'IdentificationCode' => $this->country,
                ],
            ]);
        }
    }
}
