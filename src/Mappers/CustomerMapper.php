<?php

namespace Saleh7\Zatca\Mappers;

use Saleh7\Zatca\Address;
use Saleh7\Zatca\LegalEntity;
use Saleh7\Zatca\Party;
use Saleh7\Zatca\PartyTaxScheme;
use Saleh7\Zatca\TaxScheme;

/**
 * Class CustomerMapper
 *
 * Maps customer data (array) to a Party object.
 */
class CustomerMapper
{
    /**
     * Maps customer data array to a Party object.
     *
     * Expected array structure:
     * [
     *   "taxScheme" => ["id" => "VAT"],
     *   "registrationName" => "Customer Name",
     *   "taxId" => "1234567890",
     *   "address" => [
     *       "street" => "Main Street",
     *       "buildingNumber" => "123",
     *       "subdivision" => "Subdivision",
     *       "city" => "City Name",
     *       "postalZone" => "12345",
     *       "country" => "SA"
     *   ],
     *   "identificationId" => "UniqueCustomerId", // optional
     *   "identificationType" => "IDType"          // optional
     * ]
     *
     * @param  array  $data  Customer data.
     * @return Party The mapped customer as a Party object.
     */
    public function map(array $data): Party
    {
        if (empty($data)) {
            return new Party;
        }

        // Map the LegalEntity for the customer.
        $legalEntity = (new LegalEntity)->setRegistrationName($data['registrationName'] ?? '');

        // Map the Address for the customer.
        $address = (new Address)
            ->setStreetName($data['address']['street'] ?? '')
            ->setBuildingNumber($data['address']['buildingNumber'] ?? '')
            ->setCitySubdivisionName($data['address']['subdivision'] ?? '')
            ->setCityName($data['address']['city'] ?? '')
            ->setPostalZone($data['address']['postalZone'] ?? '')
            ->setCountry($data['address']['country'] ?? 'SA');

        // Create and populate the Party object.
        $party = (new Party)
            ->setLegalEntity($legalEntity)
            ->setPostalAddress($address);

        // ZATCA requirement: Customer tax scheme handling based on VAT registration.
        // If customer has taxId (VAT-registered): MUST have VAT tax scheme.
        // If customer has no taxId (non-VAT): MUST NOT have tax scheme, may use optional schemeID.
        if (!empty($data['taxId'])) {
            // VAT-registered customer: set CompanyID and VAT tax scheme.
            // Validation ensures taxScheme is VAT if provided; defaults to VAT if not provided.
            $taxScheme = (new TaxScheme)->setId($data['taxScheme']['id'] ?? 'VAT');
            $partyTaxScheme = (new PartyTaxScheme)
                ->setTaxScheme($taxScheme)
                ->setCompanyId($data['taxId']);

            $party->setPartyTaxScheme($partyTaxScheme);
        }

        // Set party identification if available (for non-VAT customers or additional identification).
        if (isset($data['identificationId'])) {
            $party->setPartyIdentification($data['identificationId']);

            if (isset($data['identificationType'])) {
                $party->setPartyIdentificationId($data['identificationType']);
            }
        }

        return $party;
    }
}
