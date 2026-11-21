<?php

require_once __DIR__.'/../../vendor/autoload.php';

use Saleh7\Zatca\GeneratorInvoice;
use Saleh7\Zatca\InvoiceSigner;
use Saleh7\Zatca\Mappers\InvoiceMapper;

$invoiceData = [
    'uuid' => 'b51bd500-9081-4acf-9ae4-c266d569cb77',
    'id' => '111222333',
    'issueDate' => date('Y-m-d H:i:s'),
    'issueTime' => date('Y-m-d H:i:s'),
    'delivery' => [
        'actualDeliveryDate' => date('Y-m-d H:i:s'),
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
                'content' => 'NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ==', // previous invoice hash; for the first invoice: base64 of the SHA-256 hex of "0"
            ],
        ],
    ],
    'supplier' => [
        'registrationName' => 'My company name',
        'taxId' => '399999999900003', // VAT number of the signing certificate (see examples/Certificates/signing-certificate.php)
        'identificationId' => '1010010000', // commercial registration number (CRN) of the seller
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

// Map the data to an Invoice object
$invoiceMapper = new InvoiceMapper;
$invoice = $invoiceMapper->mapToInvoice($invoiceData, true, true);

// Generate the invoice XML
$generatorInvoice = GeneratorInvoice::invoice($invoice);

// sign the invoice XML with the certificate
$certificate = require __DIR__.'/../Certificates/signing-certificate.php';
$signedInvoice = InvoiceSigner::signInvoice($generatorInvoice->getXML(), $certificate);

is_dir(__DIR__.'/output') || mkdir(__DIR__.'/output');
file_put_contents(__DIR__.'/output/Simplified_Invoice.xml', $generatorInvoice->getXML());
echo "Simplified Invoice Generated Successfully\n";

// store the signed XML exactly as returned: any change breaks the hash and the signature
file_put_contents(__DIR__.'/output/Simplified_Invoice_Signed.xml', $signedInvoice->getXML());
echo "Simplified Invoice Signed Successfully\n";
