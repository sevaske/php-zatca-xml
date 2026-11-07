# php-zatca-xml

[![Tests](https://github.com/sevaske/php-zatca-xml/actions/workflows/phpunit.yml/badge.svg)](https://github.com/sevaske/php-zatca-xml/actions/workflows/phpunit.yml)
[![Latest Version](https://img.shields.io/packagist/v/sevaske/php-zatca-xml.svg)](https://packagist.org/packages/sevaske/php-zatca-xml)
[![PHP Version](https://img.shields.io/packagist/php-v/sevaske/php-zatca-xml.svg)](https://packagist.org/packages/sevaske/php-zatca-xml)
[![Downloads](https://img.shields.io/packagist/dt/sevaske/php-zatca-xml.svg)](https://packagist.org/packages/sevaske/php-zatca-xml)
[![License](https://img.shields.io/packagist/l/sevaske/php-zatca-xml.svg)](LICENSE)

PHP library for ZATCA (Fatoora) e-invoicing, phase 2 (integration), Saudi Arabia. It builds the UBL 2.1 XML
of tax invoices, credit notes and debit notes, signs it with your cryptographic stamp (XAdES), adds the QR code
and computes the invoice hash. It also generates the private key and CSR for onboarding.

Sending documents to ZATCA is done by a separate package, [sevaske/zatca-api](https://github.com/sevaske/zatca-api).
This README covers both, because one is useless without the other.

> Unofficial library, not affiliated with ZATCA. Validate your integration in the sandbox and the simulation
> environment before going live.

## Contents

- [How ZATCA e-invoicing works](#how-zatca-e-invoicing-works)
- [Installation](#installation)
- [The whole flow at a glance](#the-whole-flow-at-a-glance)
- [Step 1. Private key and CSR](#step-1-private-key-and-csr)
- [Step 2. Compliance CSID](#step-2-compliance-csid)
- [Step 3. Build an invoice](#step-3-build-an-invoice)
- [Step 4. Sign](#step-4-sign)
- [Step 5. Compliance checks and production CSID](#step-5-compliance-checks-and-production-csid)
- [Step 6. Report or clear real invoices](#step-6-report-or-clear-real-invoices)
- [Invoice data reference](#invoice-data-reference)
- [Calculating the totals](#calculating-the-totals)
- [VAT categories and exemptions](#vat-categories-and-exemptions)
- [Credit notes and debit notes](#credit-notes-and-debit-notes)
- [What you must store](#what-you-must-store)
- [Common rejections and their causes](#common-rejections-and-their-causes)
- [Errors thrown by the library](#errors-thrown-by-the-library)
- [Upgrading](#upgrading)

## How ZATCA e-invoicing works

ZATCA's process has many moving parts with similar names. This section explains the ones your code has to deal with.

### Two families of documents

| | Standard (B2B) | Simplified (B2C) |
|---|---|---|
| Buyer | a business, usually VAT-registered | a consumer |
| ZATCA name | tax invoice | simplified tax invoice |
| `InvoiceTypeCode/@name` | `01` + flags (`0100000`) | `02` + flags (`0200000`) |
| Submitted via | **clearance**: before the buyer gets it | **reporting**: within 24 hours after issuing |
| Who stamps it | you sign it, then ZATCA stamps it and returns the **cleared invoice** | you sign it, that is final |
| What the buyer receives | the cleared invoice returned by ZATCA | your signed invoice |
| QR code | added by ZATCA to the cleared invoice (yours is optional) | mandatory, yours |

Both families have the same four document types: invoice (`388`), credit note (`381`), debit note (`383`) and
prepayment invoice (`386`). The remaining five digits of `InvoiceTypeCode/@name` are flags: third-party, nominal,
export, summary and self-billed transaction.

If clearance is temporarily deactivated for a taxpayer, ZATCA answers clearance requests with HTTP 303 and standard
invoices are submitted through reporting instead.

### EGS units and CSIDs

Every device or software instance that issues invoices is an **EGS unit** (E-invoice Generation Solution). Each unit
has its own private key and its own certificate issued by ZATCA, called a **CSID** (Cryptographic Stamp Identifier).
The CSID plays two roles: it signs invoices (the certificate is embedded in the XML) and it authenticates API calls
(as username, with its secret as password).

A unit is onboarded once:

1. Generate a private key and a **CSR** describing the unit (VAT number, serial number, document types it will issue).
2. Get an **OTP** in the Fatoora portal. It is valid for one hour and there is no API for it.
3. Exchange CSR + OTP for a **compliance CSID**.
4. **Compliance checks**: sign sample documents with the compliance CSID and submit them to the compliance endpoint.
   Which samples depends on the document types declared in the CSR: standard invoice, credit note and debit note for
   `1000`; the simplified ones for `0100`; all six for `1100`.
5. Exchange the compliance CSID for a **production CSID**. From now on, real invoices are signed with it.

A production CSID expires and is renewed with a new CSR and a renewal OTP. If the renewal answers "not compliant",
the compliance checks are repeated.

There are three environments, each with its own CSIDs: **sandbox** (developer portal, mock responses, public test
values), **simulation** (real validation, OTPs from the simulation portal) and **production** ("core").

### The invoice chain

Every EGS unit keeps a chain of the documents it issues, across all types:

- **ICV** (invoice counter value): 1, 2, 3, ... incremented for every document, never reused.
- **PIH** (previous invoice hash): the hash of the previous document of the same unit. The first document uses
  `NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ==`.
- **UUID**: a unique identifier per document.
- **Invoice hash**: base64 SHA-256 of the canonical XML without the signature and the QR code. It is sent with the
  document and becomes the PIH of the next one.

ZATCA tracks rejected documents too. A rejected document is never resubmitted as is: fix the data and issue a
**new** document with a new ICV, a new UUID and a new hash; its PIH is the hash of the rejected one, because that was
the previous document of the unit.

### Errors and warnings

Every response carries validation results. A document with at least one **error** is rejected. **Warnings** do not
block acceptance (HTTP 202) but should be fixed.

## Installation

```bash
composer require sevaske/php-zatca-xml
composer require sevaske/zatca-api:^3.0   # to talk to the ZATCA API
```

Requirements: PHP 8.1+, extensions `dom`, `libxml`, `openssl`, `mbstring`, `fileinfo`, `hash`.

## The whole flow at a glance

| Step | What | Library |
|---|---|---|
| 1 | Private key and CSR for the EGS unit | `CertificateBuilder` |
| 2 | Compliance CSID (CSR + OTP) | zatca-api `onboarding()->requestComplianceCsid()` |
| 3 | Build the invoice XML | `InvoiceMapper`, `GeneratorInvoice` |
| 4 | Sign: XAdES signature, QR code, hash | `InvoiceSigner`, `Certificate` |
| 5 | Compliance checks, then production CSID | zatca-api `compliance()->checkInvoice()`, `onboarding()->requestProductionCsid()` |
| 6 | Report (B2C) or clear (B2B) real invoices | zatca-api `invoices()->report()` / `clear()` |

Steps 1, 2 and 5 happen once per EGS unit. Steps 3, 4 and 6 happen for every document.

Runnable versions of every step are in [`examples/`](examples).

## Step 1. Private key and CSR

```php
use Saleh7\Zatca\CertificateBuilder;

$builder = (new CertificateBuilder)
    ->setOrganizationIdentifier('399999999900003')        // VAT number: 15 digits, starts and ends with 3
    ->setSerialNumber('MyErp', 'v1', 'POS-0001')          // solution name, model, unique serial of this EGS unit
    ->setCommonName('POS-0001 Riyadh Branch')
    ->setOrganizationName('Maximum Speed Tech Supply LTD')
    ->setOrganizationalUnitName('Riyadh Branch')          // branch name; for VAT groups: the TIN of the member
    ->setCountryName('SA')
    ->setAddress('King Fahd Road, Riyadh')
    ->setBusinessCategory('Supply activities')
    ->setInvoiceType(1100)                                // document types: 1000 standard, 0100 simplified, 1100 both
    ->setProduction(false)                                // false: sandbox and simulation, true: production
    ->generate();

file_put_contents('storage/egs/private.pem', $builder->getPrivateKey());
file_put_contents('storage/egs/certificate.csr', $builder->getCsr());
```

The private key never leaves your system. Losing it means onboarding the unit again.

## Step 2. Compliance CSID

```php
use Sevaske\ZatcaApi\Client;

$zatca = Client::simulation();   // Client::sandbox() accepts the test OTP "123345"

$complianceCsid = $zatca->onboarding()
    ->requestComplianceCsid(file_get_contents('storage/egs/certificate.csr'), $otpFromFatooraPortal)
    ->toComplianceCsid();

file_put_contents('storage/egs/compliance-csid.json', json_encode($complianceCsid->toArray()));
```

## Step 3. Build an invoice

The invoice is described as an array (the full list of fields is in the [reference](#invoice-data-reference)) and
mapped to an `Invoice` object. The library does not calculate totals: you pass them, and the mapper can check them.

```php
use Saleh7\Zatca\GeneratorInvoice;
use Saleh7\Zatca\Mappers\InvoiceMapper;

$data = [
    'uuid' => '3cf5ee18-ee25-44ea-a444-2c37ba7f28be',
    'id' => 'INV-000123',                                 // your invoice number
    'issueDate' => '2026-01-15 10:30:00',
    'issueTime' => '2026-01-15 10:30:00',
    'currencyCode' => 'SAR',
    'taxCurrencyCode' => 'SAR',
    'invoiceType' => [
        'invoice' => 'simplified',                        // simplified | standard
        'type' => 'invoice',                              // invoice | credit | debit | prepayment
    ],
    'additionalDocuments' => [
        ['id' => 'ICV', 'uuid' => '123'],                 // invoice counter value of this EGS unit
        ['id' => 'PIH', 'attachment' => ['content' => $previousInvoiceHash]],
    ],
    'supplier' => [
        'registrationName' => 'Maximum Speed Tech Supply LTD',
        'taxId' => '399999999900003',                     // must be the VAT number of your CSID
        'identificationId' => '1010010000',
        'identificationType' => 'CRN',
        'address' => [
            'street' => 'King Fahd Road',
            'buildingNumber' => '1234',                   // 4 digits
            'subdivision' => 'Al Olaya',
            'city' => 'Riyadh',
            'postalZone' => '12345',                      // 5 digits
            'country' => 'SA',
        ],
    ],
    'paymentMeans' => ['code' => '10'],                   // 10 cash, 30 credit transfer, 42 bank account, 48 card
    'taxTotal' => [
        'taxAmount' => 30.00,
        'subTotals' => [[
            'taxableAmount' => 200.00,
            'taxAmount' => 30.00,
            'taxCategory' => ['percent' => 15, 'taxScheme' => ['id' => 'VAT']],
        ]],
    ],
    'legalMonetaryTotal' => [
        'lineExtensionAmount' => 200.00,
        'taxExclusiveAmount' => 200.00,
        'taxInclusiveAmount' => 230.00,
        'allowanceTotalAmount' => 0.00,
        'prepaidAmount' => 0.00,
        'payableAmount' => 230.00,
    ],
    'invoiceLines' => [[
        'id' => 1,
        'unitCode' => 'PCE',
        'quantity' => 2,
        'lineExtensionAmount' => 200.00,
        'item' => [
            'name' => 'Office chair',
            'classifiedTaxCategory' => [['percent' => 15, 'taxScheme' => ['id' => 'VAT']]],
        ],
        'price' => ['amount' => 100.00, 'unitCode' => 'PCE'],
        'taxTotal' => ['taxAmount' => 30.00, 'roundingAmount' => 230.00],
    ]],
];

$invoice = (new InvoiceMapper)->mapToInvoice($data, validateTotals: true, validateLines: true);
$xml = GeneratorInvoice::invoice($invoice)->getXML();
```

`mapToInvoice()` always checks that the required fields are present and throws `InvalidArgumentException` naming
the missing field. With `validateTotals` it also checks `taxInclusiveAmount = taxExclusiveAmount + taxAmount`; with
`validateLines` it checks every line (`lineExtensionAmount`, `roundingAmount`). Keep both enabled: it is much cheaper
than a rejection.

Building the objects directly (`new Invoice`, `new InvoiceLine`, ...) is possible as well; the mapper is the
recommended way.

## Step 4. Sign

```php
use Saleh7\Zatca\Helpers\Certificate;
use Saleh7\Zatca\InvoiceSigner;

$certificate = new Certificate(
    $csid->getCertificate(),                          // ComplianceCsid during onboarding, ProductionCsid afterwards
    file_get_contents('storage/egs/private.pem'),
    $csid->getSecret(),
);

$signed = InvoiceSigner::signInvoice($xml, $certificate);

$signed->getXML();     // the document to submit and to keep
$signed->getHash();    // invoice hash: send it with the document, store it as the next PIH
$signed->getQRCode();  // base64 TLV, already embedded in the XML; render it as a QR image on the printout
```

Never modify the signed XML: no pretty-printing, no re-encoding, no trimming. Any change breaks the hash and the
signature. Store and submit `getXML()` byte for byte.

## Step 5. Compliance checks and production CSID

Sign one sample of every required document type (see [EGS units and CSIDs](#egs-units-and-csids)) with the
compliance CSID and check each one:

```php
use Sevaske\ZatcaApi\Invoice\SignedInvoice;

$check = $zatca->compliance($complianceCsid)->checkInvoice(
    new SignedInvoice($signed->getXML(), $signed->getHash(), $data['uuid'])
);

if (! $check->isPassed()) {
    foreach ($check->getValidationResults()->getErrorMessages() as $error) {
        echo $error, PHP_EOL;   // "[BR-KSA-37] The seller address building number must contain 4 digits."
    }
}
```

Samples are part of the unit's chain: give each of them its own ICV, UUID and PIH.

When all samples pass:

```php
$productionCsid = $zatca->onboarding()->requestProductionCsid($complianceCsid)->toProductionCsid();

file_put_contents('storage/egs/production-csid.json', json_encode($productionCsid->toArray()));
```

## Step 6. Report or clear real invoices

Sign with the production CSID, then:

```php
use Sevaske\ZatcaApi\Client;
use Sevaske\ZatcaApi\Credentials\ProductionCsid;
use Sevaske\ZatcaApi\Invoice\SignedInvoice;

$zatca = Client::production();
$productionCsid = ProductionCsid::fromArray(json_decode(file_get_contents('storage/egs/production-csid.json'), true));
$document = new SignedInvoice($signed->getXML(), $signed->getHash(), $data['uuid']);

// Simplified documents: within 24 hours of issuing
$result = $zatca->invoices($productionCsid)->report($document);
$result->isReported();

// Standard documents: before handing them over to the buyer
$result = $zatca->invoices($productionCsid)->clear($document);

if ($result->isCleared()) {
    $clearedXml = $result->getClearedInvoice();   // store it and give THIS document to the buyer
}
```

A rejected document is a result, not an exception: check `isReported()` / `isCleared()` and
`getValidationResults()`. The [zatca-api README](https://github.com/sevaske/zatca-api#readme) covers responses,
errors and renewal in detail.

## Invoice data reference

Fields of the array passed to `InvoiceMapper::mapToInvoice()`. Amounts are numbers in the document currency.

### Document

| Key | Required | Description |
|---|---|---|
| `uuid` | yes | UUID of the document, unique forever |
| `id` | yes | your invoice number |
| `issueDate` | yes | date of issue; any format `DateTime` understands |
| `issueTime` | recommended | time of issue; combined with `issueDate` and written in UTC (`10:30:00Z`); current time if omitted |
| `invoiceType.invoice` | yes | `standard` or `simplified` |
| `invoiceType.type` | yes | `invoice`, `credit`, `debit` or `prepayment` |
| `invoiceType.isThirdParty`, `isNominal`, `isExport`, `isSummary`, `isSelfBilled` | no | transaction flags of `InvoiceTypeCode/@name`, `false` by default |
| `currencyCode` | yes | document currency, e.g. `SAR` |
| `taxCurrencyCode` | yes | `SAR` |
| `note`, `languageID` | no | one note (`cbc:Note`) and its language, `en` by default |
| `notes` | no | more notes: `[['text' => '...', 'languageId' => 'ar'], ...]` |
| `additionalDocuments` | yes | `['id' => 'ICV', 'uuid' => '<counter>']` and `['id' => 'PIH', 'attachment' => ['content' => '<previous hash>']]`; the QR reference is added automatically |
| `billingReferences` | credit / debit notes | `[['id' => '<original invoice number>', 'uuid' => '<original UUID>']]` |
| `delivery.actualDeliveryDate` | standard invoices | supply date |
| `delivery.latestDeliveryDate` | no | end date of a continuous supply |
| `paymentMeans.code` | recommended | UNTDID 4461: `10` cash, `30` credit transfer, `42` bank account, `48` bank card, `1` not defined (see `PaymentMeansCodeEnum`) |
| `paymentMeans.note` | credit / debit notes | reason for issuing the note |

### Parties

`supplier` is required. `customer` is required for standard documents; for simplified documents it is optional.

| Key | Description |
|---|---|
| `registrationName` | legal name; required for the supplier and for every buyer of a standard document |
| `taxId` | VAT number (15 digits). Supplier: required, must match the CSID. Buyer: required if VAT-registered |
| `identificationId`, `identificationType` | other identifier and its scheme. Seller: `CRN`, `MOM`, `MLS`, `700`, `SAG`, `OTH`. Buyer without VAT number: `TIN`, `CRN`, `MOM`, `MLS`, `700`, `SAG`, `NAT`, `GCC`, `IQA`, `PAS`, `OTH` |
| `address.street` | street name |
| `address.additionalStreet` | additional street name |
| `address.buildingNumber` | building number, 4 digits |
| `address.additionalNumber` | additional number of the national address |
| `address.subdivision` | district (neighborhood) |
| `address.city` | city |
| `address.postalZone` | postal code, 5 digits |
| `address.country` | ISO 3166-1 alpha-2, `SA` by default |

The address is required for the supplier and for buyers of standard documents. A buyer without a VAT number must
not have a `taxScheme`.

### Lines

| Key | Description |
|---|---|
| `id` | line number, 1–6 characters |
| `unitCode` | UN/ECE rec. 20 unit code, e.g. `PCE`, `C62`, `KGM`, `HUR` (see `UnitCodeEnum`) |
| `quantity` | quantity |
| `lineExtensionAmount` | net amount of the line (see [totals](#calculating-the-totals)) |
| `item.name` | item name |
| `item.classifiedTaxCategory` | `[['percent' => 15, 'id' => 'S', 'taxScheme' => ['id' => 'VAT']]]`; `id` is derived from the percent if omitted |
| `price.amount` | net unit price |
| `price.unitCode` | unit of the price |
| `price.allowanceCharges` | discount already included in the price (informative): `[['isCharge' => false, 'reason' => 'discount', 'amount' => 5.0]]` |
| `allowanceCharges` | line-level discounts / charges that reduce / increase `lineExtensionAmount`, with `taxCategories` |
| `taxTotal.taxAmount` | VAT of the line |
| `taxTotal.roundingAmount` | line amount including VAT |

### Document-level allowances and charges

```php
'allowanceCharges' => [[
    'isCharge' => false,                    // true for a charge
    'reason' => 'discount',
    'amount' => 10.00,
    'taxCategories' => [['percent' => 15, 'id' => 'S', 'taxScheme' => ['id' => 'VAT']]],
]],
```

Document-level charges require `legalMonetaryTotal.chargeTotalAmount`.

### Totals

| Key | Description |
|---|---|
| `taxTotal.taxAmount` | total VAT in the document currency |
| `taxTotal.taxAmountInTaxCurrency` | total VAT in SAR; required when `currencyCode` is not `SAR` |
| `taxTotal.subTotals[]` | one entry per VAT category and rate: `taxableAmount`, `taxAmount`, `taxCategory` (`id`, `percent`, `reasonCode`, `reason`) |
| `legalMonetaryTotal.lineExtensionAmount` | sum of the lines' `lineExtensionAmount` |
| `legalMonetaryTotal.allowanceTotalAmount` | sum of document-level allowances |
| `legalMonetaryTotal.chargeTotalAmount` | sum of document-level charges |
| `legalMonetaryTotal.taxExclusiveAmount` | total without VAT |
| `legalMonetaryTotal.taxInclusiveAmount` | total with VAT |
| `legalMonetaryTotal.prepaidAmount` | amount paid in advance (prepayment invoices) |
| `legalMonetaryTotal.payableAmount` | amount due |

## Calculating the totals

Inconsistent totals are the most common reason for rejection. Calculate in this order and round to two decimals at
each step:

```
line net           = quantity × price − line allowances + line charges          → lineExtensionAmount
line VAT           = line net × rate                                             → taxTotal.taxAmount
line total         = line net + line VAT                                         → taxTotal.roundingAmount

per VAT category:
taxable amount     = Σ line net − document allowances + document charges        → subTotals[].taxableAmount
VAT                = taxable amount × rate                                       → subTotals[].taxAmount

lineExtensionAmount = Σ line net
taxExclusiveAmount  = lineExtensionAmount − allowanceTotalAmount + chargeTotalAmount
taxAmount           = Σ subTotals[].taxAmount
taxInclusiveAmount  = taxExclusiveAmount + taxAmount
payableAmount       = taxInclusiveAmount − prepaidAmount
```

ZATCA recomputes these values. A category VAT that differs from `taxable × rate` produces a warning (`BR-CO-17`),
a line total that differs from `net + VAT` too (`BR-KSA-51`); other mismatches are errors.

Use a decimal library (for example `brick/math`, already installed) instead of floats for these calculations.

## VAT categories and exemptions

| `id` | Meaning | `percent` | Exemption reason |
|---|---|---|---|
| `S` | standard rate | `15` | – |
| `Z` | zero-rated (exports, international transport, qualifying medicines, ...) | `0` | required |
| `E` | exempt (financial services, residential rent, ...) | `0` | required |
| `O` | out of scope | `0` | required (`VATEX-SA-OOS`) |

The exemption reason goes into the subtotal of that category:

```php
'subTotals' => [[
    'taxableAmount' => 500.00,
    'taxAmount' => 0.00,
    'taxCategory' => [
        'id' => 'Z',
        'percent' => 0,
        'reasonCode' => 'VATEX-SA-32',
        'reason' => 'Export of goods',
        'taxScheme' => ['id' => 'VAT'],
    ],
]],
```

Use the same `id` in the lines (`item.classifiedTaxCategory`). The list of `VATEX-SA-*` codes is in the ZATCA
e-invoicing data dictionary. Without an explicit `id`, a category with a positive percent becomes `S` and a zero
percent becomes `Z`. `TaxCategoryCodeEnum` lists the codes.

## Credit notes and debit notes

A credit note (`'type' => 'credit'`) or debit note (`'type' => 'debit'`) corrects an issued invoice. It is a
document of the same family as the original (a standard credit note for a standard invoice), it is submitted the
same way (clearance or reporting) and it takes its own place in the chain.

Two things are required on top of a normal invoice:

```php
'invoiceType' => ['invoice' => 'standard', 'type' => 'credit'],
'billingReferences' => [['id' => 'INV-000123', 'uuid' => '3cf5ee18-ee25-44ea-a444-2c37ba7f28be']],
'paymentMeans' => ['code' => '10', 'note' => 'Returned goods'],   // reason for the credit note
```

Amounts are positive: the document type expresses the correction (ZATCA rejects negative amounts, rule
`BR-KSA-F-04`).

A rejected simplified invoice is invalid. Issue a corrected document as a new invoice; if the buyer already received
the rejected one, cancel it with a credit note.

## What you must store

Per EGS unit:

| Data | Why |
|---|---|
| private key | signs every document; losing it means onboarding again |
| production CSID (`ProductionCsid::toArray()`) | signing certificate and API credentials |
| last ICV | the next document uses ICV + 1 |
| last invoice hash | the next document's PIH |

Update the last ICV and hash atomically for every signed document, accepted or rejected. Two documents must never
be issued with the same ICV or the same PIH: issue documents of one unit sequentially, or give every process its own
EGS unit.

Per document: the signed XML exactly as returned by `getXML()`, its hash, UUID and ICV, the ZATCA status and
validation messages, and for standard documents the cleared invoice returned by ZATCA.

## Common rejections and their causes

| Message or code | Cause | Fix |
|---|---|---|
| `invalid-invoice-hash`, `invoiceHash_QRCODE_INVALID` | the XML was changed after signing, or the hash of another document was sent | submit `getXML()` and `getHash()` of the same signing, byte for byte |
| `certificate-permissions` "User only allowed to use the vat number that exists in the authentication certificate" | `supplier.taxId` differs from the VAT number in the CSID | use the VAT number of the CSR |
| signature errors (`invalid-digital-signature`, "Wrong Invoice Certificate", ...) | signed with another certificate or private key than the CSID used for the request | sign with the CSID that authenticates the request and its private key |
| HTTP 401 | wrong CSID or secret, or a compliance CSID used for reporting / clearance | use the production CSID of the same environment |
| `invoiceTimeStamp_QRCODE_INVALID` (warning) | QR timestamp differs from `IssueDate` / `IssueTime` | sign the XML generated by this library without changing the dates |
| `BR-KSA-98` (warning) | simplified document reported more than 24 hours after issuing | report sooner |
| `BR-KSA-37`, `BR-KSA-09` | incomplete seller address, building number not 4 digits | complete the address |
| `BR-KSA-F-13` (warning) | invalid format of the other seller / buyer identifier | check `identificationId` and its type |
| `BR-KSA-14` | buyer without VAT number and without a valid identifier | add `identificationId` with one of the buyer schemes |
| `BR-KSA-56` | credit / debit note without billing reference | add `billingReferences` |
| `BR-KSA-F-04` | negative amount | use positive amounts, the document type expresses the correction |
| `BR-CO-17`, `BR-KSA-51` (warnings), other `BR-CO-*` errors | totals don't add up | see [calculating the totals](#calculating-the-totals) |
| `XSD_ZATCA_INVALID` | XML structure invalid | usually a missing mandatory element; the message names it |
| HTTP 409 (reporting) / 208 (clearance) | the same document was submitted before | not an error: it is already reported / cleared |

## Errors thrown by the library

| Exception | When |
|---|---|
| `InvalidArgumentException` | missing or invalid invoice data (thrown by the mapper and the validators), invalid dates, QR values longer than 255 bytes, invalid certificate |
| `Saleh7\Zatca\Exceptions\CertificateBuilderException` | the CSR or the private key cannot be generated |
| `Saleh7\Zatca\Exceptions\ZatcaStorageException` | a file cannot be written by `saveXMLFile()` / `generateAndSave()` |

The library exceptions extend `Saleh7\Zatca\Exceptions\ZatcaException` and expose `context()`.

## Upgrading

From 3.x to 4.0: see [UPGRADE.md](UPGRADE.md) and [CHANGELOG.md](CHANGELOG.md).

## Credits

This package is a community-maintained fork of [Saleh7/php-zatca-xml](https://github.com/Saleh7/php-zatca-xml).

## License

MIT, see [LICENSE](LICENSE).
