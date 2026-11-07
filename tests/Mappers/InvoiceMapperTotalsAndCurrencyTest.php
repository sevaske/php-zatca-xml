<?php

namespace Saleh7\Zatca\Tests\Mappers;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saleh7\Zatca\GeneratorInvoice;
use Saleh7\Zatca\Mappers\InvoiceMapper;
use Saleh7\Zatca\Tests\Fixtures\InvoiceDataFixture;

class InvoiceMapperTotalsAndCurrencyTest extends TestCase
{
    private function generate(array $data): string
    {
        return GeneratorInvoice::invoice((new InvoiceMapper)->mapToInvoice($data))->getXML();
    }

    public function test_charge_total_amount_is_mapped(): void
    {
        $data = InvoiceDataFixture::simplified();
        $data['allowanceCharges'][0]['isCharge'] = true;
        $data['allowanceCharges'][0]['amount'] = 5.0;
        $data['legalMonetaryTotal']['chargeTotalAmount'] = 5.0;

        $this->assertStringContainsString(
            '<cbc:ChargeTotalAmount currencyID="SAR">5.00</cbc:ChargeTotalAmount>',
            $this->generate($data)
        );
    }

    public function test_charge_total_amount_is_omitted_when_not_provided(): void
    {
        $this->assertStringNotContainsString('ChargeTotalAmount', $this->generate(InvoiceDataFixture::simplified()));
    }

    public function test_document_level_charge_requires_charge_total_amount(): void
    {
        $data = InvoiceDataFixture::simplified();
        $data['allowanceCharges'][0]['isCharge'] = true;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('chargeTotalAmount');

        (new InvoiceMapper)->mapToInvoice($data);
    }

    public function test_amounts_use_the_invoice_document_currency(): void
    {
        $data = InvoiceDataFixture::simplified();
        $data['currencyCode'] = 'USD';
        $data['taxTotal']['taxAmountInTaxCurrency'] = 25.73;

        $xml = $this->generate($data);

        $this->assertStringContainsString('<cbc:DocumentCurrencyCode>USD</cbc:DocumentCurrencyCode>', $xml);
        $this->assertStringContainsString('<cbc:TaxCurrencyCode>SAR</cbc:TaxCurrencyCode>', $xml);
        $this->assertStringContainsString('<cbc:PayableAmount currencyID="USD">52.61</cbc:PayableAmount>', $xml);

        preg_match_all('#<cac:TaxTotal>\s*<cbc:TaxAmount currencyID="(\w+)">([\d.]+)<#', $xml, $matches);
        $this->assertSame(['SAR', 'USD'], array_slice($matches[1], 0, 2));
        $this->assertSame(['25.73', '6.86'], array_slice($matches[2], 0, 2));
    }

    public function test_foreign_currency_without_tax_currency_amount_throws(): void
    {
        $data = InvoiceDataFixture::simplified();
        $data['currencyCode'] = 'USD';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tax amount in tax currency (SAR)');

        $this->generate($data);
    }

    public function test_sar_invoice_tax_totals_are_unchanged(): void
    {
        $xml = $this->generate(InvoiceDataFixture::simplified());

        preg_match_all('#<cac:TaxTotal>\s*<cbc:TaxAmount currencyID="(\w+)">([\d.]+)<#', $xml, $matches);
        $this->assertSame(['SAR', 'SAR'], array_slice($matches[1], 0, 2));
        $this->assertSame(['6.86', '6.86'], array_slice($matches[2], 0, 2));
    }

    public function test_explicit_generator_currency_still_overrides(): void
    {
        $invoice = (new InvoiceMapper)->mapToInvoice(InvoiceDataFixture::simplified());

        $this->assertStringContainsString(
            '<cbc:PayableAmount currencyID="EUR">',
            GeneratorInvoice::invoice($invoice, 'EUR')->getXML()
        );
    }
}
