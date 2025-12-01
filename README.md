<p align="center">
<img src="https://badgen.net/packagist/php/sevaske/php-zatca-xml" alt="php Vers ion">
<a href="https://packagist.org/packages/sevaske/php-zatca-xml"><img alt="Packagist Stars" src="https://img.shields.io/packagist/stars/sevaske/php-zatca-xml"></a>
<a href="https://packagist.org/packages/sevaske/php-zatca-xml"><img alt="Packagist Downloads" src="https://img.shields.io/packagist/dt/sevaske/php-zatca-xml"></a>
<a href="https://packagist.org/packages/sevaske/php-zatca-xml"><img alt="Packagist Version" src="https://img.shields.io/packagist/v/sevaske/php-zatca-xml"></a>
<a href="https://packagist.org/packages/sevaske/php-zatca-xml"><img alt="License" src="https://img.shields.io/badge/License-MIT-yellow.svg"></a>
</p>

<p align="center">
Please feel free to <a href="https://github.com/sevaske/php-zatca-xml/pulls?q=sort%3Aupdated-desc+is%3Apr+is%3Aopen"><strong>contribute</strong></a> if you are missing features or tags
<br />
<a href="https://github.com/sevaske/php-zatca-xml/tree/main/examples">View Examples</a>
·
<a href="https://github.com/sevaske/php-zatca-xml/issues">Report a bug</a>
</p>

# FORK!

**Note:** The original author of the repository [Saleh7/php-zatca-xml](https://github.com/Saleh7/php-zatca-xml) has been inactive and unresponsive for several months.

This repository is a community-maintained fork with fixes and updates to keep the library functional and improved.

All changes and bug fixes are collected here to support users and continue development.

**Namespaces:**  
The original namespace `Saleh7\Zatca` has not been changed to maintain compatibility.

**Future Merge:**  
If the original author returns and resumes activity, we are open to merging all changes and improvements back into the original repository.

**API Integration:**  
API-related functionality has been refactored and moved to a separate library for better modularity and maintainability:  
🔗 [sevaske/zatca-api:^1.0](https://github.com/sevaske/zatca-api/tree/v1)

Original project: [https://github.com/Saleh7/php-zatca-xml](https://github.com/Saleh7/php-zatca-xml)


## 📖 Introduction  

This is an unofficial PHP library for generating ZATCA Fatoora e-invoices (simplified invoice, simplified credit note, simplified debit note, standard invoice, standard credit note, standard debit note), certificates, and for interacting with the API.  


## ✨ Features  

- 🚀 **ZATCA-Compliant** – Easily generate valid e-invoices for ZATCA regulations  
- 📜 **Invoice Creation** – Generate standard and simplified invoices in XML format  
- 🔐 **Digital Signing** – Sign invoices securely to ensure compliance  
- 🏷 **QR Code Generation** – Automatically generate QR codes for invoices  
- 📡 **Direct Submission to ZATCA** – Send invoices directly to ZATCA’s servers  
- ⚡ **Lightweight & Fast** – Optimized for performance and easy integration in PHP projects  
- 🔄 **Customizable & Extensible** – Easily adapt the library to your needs  


## 📌 Requirements  

### ✅ PHP Version  
- **PHP 8.1 or higher**


## 🛠 Installation  

```bash
composer require sevaske/php-zatca-xml
```

## 🚀 Usage  

This library simplifies the process of generating **ZATCA-compliant** e-invoices, handling **certificates**, signing invoices, and submitting them to **ZATCA’s API**. 

You can find working examples for generating and signing invoices and notes here:

🔗 [examples](https://github.com/sevaske/php-zatca-xml/tree/main/examples)

---

### 📜 **1. Generating a Compliance Certificate**  

First, generate a **certificate signing request (CSR)** and private key:  

```php
use Saleh7\Zatca\CertificateBuilder;
use Saleh7\Zatca\Exceptions\CertificateBuilderException;

try {
    (new CertificateBuilder())
        ->setOrganizationIdentifier('312345678901233') // The Organization Identifier must be 15 digits, starting andending with 3
        // string $solutionName .. The solution provider name
        // string $model .. The model of the unit the stamp is being generated for
        // string $serialNumber .. # If you have multiple devices each should have a unique serial number
        ->setSerialNumber('Saleh', '1n', 'SME00023')
        ->setCommonName('My Organization') // The common name to be used in the certificate
        ->setCountryName('SA') // The Country name must be Two chars only
        ->setOrganizationName('My Company') // The name of your organization
        ->setOrganizationalUnitName('IT Department') // A subunit in your organizatio
        ->setAddress('Riyadh 1234 Street') // like Riyadh 1234 Street 
        ->setInvoiceType(1100)// # Four digits, each digit acting as a bool. The order is as follows: Standard Invoice, Simplified, future use, future use 
        ->setProduction(false)// true = Production |  false = Testing
        ->setBusinessCategory('Technology') // Your business category like food, real estate, etc
        
        ->generateAndSave('output/certificate.csr', 'output/private.pem');
        
    echo "Certificate and private key saved.\n";
} catch (CertificateBuilderException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
```

### 🔐 **2. Requesting a Compliance Certificate from ZATCA**  

Once the CSR is generated, you need to request a **compliance certificate** from **ZATCA's API**.  

```php
use GuzzleHttp\Client;
use Sevaske\ZatcaApi\Api;
use Sevaske\ZatcaApi\Exceptions\ZatcaException;

$api = new Api('sandbox', new Client);
$certificatePath = __DIR__.'/output/certificate.csr';
$csr = file_get_contents($certificatePath);

try {
    $response = $api->complianceCertificate($csr, '123123');
    $credentials = [
        'requestId' => $response->requestId(),
        'certificate' => $response->certificate(),
        'secret' => $response->secret(),
    ];

    print_r($credentials);

    // sava file output/ZATCA_certificate_data.json
    $outputFile = __DIR__.'/output/ZATCA_certificate_data.json';
    file_put_contents($outputFile, json_encode($credentials, JSON_PRETTY_PRINT));

    echo "\nCertificate data saved to {$outputFile}\n";
} catch (ZatcaException $e) {
    echo 'API Error: '.$e->getMessage()."\n";
    print_r($e->context());
} catch (\Exception $e) {
    echo 'Error: '.$e->getMessage();
}
```

### 🧾 **3. Generating and signing an Invoice XML**  

Now that we have the compliance certificate, we can generate a **ZATCA-compliant e-invoice in XML format**.

Example of the simplified invoice:

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

// sign the invoice XML with the certificate
$certificate = (new Certificate(
    'MIID3jCCA4SgAwIBAgITEQAAOAPF90Ajs/xcXwABAAA4AzAKBggqhkjOPQQDAjBiMRUwEwYKCZImiZPyLGQBGRYFbG9jYWwxEzARBgoJkiaJk/IsZAEZFgNnb3YxFzAVBgoJkiaJk/IsZAEZFgdleHRnYXp0MRswGQYDVQQDExJQUlpFSU5WT0lDRVNDQTQtQ0EwHhcNMjQwMTExMDkxOTMwWhcNMjkwMTA5MDkxOTMwWjB1MQswCQYDVQQGEwJTQTEmMCQGA1UEChMdTWF4aW11bSBTcGVlZCBUZWNoIFN1cHBseSBMVEQxFjAUBgNVBAsTDVJpeWFkaCBCcmFuY2gxJjAkBgNVBAMTHVRTVC04ODY0MzExNDUtMzk5OTk5OTk5OTAwMDAzMFYwEAYHKoZIzj0CAQYFK4EEAAoDQgAEoWCKa0Sa9FIErTOv0uAkC1VIKXxU9nPpx2vlf4yhMejy8c02XJblDq7tPydo8mq0ahOMmNo8gwni7Xt1KT9UeKOCAgcwggIDMIGtBgNVHREEgaUwgaKkgZ8wgZwxOzA5BgNVBAQMMjEtVFNUfDItVFNUfDMtZWQyMmYxZDgtZTZhMi0xMTE4LTliNTgtZDlhOGYxMWU0NDVmMR8wHQYKCZImiZPyLGQBAQwPMzk5OTk5OTk5OTAwMDAzMQ0wCwYDVQQMDAQxMTAwMREwDwYDVQQaDAhSUlJEMjkyOTEaMBgGA1UEDwwRU3VwcGx5IGFjdGl2aXRpZXMwHQYDVR0OBBYEFEX+YvmmtnYoDf9BGbKo7ocTKYK1MB8GA1UdIwQYMBaAFJvKqqLtmqwskIFzVvpP2PxT+9NnMHsGCCsGAQUFBwEBBG8wbTBrBggrBgEFBQcwAoZfaHR0cDovL2FpYTQuemF0Y2EuZ292LnNhL0NlcnRFbnJvbGwvUFJaRUludm9pY2VTQ0E0LmV4dGdhenQuZ292LmxvY2FsX1BSWkVJTlZPSUNFU0NBNC1DQSgxKS5jcnQwDgYDVR0PAQH/BAQDAgeAMDwGCSsGAQQBgjcVBwQvMC0GJSsGAQQBgjcVCIGGqB2E0PsShu2dJIfO+xnTwFVmh/qlZYXZhD4CAWQCARIwHQYDVR0lBBYwFAYIKwYBBQUHAwMGCCsGAQUFBwMCMCcGCSsGAQQBgjcVCgQaMBgwCgYIKwYBBQUHAwMwCgYIKwYBBQUHAwIwCgYIKoZIzj0EAwIDSAAwRQIhALE/ichmnWXCUKUbca3yci8oqwaLvFdHVjQrveI9uqAbAiA9hC4M8jgMBADPSzmd2uiPJA6gKR3LE03U75eqbC/rXA==',
    'MHQCAQEEIL14JV+5nr/sE8Sppaf2IySovrhVBtt8+yz+g4NRKyz8oAcGBSuBBAAKoUQDQgAEoWCKa0Sa9FIErTOv0uAkC1VIKXxU9nPpx2vlf4yhMejy8c02XJblDq7tPydo8mq0ahOMmNo8gwni7Xt1KT9UeA==',
    'secret'
));
$signedInvoice = InvoiceSigner::signInvoice($generatorInvoice->getXML(), $certificate);

$outputXML = GeneratorInvoice::invoice($invoice)->saveXMLFile('Simplified_Invoice.xml');
echo "Simplified Invoice Generated Successfully\n";

$signedInvoice->saveXMLFile('Simplified_Invoice_Signed.xml');
echo "Simplified Invoice Signed Successfully\n";
```

Other examples you can find in the ./examples folder.

### 📤 **5. Submitting the Signed Invoice to ZATCA**  

Once the invoice is **digitally signed**, it can be submitted to **ZATCA’s API** for compliance validation and clearance.


## Contributing

Pull requests are welcome. For major changes, please open an issue first
to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License

This project is licensed under the [MIT License](https://github.com/Saleh7/php-zatca-xml/blob/main/LICENSE).
