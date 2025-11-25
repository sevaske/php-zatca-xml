<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Saleh7\Zatca\Mappers\Validators\CustomerValidator;
use InvalidArgumentException;

class CustomerValidatorTest extends TestCase
{
    private CustomerValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new CustomerValidator();
    }

    // Standard (B2B) Invoice Tests

    public function testVatRegisteredCustomerForStandardInvoice(): void
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

    public function testVatRegisteredCustomerWithoutTaxSchemeIsAllowed(): void
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

    public function testNonVatCustomerWithSchemeIdForStandardInvoice(): void
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

    public function testNonVatCustomerWithTaxSchemeFails(): void
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

    public function testStandardInvoiceWithoutCustomerDataFails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Customer data is required for standard');

        $this->validator->validate([], false);
    }

    public function testNonVatCustomerWithoutRegistrationNameForStandardFails(): void
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

    public function testSimplifiedInvoiceWithNoCustomerData(): void
    {
        $this->validator->validate([], true);
        $this->assertTrue(true);
    }

    public function testVatRegisteredCustomerForSimplifiedInvoice(): void
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

    public function testNonVatCustomerForSimplifiedInvoice(): void
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

    public function testNonVatCustomerWithIdentificationButNoTypeFails(): void
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

    public function testCustomerWithIncompleteAddressFails(): void
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
