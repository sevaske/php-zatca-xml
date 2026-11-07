<?php

namespace Saleh7\Zatca\Tests;

use DateTime;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saleh7\Zatca\AllowanceCharge;
use Saleh7\Zatca\Exceptions\ZatcaException;
use Saleh7\Zatca\Exceptions\ZatcaExceptionInterface;
use Saleh7\Zatca\GeneratorInvoice;
use Saleh7\Zatca\Helpers\Certificate;
use Saleh7\Zatca\Helpers\InvoiceExtension;
use Saleh7\Zatca\Mappers\InvoiceMapper;
use Saleh7\Zatca\Mappers\PriceMapper;
use Saleh7\Zatca\Mappers\Validators\InvoiceAmountValidator;
use Saleh7\Zatca\Tests\Fixtures\InvoiceDataFixture;

/**
 * Regression tests for the issues fixed in 4.0.
 */
class ReviewFixesTest extends TestCase
{
    /**
     * Hash returned by the signer for the fixture (accepted by the ZATCA sandbox).
     */
    private const FIXTURE_HASH = 'JDXm4w8lD0YRVX06qGEvWvd7EPr4Kalg2QcpCB0qKgI=';

    /**
     * Public ZATCA simulation sample certificate and key (same as in the examples).
     */
    private const CERTIFICATE = 'MIID3jCCA4SgAwIBAgITEQAAOAPF90Ajs/xcXwABAAA4AzAKBggqhkjOPQQDAjBiMRUwEwYKCZImiZPyLGQBGRYFbG9jYWwxEzARBgoJkiaJk/IsZAEZFgNnb3YxFzAVBgoJkiaJk/IsZAEZFgdleHRnYXp0MRswGQYDVQQDExJQUlpFSU5WT0lDRVNDQTQtQ0EwHhcNMjQwMTExMDkxOTMwWhcNMjkwMTA5MDkxOTMwWjB1MQswCQYDVQQGEwJTQTEmMCQGA1UEChMdTWF4aW11bSBTcGVlZCBUZWNoIFN1cHBseSBMVEQxFjAUBgNVBAsTDVJpeWFkaCBCcmFuY2gxJjAkBgNVBAMTHVRTVC04ODY0MzExNDUtMzk5OTk5OTk5OTAwMDAzMFYwEAYHKoZIzj0CAQYFK4EEAAoDQgAEoWCKa0Sa9FIErTOv0uAkC1VIKXxU9nPpx2vlf4yhMejy8c02XJblDq7tPydo8mq0ahOMmNo8gwni7Xt1KT9UeKOCAgcwggIDMIGtBgNVHREEgaUwgaKkgZ8wgZwxOzA5BgNVBAQMMjEtVFNUfDItVFNUfDMtZWQyMmYxZDgtZTZhMi0xMTE4LTliNTgtZDlhOGYxMWU0NDVmMR8wHQYKCZImiZPyLGQBAQwPMzk5OTk5OTk5OTAwMDAzMQ0wCwYDVQQMDAQxMTAwMREwDwYDVQQaDAhSUlJEMjkyOTEaMBgGA1UEDwwRU3VwcGx5IGFjdGl2aXRpZXMwHQYDVR0OBBYEFEX+YvmmtnYoDf9BGbKo7ocTKYK1MB8GA1UdIwQYMBaAFJvKqqLtmqwskIFzVvpP2PxT+9NnMHsGCCsGAQUFBwEBBG8wbTBrBggrBgEFBQcwAoZfaHR0cDovL2FpYTQuemF0Y2EuZ292LnNhL0NlcnRFbnJvbGwvUFJaRUludm9pY2VTQ0E0LmV4dGdhenQuZ292LmxvY2FsX1BSWkVJTlZPSUNFU0NBNC1DQSgxKS5jcnQwDgYDVR0PAQH/BAQDAgeAMDwGCSsGAQQBgjcVBwQvMC0GJSsGAQQBgjcVCIGGqB2E0PsShu2dJIfO+xnTwFVmh/qlZYXZhD4CAWQCARIwHQYDVR0lBBYwFAYIKwYBBQUHAwMGCCsGAQUFBwMCMCcGCSsGAQQBgjcVCgQaMBgwCgYIKwYBBQUHAwMwCgYIKwYBBQUHAwIwCgYIKoZIzj0EAwIDSAAwRQIhALE/ichmnWXCUKUbca3yci8oqwaLvFdHVjQrveI9uqAbAiA9hC4M8jgMBADPSzmd2uiPJA6gKR3LE03U75eqbC/rXA==';

    private const PRIVATE_KEY = 'MHQCAQEEIL14JV+5nr/sE8Sppaf2IySovrhVBtt8+yz+g4NRKyz8oAcGBSuBBAAKoUQDQgAEoWCKa0Sa9FIErTOv0uAkC1VIKXxU9nPpx2vlf4yhMejy8c02XJblDq7tPydo8mq0ahOMmNo8gwni7Xt1KT9UeA==';

    private static function generate(array $data): string
    {
        return GeneratorInvoice::invoice((new InvoiceMapper)->mapToInvoice($data))->getXML();
    }

    public function test_compute_xml_digest_does_not_modify_the_document(): void
    {
        $extension = InvoiceExtension::fromString(file_get_contents(__DIR__.'/Fixtures/simplified-invoice-signed.xml'));

        $this->assertSame(self::FIXTURE_HASH, $extension->computeXmlDigest());
        $this->assertNotNull($extension->find('ext:UBLExtensions'));
        $this->assertNotNull($extension->find('cac:Signature'));
        $this->assertSame(self::FIXTURE_HASH, $extension->computeXmlDigest());
    }

    public function test_multiple_notes_are_written(): void
    {
        $data = InvoiceDataFixture::simplified();
        $data['note'] = 'Main note';
        $data['notes'] = [['text' => 'ملاحظة', 'languageId' => 'ar'], ['text' => 'Second note', 'languageId' => 'en']];

        preg_match_all('#<cbc:Note languageID="(\w+)">(.*?)</cbc:Note>#u', self::generate($data), $notes, PREG_SET_ORDER);

        $this->assertSame([['en', 'Main note'], ['ar', 'ملاحظة'], ['en', 'Second note']], array_map(fn ($m) => [$m[1], $m[2]], $notes));
    }

    public function test_price_allowance_is_an_allowance_by_default(): void
    {
        $price = (new PriceMapper)->map(['amount' => 10, 'allowanceCharges' => [['reason' => 'discount', 'amount' => 1]]]);

        $this->assertFalse($price->getAllowanceCharges()[0]->isChargeIndicator());
    }

    public function test_allowance_charge_base_amount_follows_amount(): void
    {
        $allowance = (new AllowanceCharge)->setChargeIndicator(false)->setAllowanceChargeReason('discount')->setAmount(5)->setBaseAmount(50);
        $data = InvoiceDataFixture::simplified();
        $invoice = (new InvoiceMapper)->mapToInvoice($data);
        $invoice->setAllowanceCharges([$allowance]);

        $xml = GeneratorInvoice::invoice($invoice)->getXML();
        preg_match('#<cac:AllowanceCharge>(.*?)</cac:AllowanceCharge>#s', $xml, $block);
        preg_match_all('#<(?:cbc|cac):(\w+)#', $block[1], $elements);

        $this->assertSame(['ChargeIndicator', 'AllowanceChargeReason', 'Amount', 'BaseAmount'], array_values(array_unique($elements[1])));
    }

    public function test_invoice_line_elements_follow_the_ubl_order(): void
    {
        $data = InvoiceDataFixture::simplified();
        $data['invoiceLines'][0]['allowanceCharges'] = [['isCharge' => false, 'reason' => 'discount', 'amount' => 0.0, 'taxCategories' => [['percent' => 15, 'taxScheme' => ['id' => 'VAT']]]]];
        $invoice = (new InvoiceMapper)->mapToInvoice($data);
        $invoice->getInvoiceLines()[0]->setAccountingCost('Project A');

        preg_match('#<cac:InvoiceLine>(.*?)</cac:InvoiceLine>#s', GeneratorInvoice::invoice($invoice)->getXML(), $line);
        preg_match_all('#^\s{8}<(?:cbc|cac):(\w+)#m', $line[1], $elements);

        $this->assertSame(['ID', 'InvoicedQuantity', 'LineExtensionAmount', 'AccountingCost', 'AllowanceCharge', 'TaxTotal', 'Item', 'Price'], $elements[1]);
    }

    public function test_invoice_line_without_price_is_rejected(): void
    {
        $invoice = (new InvoiceMapper)->mapToInvoice(InvoiceDataFixture::simplified());
        $invoice->getInvoiceLines()[0]->setPrice(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('has no price');

        GeneratorInvoice::invoice($invoice);
    }

    public function test_tax_percent_keeps_two_decimals(): void
    {
        $data = InvoiceDataFixture::simplified();
        $data['taxTotal']['subTotals'][0]['taxCategory']['percent'] = 2.5;

        $this->assertStringContainsString('<cbc:Percent>2.50</cbc:Percent>', self::generate($data));
    }

    public function test_tax_percent_is_required(): void
    {
        $data = InvoiceDataFixture::simplified();
        unset($data['taxTotal']['subTotals'][0]['taxCategory']['percent']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"percent" of the tax subtotal tax category is required');

        (new InvoiceMapper)->mapToInvoice($data);
    }

    public function test_single_classified_tax_category_object_is_accepted(): void
    {
        $data = InvoiceDataFixture::simplified();
        $data['invoiceLines'][0]['item']['classifiedTaxCategory'] = ['percent' => 15, 'taxScheme' => ['id' => 'VAT']];

        $invoice = (new InvoiceMapper)->mapToInvoice($data);

        $this->assertCount(1, $invoice->getInvoiceLines()[0]->getItem()->getClassifiedTaxCategory());
    }

    public function test_line_validation_accounts_for_line_allowances_and_charges(): void
    {
        $line = InvoiceDataFixture::simplified()['invoiceLines'][0];
        $line['quantity'] = 2;
        $line['price']['amount'] = 50;
        $line['allowanceCharges'] = [['isCharge' => false, 'amount' => 10], ['isCharge' => true, 'amount' => 2.5]];
        $line['lineExtensionAmount'] = 92.5; // 2 * 50 - 10 + 2.5
        $line['taxTotal'] = ['taxAmount' => 13.88, 'roundingAmount' => 106.38];

        (new InvoiceAmountValidator)->validateInvoiceLines([$line]);

        $line['lineExtensionAmount'] = 100;
        $line['taxTotal']['roundingAmount'] = 113.88;
        $this->expectException(InvalidArgumentException::class);
        (new InvoiceAmountValidator)->validateInvoiceLines([$line]);
    }

    public function test_issue_time_with_zero_offset_is_utc(): void
    {
        $invoice = (new InvoiceMapper)->mapToInvoice(InvoiceDataFixture::simplified());
        $invoice->setIssueTime(new DateTime('10:00:00', new DateTimeZone('+00:00')));

        $this->assertStringContainsString('<cbc:IssueTime>10:00:00Z</cbc:IssueTime>', GeneratorInvoice::invoice($invoice)->getXML());
    }

    public function test_qr_timestamp_matches_the_xml_exactly(): void
    {
        $invoice = (new InvoiceMapper)->mapToInvoice(InvoiceDataFixture::simplified());
        $invoice->setIssueDate(new DateTime('2026-01-15', new DateTimeZone('Asia/Riyadh')));
        $invoice->setIssueTime(new DateTime('2026-01-15 10:00:00', new DateTimeZone('Asia/Riyadh')));
        $xml = GeneratorInvoice::invoice($invoice)->getXML();
        $certificate = new Certificate(self::CERTIFICATE, self::PRIVATE_KEY, 'secret');

        $tags = InvoiceExtension::fromString($xml)->generateQrTagsArray($certificate, self::FIXTURE_HASH, 'c2lnbmF0dXJl');

        $this->assertStringContainsString('<cbc:IssueTime>10:00:00</cbc:IssueTime>', $xml);
        $this->assertSame('2026-01-15T10:00:00', $tags[2]->getValue());
    }

    public function test_address_elements_follow_the_ubl_order(): void
    {
        $data = InvoiceDataFixture::simplified();
        $data['supplier']['address']['additionalNumber'] = '3724';
        $data['supplier']['address']['additionalStreet'] = 'King Fahd Road';

        preg_match('#<cac:AccountingSupplierParty>.*?<cac:PostalAddress>(.*?)</cac:PostalAddress>#s', self::generate($data), $address);
        preg_match_all('#<cbc:(\w+)>#', $address[1], $elements);

        $this->assertSame(['StreetName', 'AdditionalStreetName', 'BuildingNumber', 'PlotIdentification', 'CitySubdivisionName', 'CityName', 'PostalZone', 'IdentificationCode'], $elements[1]);
    }

    public function test_invalid_certificate_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to load the X.509 certificate');

        new Certificate('not a certificate', self::PRIVATE_KEY, 'secret');
    }

    public function test_exceptions_carry_context_without_external_packages(): void
    {
        $exception = (new ZatcaException('Failed', ['path' => 'a']))->withContext(['reason' => 'b']);

        $this->assertInstanceOf(ZatcaExceptionInterface::class, $exception);
        $this->assertSame(['path' => 'a', 'reason' => 'b'], $exception->context());
        $this->assertSame('An error occurred.', (new ZatcaException)->getMessage());
    }
}
