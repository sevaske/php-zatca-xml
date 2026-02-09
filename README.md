# ZATCA E-Invoice XML

[![PHP Version](https://badgen.net/packagist/php/sevaske/php-zatca-xml)](https://packagist.org/packages/sevaske/php-zatca-xml)
[![Packagist Stars](https://img.shields.io/packagist/stars/sevaske/php-zatca-xml)](https://packagist.org/packages/sevaske/php-zatca-xml)
[![Packagist Downloads](https://img.shields.io/packagist/dt/sevaske/php-zatca-xml)](https://packagist.org/packages/sevaske/php-zatca-xml)
[![Packagist Version](https://img.shields.io/packagist/v/sevaske/php-zatca-xml)](https://packagist.org/packages/sevaske/php-zatca-xml)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](https://packagist.org/packages/sevaske/php-zatca-xml)

A PHP library for generating **ZATCA-compliant** e-invoices in XML format for Saudi Arabia's electronic invoicing system (Fatoora).

> ⚠️ **Note:** This is an unofficial library, not maintained by ZATCA.

---

## 🎯 What This Library Does

- ✅ Generate ZATCA-compliant XML invoices (Standard & Simplified)
- ✅ Create credit notes and debit notes
- ✅ Generate and manage certificates (CSR, private keys)
- ✅ Sign invoices with cryptographic signatures
- ✅ Generate QR codes for invoices
- ✅ Works with API

---

## 📦 Installation

```bash
composer require sevaske/php-zatca-xml:^4.0
```

### Requirements
- PHP 8.1 or higher
- OpenSSL extension

---

## Integration Workflow

```
1. Generate CSR
   ↓
2. Request compliance certificate
   ↓
3. Generate invoice XML
   ↓
4. Sign invoice
   ↓
5. Submit to ZATCA (simulation)
   ↓
6. Generate production certificate
```

---

## 🚀 Quick Start

### Generate CSR and Private Key

```php
use Saleh7\Zatca\CertificateBuilder;
use Saleh7\Zatca\GeneratorInvoice;
use Saleh7\Zatca\InvoiceSigner;
use Saleh7\Zatca\Mappers\InvoiceMapper;
use Saleh7\Zatca\Helpers\Certificate;

$certificate = (new CertificateBuilder())->setOrganizationIdentifier('312345678901233')
    ->setSerialNumber('MySolution', 'Model1', 'DEVICE001')
    ->setCommonName('My Company')
    ->setCountryName('SA')
    ->setOrganizationName('My Company Ltd')
    ->setOrganizationalUnitName('IT Department')
    ->setAddress('Riyadh 1234 Street')
    ->setInvoiceType(1100)
    ->setProduction(false)
    ->setBusinessCategory('Technology')
    ->generate();

$csr = $certificate->getCsr(); // .csr
$pem = $certificate->getCsr(); // .pem

file_put_contents('output/certificate.csr', $csr);
file_put_contents('output/private.pem', $pem);
```

### Compliance certificate & set authentication

```php
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Sevaske\ZatcaApi\ZatcaAuth;
use Sevaske\ZatcaApi\ZatcaClient;

$httpClient = new Client();
$factory = new HttpFactory();

// Initialize ZatcaClient with sandbox environment
$client = new ZatcaClient(
    $httpClient,
    $factory, // RequestFactoryInterface
    $factory, // StreamFactoryInterface
    'sandbox' // environment: sandbox | simulation | production
);
$response = $client->complianceCertificate($csr, '112233');
$credentials = [
    'requestId' => $response->requestId(),
    'certificate' => $response->certificate(),
    'secret' => $response->secret(),
];

if (! $response->success()) {
    // failed
}

$outputFile = __DIR__.'/output/simulation.json';
file_put_contents($outputFile, json_encode($credentials, JSON_PRETTY_PRINT));

// to make authorized requests
$authToken = new ZatcaAuth($response->certificate(), $response->secret());
$client->setAuthToken($authToken);
```

### Basic XML Generation

```php
use Saleh7\Zatca\GeneratorInvoice;
use Saleh7\Zatca\Helpers\Certificate;
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

// Map the data to an Invoice object
$invoiceMapper = new InvoiceMapper;
$invoice = $invoiceMapper->mapToInvoice($invoiceData);

// Generate the invoice XML
$generatorInvoice = GeneratorInvoice::invoice($invoice);

$simulation = json_decode(file_get_contents('output/simulation.json'), true);
$privateKey = file_get_contents('output/private.pem');

// sign the invoice XML with the certificate
$certificate = (new Certificate(
    $simulation['certificate'],
    $privateKey,
    $simulation['secret'],
));
$signedInvoice = InvoiceSigner::signInvoice($generatorInvoice->getXML(), $certificate);

$signedInvoice->getXML();
$signedInvoice->getHash();
$signedInvoice->getQRCode();
```

### Submit invoice

```php
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Saleh7\Zatca\InvoiceSigner;
use Sevaske\ZatcaApi\ZatcaAuth;
use Sevaske\ZatcaApi\ZatcaClient;
use Sevaske\ZatcaApi\Exceptions\ZatcaException;

/**
* @var Sevaske\ZatcaApi\ZatcaClient $client
*/

try {
    // load simulation credential
    $simulationPath = __DIR__.'/output/simulation.json';
    $simulation = json_decode(file_get_contents($simulationPath), true);

    $client->setAuthToken(new ZatcaAuth(
        certificate: $simulation['certificate'],
        secret: $simulation['secret'])
    );

    /**
     * @var InvoiceSigner $signedInvoice
     */
    $response = $client->reportingInvoice(
        invoice: $signedInvoice->getInvoice(),
        invoiceHash: $signedInvoice->getHash(),
        uuid: 'generated uuid',
    );

    if (! $response->success()) {
        throw new ZatcaException('Failed request.', $response->errors());
    }
    
    $response->errors();
    $response->warnings();
    $response->toArray();
} catch (ZatcaException|Exception $e) {
    // handle
}
```

#### Submission Types

**Simplified Invoice (B2C) - Reporting:**
```php
$response = $client->reportingInvoice($xml, $hash, $uuid);
```

**Standard Invoice (B2B) - Clearance:**
```php
$response = $client->clearanceInvoice($xml, $hash, $uuid);
```

#### Simulation Testing

Before production, submit 6 test invoices:

```php
/**
* @var Sevaske\ZatcaApi\ZatcaClient $client
*/

// Switch to simulation environment
$client = $client->withEnvironment('simulation');

// Submit 3 simplified invoices
$client->reportingInvoice($simplifiedInvoiceXML, $hash, $uuid);
$client->reportingInvoice($simplifiedDebitNoteXML, $hash, $uuid);
$client->reportingInvoice($simplifiedCreditNoteXML, $hash, $uuid);

// Submit 3 standard invoices
$client->clearanceInvoice($standardInvoiceXML, $hash, $uuid);
$client->clearanceInvoice($standardDebitNoteXML, $hash, $uuid);
$client->clearanceInvoice($standardCreditNoteXML, $hash, $uuid);
```

### Production Certificate

After simulation you are able to switch to the production environment:

```php
/**
* @var Sevaske\ZatcaApi\ZatcaClient $client
*/
// load simulation credential
$simulationPath = __DIR__.'/output/simulation.json';
$simulation = json_decode(file_get_contents($simulationPath), true);

$response = $client->productionCertificate($simulation['requestId']);
$credentials = [
    'requestId' => $response->requestId(),
    'certificate' => $response->certificate(),
    'secret' => $response->secret(),
];

if (! $response->success()) {
    // failed
}

$outputFile = __DIR__.'/output/production.json';
file_put_contents($outputFile, json_encode($credentials, JSON_PRETTY_PRINT));

// to make authorized PRODUCTION requests
$authToken = new ZatcaAuth($response->certificate(), $response->secret());
$client->setAuthToken($authToken);

// you are able to submit production invoices now
```

---

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

---

## 📄 License

This project is licensed under the [MIT License](LICENSE).

---

## 🔗 Related Projects

- **[sevaske/zatca-api](https://github.com/sevaske/zatca-api)** - ZATCA API Client

---

## 🙏 Acknowledgments

This is a community-maintained fork of [Saleh7/php-zatca-xml](https://github.com/Saleh7/php-zatca-xml).

All improvements and bug fixes are collected here to support the community while the original author is inactive.

---

## ⚠️ Disclaimer

This library is not officially maintained by ZATCA. Use at your own risk and always test thoroughly in sandbox/simulation environments before production use.

---

## 📞 Support

- 🐛 [Report Issues](https://github.com/sevaske/php-zatca-xml/issues)
- 📖 [Examples](https://github.com/sevaske/php-zatca-xml/tree/main/examples)
- 🔌 [API Integration Guide](https://github.com/sevaske/zatca-api#readme)

---

## License

This project is licensed under the [MIT License](LICENSE).

---

**Happy invoicing! 🧾✨**