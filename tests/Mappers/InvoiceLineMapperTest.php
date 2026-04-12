<?php

namespace Saleh7\Zatca\Tests\Mappers;

use PHPUnit\Framework\TestCase;
use Saleh7\Zatca\AllowanceCharge;
use Saleh7\Zatca\Mappers\InvoiceLineMapper;

class InvoiceLineMapperTest extends TestCase
{
    private InvoiceLineMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new InvoiceLineMapper;
    }

    public function test_maps_invoice_line_without_allowance_charges(): void
    {
        $lines = [
            [
                'id' => '1',
                'quantity' => 2,
                'lineExtensionAmount' => 100.00,
                'taxAmount' => 15.00,
                'roundingAmount' => 115.00,
                'item' => [
                    'name' => 'Test Item',
                    'classifiedTaxCategory' => [
                        'percent' => 15,
                        'id' => 'S',
                        'taxScheme' => ['id' => 'VAT'],
                    ],
                ],
                'price' => [
                    'amount' => 50.00,
                    'baseQuantity' => 1,
                ],
            ],
        ];

        $result = $this->mapper->mapInvoiceLines($lines);

        $this->assertCount(1, $result);
        $this->assertEmpty($result[0]->getAllowanceCharges());
    }

    public function test_maps_invoice_line_with_allowance_charge(): void
    {
        $lines = [
            [
                'id' => '1',
                'quantity' => 1,
                'lineExtensionAmount' => 85.00,
                'taxAmount' => 12.75,
                'roundingAmount' => 97.75,
                'item' => [
                    'name' => 'Test Item',
                    'classifiedTaxCategory' => [
                        'percent' => 15,
                        'id' => 'S',
                        'taxScheme' => ['id' => 'VAT'],
                    ],
                ],
                'price' => [
                    'amount' => 100.00,
                    'baseQuantity' => 1,
                ],
                'allowanceCharges' => [
                    [
                        'isCharge' => false,
                        'reason' => 'discount',
                        'amount' => 15.00,
                        'taxCategories' => [
                            [
                                'percent' => 15,
                                'id' => 'S',
                                'taxScheme' => ['id' => 'VAT'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->mapper->mapInvoiceLines($lines);

        $this->assertCount(1, $result);

        $allowanceCharges = $result[0]->getAllowanceCharges();
        $this->assertCount(1, $allowanceCharges);
        $this->assertInstanceOf(AllowanceCharge::class, $allowanceCharges[0]);
        $this->assertFalse($allowanceCharges[0]->isChargeIndicator());
        $this->assertEquals(15.00, $allowanceCharges[0]->getAmount());
    }

    public function test_maps_invoice_line_with_allowance_charge_without_tax_categories(): void
    {
        $lines = [
            [
                'id' => '1',
                'quantity' => 1,
                'lineExtensionAmount' => 85.00,
                'taxAmount' => 12.75,
                'roundingAmount' => 97.75,
                'item' => [
                    'name' => 'Test Item',
                    'classifiedTaxCategory' => [
                        'percent' => 15,
                        'id' => 'S',
                        'taxScheme' => ['id' => 'VAT'],
                    ],
                ],
                'price' => [
                    'amount' => 100.00,
                    'baseQuantity' => 1,
                ],
                'allowanceCharges' => [
                    [
                        'isCharge' => false,
                        'reason' => 'discount',
                        'amount' => 15.00,
                    ],
                ],
            ],
        ];

        $result = $this->mapper->mapInvoiceLines($lines);

        $allowanceCharges = $result[0]->getAllowanceCharges();
        $this->assertCount(1, $allowanceCharges);
    }
}
