<?php

namespace Saleh7\Zatca\Tests\Mappers;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saleh7\Zatca\Mappers\Validators\SupplierValidator;

class SupplierValidatorTest extends TestCase
{
    private SupplierValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new SupplierValidator;
    }

    public function test_valid_supplier_with_vat_scheme(): void
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

    public function test_supplier_without_tax_id_fails(): void
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

    public function test_supplier_without_tax_scheme_is_allowed(): void
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

    public function test_supplier_with_non_vat_tax_scheme_fails(): void
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

    public function test_supplier_without_registration_name_fails(): void
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

    public function test_supplier_with_incomplete_address_fails(): void
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

    public function test_empty_supplier_data_fails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Supplier data is required');

        $this->validator->validate([]);
    }
}
