<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Saleh7\Zatca\Mappers\Validators\SupplierValidator;
use InvalidArgumentException;

class SupplierValidatorTest extends TestCase
{
    private SupplierValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new SupplierValidator();
    }

    public function testValidSupplierWithVatScheme(): void
    {
        $data = [
            'registrationName' => 'My Company',
            'taxId' => '311111111111113',
            'taxScheme' => ['id' => 'VAT'],
            'address' => [
                'street' => 'Main St',
                'buildingNumber' => '123',
                'city' => 'Riyadh',
                'postalZone' => '12345',
                'country' => 'SA',
            ],
        ];

        $this->validator->validate($data);
        $this->assertTrue(true); // If no exception, validation passed
    }

    public function testSupplierWithoutTaxIdFails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Supplier VAT Number (taxId)');

        $data = [
            'registrationName' => 'My Company',
            'taxScheme' => ['id' => 'VAT'],
            'address' => [
                'street' => 'Main St',
                'buildingNumber' => '123',
                'city' => 'Riyadh',
                'postalZone' => '12345',
                'country' => 'SA',
            ],
        ];

        $this->validator->validate($data);
    }

    public function testSupplierWithoutTaxSchemeIsAllowed(): void
    {
        // Missing taxScheme is allowed - mapper will default to VAT
        $data = [
            'registrationName' => 'My Company',
            'taxId' => '311111111111113',
            'address' => [
                'street' => 'Main St',
                'buildingNumber' => '123',
                'city' => 'Riyadh',
                'postalZone' => '12345',
                'country' => 'SA',
            ],
        ];

        $this->validator->validate($data);
        $this->assertTrue(true);
    }

    public function testSupplierWithNonVatTaxSchemeFails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("tax scheme must be 'VAT'");

        $data = [
            'registrationName' => 'My Company',
            'taxId' => '311111111111113',
            'taxScheme' => ['id' => 'OTHER'],
            'address' => [
                'street' => 'Main St',
                'buildingNumber' => '123',
                'city' => 'Riyadh',
                'postalZone' => '12345',
                'country' => 'SA',
            ],
        ];

        $this->validator->validate($data);
    }

    public function testSupplierWithoutRegistrationNameFails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Registration Name');

        $data = [
            'taxId' => '311111111111113',
            'taxScheme' => ['id' => 'VAT'],
            'address' => [
                'street' => 'Main St',
                'buildingNumber' => '123',
                'city' => 'Riyadh',
                'postalZone' => '12345',
                'country' => 'SA',
            ],
        ];

        $this->validator->validate($data);
    }

    public function testSupplierWithIncompleteAddressFails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Address');

        $data = [
            'registrationName' => 'My Company',
            'taxId' => '311111111111113',
            'taxScheme' => ['id' => 'VAT'],
            'address' => [
                'street' => 'Main St',
                // missing buildingNumber
                'city' => 'Riyadh',
                'postalZone' => '12345',
                'country' => 'SA',
            ],
        ];

        $this->validator->validate($data);
    }

    public function testEmptySupplierDataFails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Supplier data is required');

        $this->validator->validate([]);
    }
}
