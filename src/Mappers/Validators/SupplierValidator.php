<?php

namespace Saleh7\Zatca\Mappers\Validators;

use InvalidArgumentException;

/**
 * Class SupplierValidator
 *
 * Validates supplier (seller) data according to ZATCA e-invoicing requirements.
 *
 * ZATCA Rule: The seller is always treated as a taxable person and must provide:
 * - A valid VAT number (taxId/CompanyID)
 * - A VAT tax scheme (taxScheme with id='VAT')
 * on every invoice (both standard B2B and simplified B2C).
 */
class SupplierValidator extends PartyValidator
{
    /**
     * Validate supplier data according to ZATCA requirements.
     *
     * The supplier must always have:
     * - registrationName (required)
     * - taxId (VAT number/CompanyID) (required)
     * - taxScheme with id='VAT' (required)
     * - complete address information (required)
     *
     * @param  array  $data  The supplier data array.
     *
     * @throws InvalidArgumentException if any required field is missing or invalid.
     */
    public function validate(array $data): void
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Supplier data is required.');
        }

        // Validate required supplier fields.
        $requiredFields = [
            'registrationName' => 'Supplier Registration Name',
            'taxId' => 'Supplier VAT Number (taxId)',
        ];

        foreach ($requiredFields as $field => $friendlyName) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("The field '{$friendlyName}' is required and cannot be empty.");
            }
        }

        // Validate that the supplier has a VAT tax scheme.
        // ZATCA requirement: Seller is always a taxable person with VAT registration.
        // If taxScheme is provided, it must be 'VAT'. If not provided, mapper will default to 'VAT'.
        if (isset($data['taxScheme']['id']) && !empty($data['taxScheme']['id'])) {
            if (strtoupper($data['taxScheme']['id']) !== 'VAT') {
                throw new InvalidArgumentException("Supplier tax scheme must be 'VAT'. Found: '{$data['taxScheme']['id']}'.");
            }
        }

        // Validate supplier address.
        $this->validateAddress($data, 'Supplier');

        // Validate identification fields if provided.
        $this->validateIdentification($data, 'Supplier');
    }
}
