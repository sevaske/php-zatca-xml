<?php

namespace Saleh7\Zatca\Mappers\Validators;

use InvalidArgumentException;

/**
 * Class CustomerValidator
 *
 * Validates customer (buyer) data according to ZATCA e-invoicing requirements.
 *
 * ZATCA Rules for Buyers:
 * 1. Standard (B2B) invoices:
 *    - VAT-registered buyers: MUST provide taxId (CompanyID) + VAT tax scheme
 *    - Non-VAT buyers: identification is optional; if provided, use schemeID (no tax scheme)
 *
 * 2. Simplified (B2C) invoices:
 *    - VAT-registered buyers: MUST provide taxId (CompanyID) + VAT tax scheme
 *    - Non-VAT buyers: identification is optional; if provided, use schemeID (no tax scheme)
 *
 * Summary:
 * - If buyer has taxId: MUST have VAT tax scheme
 * - If buyer has no taxId: MUST NOT have tax scheme; may have optional schemeID
 */
class CustomerValidator extends PartyValidator
{
    /**
     * Validate customer data according to ZATCA requirements.
     *
     * @param  array  $data  The customer data array.
     * @param  bool  $isSimplified  Whether this is a simplified (B2C) invoice.
     *
     * @throws InvalidArgumentException if validation fails.
     */
    public function validate(array $data, bool $isSimplified = false): void
    {
        // For simplified invoices, customer data is optional.
        // For standard invoices, customer data is required.
        if (empty($data)) {
            if (!$isSimplified) {
                throw new InvalidArgumentException('Customer data is required for standard (B2B) invoices.');
            }
            // If simplified and no customer data, validation passes.
            return;
        }

        // Check if customer is VAT-registered (has taxId).
        $hasVatRegistration = !empty($data['taxId']);

        if ($hasVatRegistration) {
            // VAT-registered buyer: MUST have VAT tax scheme.
            $this->validateVatRegisteredCustomer($data);
        } else {
            // Non-VAT buyer: MUST NOT have tax scheme.
            $this->validateNonVatCustomer($data, $isSimplified);
        }

        // Validate address if provided (required for standard invoices).
        if (!$isSimplified || (isset($data['address']) && !empty($data['address']))) {
            $this->validateAddress($data, 'Customer');
        }
    }

    /**
     * Validate VAT-registered customer.
     *
     * VAT-registered buyers MUST have:
     * - taxId (CompanyID)
     * - taxScheme with id='VAT'
     * - registrationName
     *
     * @param  array  $data  Customer data.
     *
     * @throws InvalidArgumentException if validation fails.
     */
    private function validateVatRegisteredCustomer(array $data): void
    {
        // Validate required fields for VAT-registered customer.
        if (empty($data['registrationName'])) {
            throw new InvalidArgumentException('Customer Registration Name is required for VAT-registered buyers.');
        }

        if (empty($data['taxId'])) {
            throw new InvalidArgumentException('Customer VAT Number (taxId) is required for VAT-registered buyers.');
        }

        // ZATCA requirement: VAT-registered buyers MUST have VAT tax scheme.
        // If taxScheme is provided, it must be 'VAT'. If not provided, mapper will default to 'VAT'.
        if (isset($data['taxScheme']['id']) && !empty($data['taxScheme']['id'])) {
            if (strtoupper($data['taxScheme']['id']) !== 'VAT') {
                throw new InvalidArgumentException(
                    "VAT-registered customer tax scheme must be 'VAT'. Found: '{$data['taxScheme']['id']}'."
                );
            }
        }
    }

    /**
     * Validate non-VAT customer.
     *
     * Non-VAT buyers:
     * - MUST NOT have taxScheme
     * - MAY have optional identification (identificationId with schemeID)
     * - registrationName is optional
     *
     * @param  array  $data  Customer data.
     * @param  bool  $isSimplified  Whether this is a simplified invoice.
     *
     * @throws InvalidArgumentException if validation fails.
     */
    private function validateNonVatCustomer(array $data, bool $isSimplified): void
    {
        // ZATCA requirement: Non-VAT buyers MUST NOT have tax scheme.
        if (isset($data['taxScheme']) && !empty($data['taxScheme']['id'])) {
            throw new InvalidArgumentException(
                "Non-VAT registered customer must not have a tax scheme. Remove 'taxScheme' or provide a valid 'taxId'."
            );
        }

        // For standard invoices, registrationName is required even for non-VAT customers.
        if (!$isSimplified && empty($data['registrationName'])) {
            throw new InvalidArgumentException(
                'Customer Registration Name is required for standard (B2B) invoices.'
            );
        }

        // Validate identification fields if provided.
        $this->validateIdentification($data, 'Customer');
    }
}
