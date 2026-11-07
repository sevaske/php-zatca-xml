# ZATCA E-Invoice XML

[![PHP Version](https://badgen.net/packagist/php/sevaske/php-zatca-xml)](https://packagist.org/packages/sevaske/php-zatca-xml)
[![Packagist Stars](https://img.shields.io/packagist/stars/sevaske/php-zatca-xml)](https://packagist.org/packages/sevaske/php-zatca-xml)
[![Packagist Downloads](https://img.shields.io/packagist/dt/sevaske/php-zatca-xml)](https://packagist.org/packages/sevaske/php-zatca-xml)
[![Packagist Version](https://img.shields.io/packagist/v/sevaske/php-zatca-xml)](https://packagist.org/packages/sevaske/php-zatca-xml)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](https://packagist.org/packages/sevaske/php-zatca-xml)

A PHP library for generating **ZATCA-compliant** e-invoices in XML format for Saudi Arabia's electronic invoicing system (Fatoora). Provides full integration coverage for ZATCA Phase 2.

> ⚠️ **Note:** This is an unofficial library, not maintained by ZATCA.

---

## 🎯 What This Library Does

- ✅ Generate ZATCA-compliant XML invoices (Standard & Simplified)
- ✅ Create credit notes and debit notes
- ✅ Generate and manage certificates (CSR, private keys)
- ✅ Sign invoices with cryptographic signatures
- ✅ Generate QR codes for invoices
- ✅ Works with [`sevaske/zatca-api`](https://github.com/sevaske/zatca-api) 3.x for onboarding and submission (optional)

---

## 📦 Installation

```bash
composer require sevaske/php-zatca-xml:^4.0
```

To talk to the ZATCA API (onboarding, compliance checks, reporting, clearance) install the API client as well:

```bash
composer require sevaske/zatca-api:^3.0
```

### Requirements
- PHP 8.1 or higher
- OpenSSL extension

---

## Integration Workflow

```
1. Generate CSR and private key                     (this library)
   ↓
2. Request compliance CSID                          (zatca-api)
   ↓
3. Generate and sign sample invoices                (this library)
   ↓
4. Compliance checks of the samples                 (zatca-api)
   ↓
5. Request production CSID                          (zatca-api)
   ↓
6. Generate, sign and report / clear real invoices  (this library + zatca-api)
```

---

## 🚀 Quick Start

### Generate CSR and Private Key

```php
use Saleh7\Zatca\CertificateBuilder;
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
$pem = $certificate->getPrivateKey(); // .pem

file_put_contents('output/certificate.csr', $csr);
file_put_contents('output/private.pem', $pem);
```

### Compliance CSID

```php
use Sevaske\ZatcaApi\Client;

// Client::sandbox() accepts the test OTP "123345"; in simulation / production use the OTP from the Fatoora portal.
$zatca = Client::sandbox();

$complianceCsid = $zatca->onboarding()
    ->requestComplianceCsid($csr, '123345')
    ->toComplianceCsid();

file_put_contents('output/compliance-csid.json', json_encode($complianceCsid->toArray(), JSON_PRETTY_PRINT));
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
    'note' => "Tax ID is 333333333333333 because a customer didn't provide it.",
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
        // 'taxAmountInTaxCurrency' => 25.73, // required (VAT total in SAR) when currencyCode is not SAR
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
        // 'chargeTotalAmount' => 0.0, // required when document-level charges (isCharge: true) are present
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

// the compliance CSID signs the compliance samples, the production CSID signs real invoices
$csid = Sevaske\ZatcaApi\Credentials\ComplianceCsid::fromArray(json_decode(file_get_contents('output/compliance-csid.json'), true));
$privateKey = file_get_contents('output/private.pem');

// sign the invoice XML with the certificate
$certificate = new Certificate(
    $csid->getCertificate(),
    $privateKey,
    $csid->getSecret(),
);
$signedInvoice = InvoiceSigner::signInvoice($generatorInvoice->getXML(), $certificate);

$signedInvoice->getXML();
$signedInvoice->getHash();
$signedInvoice->getQRCode();
```

### Compliance checks

Before requesting the production CSID, check one signed sample of every document type the EGS unit will issue
(standard / simplified invoice, credit note, debit note), signed with the compliance CSID:

```php
use Sevaske\ZatcaApi\Invoice\SignedInvoice;

$check = $zatca->compliance($complianceCsid)->checkInvoice(
    new SignedInvoice($signedInvoice->getXML(), $signedInvoice->getHash(), $invoiceData['uuid'])
);

if (! $check->isPassed()) {
    foreach ($check->getValidationResults()->getErrorMessages() as $error) {
        echo $error, PHP_EOL; // "[BR-KSA-37] The seller address building number must contain 4 digits."
    }
}
```

### Production CSID

```php
$productionCsid = $zatca->onboarding()
    ->requestProductionCsid($complianceCsid)
    ->toProductionCsid();

file_put_contents('output/production-csid.json', json_encode($productionCsid->toArray(), JSON_PRETTY_PRINT));
```

Sign real invoices with `$productionCsid->getCertificate()` and the same private key.

### Submit invoice

```php
use Sevaske\ZatcaApi\Client;
use Sevaske\ZatcaApi\Credentials\ProductionCsid;
use Sevaske\ZatcaApi\Invoice\SignedInvoice;

$zatca = Client::production();
$productionCsid = ProductionCsid::fromArray(json_decode(file_get_contents('output/production-csid.json'), true));

// UUID read from the XML, invoice hash computed the same way ZATCA does
$invoice = SignedInvoice::fromXml($signedInvoice->getXML());

// Simplified invoices, credit and debit notes (B2C): report within 24 hours
$result = $zatca->invoices($productionCsid)->report($invoice);
$result->isReported();

// Standard invoices, credit and debit notes (B2B): clear before handing over to the buyer
$result = $zatca->invoices($productionCsid)->clear($invoice);

if ($result->isCleared()) {
    $clearedXml = $result->getClearedInvoice(); // always store it: this is the document for the buyer
}
```

A rejected invoice is returned as a result, not thrown: see `getValidationResults()`. More in the
[zatca-api documentation](https://github.com/sevaske/zatca-api#readme).

---

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

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

**Happy invoicing! 🧾✨**
