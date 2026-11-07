<?php

namespace Saleh7\Zatca\Tests\Fixtures;

/**
 * Minimal valid simplified invoice data for mapper-based tests.
 */
class InvoiceDataFixture
{
    public static function simplified(): array
    {
        return [
            'uuid' => 'b51bd500-9081-4acf-9ae4-c266d569cb77',
            'id' => '111222333',
            'issueDate' => '2026-01-15 10:00:00',
            'issueTime' => '2026-01-15 10:00:00',
            'delivery' => [
                'actualDeliveryDate' => '2026-01-15 10:00:00',
            ],
            'currencyCode' => 'SAR',
            'taxCurrencyCode' => 'SAR',
            'note' => 'Tax ID is 333333333333333 because a customer didnt provide it.',
            'languageID' => 'en',
            'invoiceType' => [
                'invoice' => 'simplified',
                'type' => 'invoice',
                'isThirdParty' => false,
                'isNominal' => false,
                'isExport' => false,
                'isSummary' => false,
                'isSelfBilled' => false,
            ],
            'additionalDocuments' => [
                [
                    'id' => 'ICV',
                    'uuid' => '1', // counter value
                ],
                [
                    'id' => 'PIH',
                    'attachment' => [
                        'content' => 'MA==', // previous hash
                    ],
                ],
            ],
            'supplier' => [
                'registrationName' => 'My company name',
                'taxId' => '311111111111113',
                'identificationId' => '1111111111', // my company CRN
                'identificationType' => 'CRN',
                'address' => [
                    'street' => 'company street name',
                    'buildingNumber' => '8008',
                    'subdivision' => 'sub',
                    'city' => 'Riyadh',
                    'postalZone' => '12345',
                    'country' => 'SA',
                ],
            ],
            'customer' => [
                'registrationName' => 'Naruto Uzumaki',
                'taxId' => '333333333333333',
                'address' => [
                    'street' => 'Al Urubah Road',
                    'buildingNumber' => '7176',
                    'subdivision' => 'Al Olaya',
                    'city' => 'Riyadh',
                    'postalZone' => '12251',
                    'country' => 'SA',
                ],
            ],
            'paymentMeans' => [
                'code' => '10', // cash
            ],
            'allowanceCharges' => [
                [
                    'isCharge' => false,
                    'reason' => 'discount',
                    'amount' => 0.0,
                    'taxCategories' => [
                        0 => [
                            'percent' => 15,
                            'taxScheme' => [
                                'id' => 'VAT',
                            ],
                        ],
                    ],
                ],
            ],
            'taxTotal' => [
                'taxAmount' => 6.86,
                'subTotals' => [
                    0 => [
                        'taxableAmount' => 45.75,
                        'taxAmount' => 6.86,
                        'taxCategory' => [
                            'percent' => 15,
                            'taxScheme' => [
                                'id' => 'VAT',
                            ],
                        ],
                    ],
                ],
            ],
            'legalMonetaryTotal' => [
                'lineExtensionAmount' => 45.75,
                'taxExclusiveAmount' => 45.75,
                'taxInclusiveAmount' => 52.61,
                'prepaidAmount' => 0,
                'payableAmount' => 52.61,
                'allowanceTotalAmount' => 0.0,
            ],
            'invoiceLines' => [
                [
                    'id' => 1,
                    'unitCode' => 'PCE',
                    'quantity' => 1,
                    'lineExtensionAmount' => 20.75,
                    'item' => [
                        'name' => 'My product',
                        'classifiedTaxCategory' => [
                            0 => [
                                'percent' => 15.0,
                                'taxScheme' => [
                                    'id' => 'VAT',
                                ],
                            ],
                        ],
                    ],
                    'price' => [
                        'amount' => 20.75,
                        'unitCode' => 'UNIT',
                        'allowanceCharges' => [
                            0 => [
                                'isCharge' => false,
                                'reason' => 'discount',
                                'amount' => 0.0,
                            ],
                        ],
                    ],
                    'taxTotal' => [
                        'taxAmount' => 3.11,
                        'roundingAmount' => 23.86,
                    ],
                ],
                [
                    'id' => 2,
                    'unitCode' => 'C62',
                    'quantity' => 1,
                    'lineExtensionAmount' => 25.0,
                    'item' => [
                        'name' => 'My another product',
                        'classifiedTaxCategory' => [
                            0 => [
                                'percent' => 15.0,
                                'taxScheme' => [
                                    'id' => 'VAT',
                                ],
                            ],
                        ],
                    ],
                    'price' => [
                        'amount' => '25.00',
                        'unitCode' => 'UNIT',
                        'allowanceCharges' => [
                            0 => [
                                'isCharge' => false,
                                'reason' => 'discount',
                                'amount' => 0.0,
                            ],
                        ],
                    ],
                    'taxTotal' => [
                        'taxAmount' => 3.75,
                        'roundingAmount' => 28.75,
                    ],
                ],
            ],
        ];
    }
}
