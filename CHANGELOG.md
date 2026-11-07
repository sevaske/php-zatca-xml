# Changelog

All notable changes to this project will be documented in this file.

## [4.0.0] - Unreleased

See [UPGRADE.md](UPGRADE.md) for upgrading from 3.x.

### Changed

- No dependency on `sevaske/zatca-api` (works with any version; the examples use 3.x) and `sevaske/support`.
- Invalid dates, QR values longer than 255 bytes, missing VAT percents, invoice lines without a price and
  invalid certificates throw instead of producing a wrong invoice.
- `chargeTotalAmount` is required with document-level charges; foreign-currency invoices use their currency and
  require the VAT total in SAR (`taxAmountInTaxCurrency`).
- `CertificateBuilder::generate()` returns the builder; `TaxCategoryCode` enum renamed to `TaxCategoryCodeEnum`.

### Removed

- File storage: `Storage`, `ZatcaStorageException`, `GeneratorInvoice::saveXMLFile()`, `InvoiceSigner::saveXMLFile()`,
  `CertificateBuilder::generateAndSave()` and `savePrivateKey()`. Store `getXML()`, `getCsr()` and `getPrivateKey()`
  yourself.

### Added

- Several notes per invoice (`Invoice::addNote()`, `getNotes()`, mapper `notes`).
- `UnitCodeEnum`.
- Examples for the full onboarding (compliance CSID, production CSID) and submission with zatca-api 3.

### Fixed

- QR timestamp now matches the XML (ZATCA warned `invoiceTimeStamp_QRCODE_INVALID` for local times).
- Issue date no longer shifted by a day in non-UTC timezones (also in 3.x).
- `computeXmlDigest()` no longer modifies the document.
- Line validation accounts for line-level allowances and charges.
- Price allowances default to allowances; a single `classifiedTaxCategory` object is accepted.
- UBL element order in `AllowanceCharge` and `InvoiceLine`; VAT percent written with two decimals.
- PHPStan level 5 clean.
