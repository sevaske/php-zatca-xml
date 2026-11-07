# Upgrading from 3.x to 4.0

4.0 keeps the API of 3.x (mappers, `GeneratorInvoice`, `InvoiceSigner`, `Certificate`, ...). It removes the
dependency on `sevaske/zatca-api` and turns several silent fallbacks into explicit errors: invoices that 3.x
generated with wrong data now fail before they reach ZATCA.

## Dependencies

- `sevaske/zatca-api` is no longer required. Install it yourself if you talk to the ZATCA API; 4.0 works with
  any version, the examples use 3.x: `composer require sevaske/zatca-api:^3.0`.
- `sevaske/support` is no longer installed.
- `Saleh7\Zatca\Exceptions\ZatcaException` extends `\Exception` and implements the library's own
  `Saleh7\Zatca\Exceptions\ZatcaExceptionInterface`; `context()` and `withContext()` are unchanged.
  Catch `Saleh7\Zatca\Exceptions\ZatcaException` instead of the exception classes of `sevaske/zatca-api` / `sevaske/support`.

## Behaviour changes

| 3.x | 4.0 |
|---|---|
| Invalid dates (`2026-13-45`, `2026-02-30`) silently became "now" / rolled over | `InvalidArgumentException` naming the field |
| QR values longer than 255 bytes (e.g. a long Arabic seller name) produced a corrupt QR code | `InvalidArgumentException` |
| Document-level charges without `chargeTotalAmount` were accepted | `chargeTotalAmount` is required when a charge (`isCharge: true`) is present |
| Amounts always used `SAR` unless passed to `GeneratorInvoice::invoice()` | the invoice's `currencyCode` is used; `taxTotal.taxAmountInTaxCurrency` (VAT total in SAR) is required for other currencies |
| `new Storage('dir')` changed the base path of every later `Storage` | the base path belongs to the instance; set a global default with `Storage::setBasePath()` |
| A missing VAT `percent` defaulted to 15 | the `percent` is required in tax categories, subtotals and allowance tax categories |
| Price allowances defaulted to charges (`isCharge: true`) | they default to allowances (`isCharge: false`) |
| Only the last entry of `notes` was written | every note is written; `Invoice::addNote()` / `getNotes()` |
| An invoice line without a price wrote an empty `cac:TaxScheme` | `InvalidArgumentException` (every line needs a price, BR-26) |
| An invalid certificate failed later with an unrelated error | `Certificate` throws `InvalidArgumentException` |
| `InvoiceExtension::computeXmlDigest()` removed the signature and QR code from the document | the document is left unchanged |

## Output changes

- `cbc:Percent` of tax categories is written with two decimals (`15.00`), like `ClassifiedTaxCategory`.
- The QR timestamp equals `IssueDate` / `IssueTime` as written in the XML. 3.x appended `Z` to a local time,
  which ZATCA reports as `invoiceTimeStamp_QRCODE_INVALID`.
- An `IssueTime` with a zero UTC offset (`+00:00`, `GMT`, ...) is written with the `Z` suffix.
- Element order follows the UBL schema in `AllowanceCharge` (`BaseAmount`) and `InvoiceLine`
  (`AccountingCost` and `DocumentReference` before `AllowanceCharge`).

## API changes

- `CertificateBuilder::generate()` returns the builder (`self`), so calls can be chained.
- `Saleh7\Zatca\Enums\TaxCategoryCode` is renamed to `TaxCategoryCodeEnum`; `UnitCodeEnum` is added.
- `AllowanceCharge::getTaxCategory()` returns the list of tax categories (`?array`), as it always did at runtime.
- `DocumentReference` getters return `null` for values that are not set.
- `InvoiceAmountValidator::validateInvoiceLines()` takes line-level allowances and charges into account.
