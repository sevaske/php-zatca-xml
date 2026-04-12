<?php

namespace Saleh7\Zatca\Tests\Mappers;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saleh7\Zatca\Mappers\Validators\CustomerValidator;

class CustomerValidatorTest extends TestCase
{
    private CustomerValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new CustomerValidator;
    }

    // Standard (B2B) Invoice Tests

    public function test_vat_registered_customer_for_standard_invoice(): void
    {
        $data = [
            'registrationName' => 'Customer Ltd',
            'taxId' => '333333333333333',
            'taxScheme' => ['id' => 'VAT'],
            'address' => [
                'street' => 'Customer St',
                'buildingNumber' => '456',
                'city' => 'Riyadh',
                'postalZone' => '12345',
                'country' => 'SA',
            ],
        ];

        $this->validator->validate($data, false);
        $this->assertTrue(true); // If no exception, validation passed
    }

    public function test_vat_registered_customer_without_tax_scheme_is_allowed(): void
    {
        // Missing taxScheme is allowed - mapper will default to VAT
        $data = [
            'registrationName' => 'Customer Ltd',
            'taxId' => '333333333333333',
            'address' => [
                'street' => 'Customer St',
                'buildingNumber' => '456',
                'city' => 'Riyadh',
                'postalZone' => '12345',
                'country' => 'SA',
            ],
        ];

        $this->validator->validate($data, false);
        $this->assertTrue(true);
    }

    public function test_non_vat_customer_with_scheme_id_for_standard_invoice(): void
    {
        $data = [
            'registrationName' => 'Non-VAT Customer',
            'identificationId' => '1010010000',
            'identificationType' => 'CRN',
            'address' => [
                'street' => 'Customer St',
                'buildingNumber' => '456',
                'city' => 'Riyadh',
                'postalZone' => '12345',
                'country' => 'SA',
            ],
        ];

        $this->validator->validate($data, false);
        $this->assertTrue(true);
    }

    public function test_non_vat_customer_with_tax_scheme_fails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must not have a tax scheme');

        $data = [
            'registrationName' => 'Non-VAT Customer',
            'taxScheme' => ['id' => 'VAT'],
            'address' => [
                'street' => 'Customer St',
                'buildingNumber' => '456',
                'city' => 'Riyadh',
                'postalZone' => '12345',
                'country' => 'SA',
            ],
        ];

        $this->validator->validate($data, false);
    }

    public function test_standard_invoice_without_customer_data_fails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Customer data is required for standard');

        $this->validator->validate([], false);
    }

    public function test_non_vat_customer_without_registration_name_for_standard_fails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Registration Name is required');

        $data = [
            'identificationId' => '1010010000',
            'identificationType' => 'CRN',
            'address' => [
                'street' => 'Customer St',
                'buildingNumber' => '456',
                'city' => 'Riyadh',
                'postalZone' => '12345',
                'country' => 'SA',
            ],
        ];

        $this->validator->validate($data, false);
    }

    // Simplified (B2C) Invoice Tests

    public function test_simplified_invoice_with_no_customer_data(): void
    {
        $this->validator->validate([], true);
        $this->assertTrue(true);
    }

    public function test_vat_registered_customer_for_simplified_invoice(): void
    {
        $data = [
            'registrationName' => 'VAT Customer',
            'taxId' => '333333333333333',
            'taxScheme' => ['id' => 'VAT'],
            'address' => [
                'street' => 'Customer St',
                'buildingNumber' => '456',
                'city' => 'Riyadh',
                'postalZone' => '12345',
                'country' => 'SA',
            ],
        ];

        $this->validator->validate($data, true);
        $this->assertTrue(true);
    }

    public function test_non_vat_customer_for_simplified_invoice(): void
    {
        $data = [
            'identificationId' => '1010010000',
            'identificationType' => 'NAT',
            'address' => [
                'street' => 'Customer St',
                'buildingNumber' => '456',
                'city' => 'Riyadh',
                'postalZone' => '12345',
                'country' => 'SA',
            ],
        ];

        $this->validator->validate($data, true);
        $this->assertTrue(true);
    }

    public function test_non_vat_customer_with_identification_but_no_type_fails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Customer identificationType is required');

        $data = [
            'registrationName' => 'Customer',
            'identificationId' => '1010010000',
            'address' => [
                'street' => 'Customer St',
                'buildingNumber' => '456',
                'city' => 'Riyadh',
                'postalZone' => '12345',
                'country' => 'SA',
            ],
        ];

        $this->validator->validate($data, false);
    }

    public function test_customer_with_incomplete_address_fails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Address');

        $data = [
            'registrationName' => 'Customer Ltd',
            'taxId' => '333333333333333',
            'taxScheme' => ['id' => 'VAT'],
            'address' => [
                'street' => 'Customer St',
                // missing buildingNumber
                'city' => 'Riyadh',
                'postalZone' => '12345',
                'country' => 'SA',
            ],
        ];

        $this->validator->validate($data, false);
    }
}
