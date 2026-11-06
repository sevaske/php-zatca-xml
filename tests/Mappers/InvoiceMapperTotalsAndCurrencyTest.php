<?php

namespace Saleh7\Zatca\Tests\Mappers;

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

    public function test_document_level_charge_without_charge_total_amount_is_still_accepted(): void
    {
        $data = InvoiceDataFixture::simplified();
        $data['allowanceCharges'][0]['isCharge'] = true;

        $this->assertStringNotContainsString('ChargeTotalAmount', $this->generate($data));
    }

    public function test_tax_amount_in_tax_currency_is_written_in_tax_currency(): void
    {
        $data = InvoiceDataFixture::simplified();
        $data['currencyCode'] = 'USD';
        $data['taxTotal']['taxAmountInTaxCurrency'] = 25.73;

        $invoice = (new InvoiceMapper)->mapToInvoice($data);
        $xml = GeneratorInvoice::invoice($invoice, 'USD')->getXML();

        $this->assertStringContainsString('<cbc:DocumentCurrencyCode>USD</cbc:DocumentCurrencyCode>', $xml);
        $this->assertStringContainsString('<cbc:PayableAmount currencyID="USD">52.61</cbc:PayableAmount>', $xml);
        $this->assertSame([['SAR', '25.73'], ['USD', '6.86']], $this->taxTotals($xml));
    }

    public function test_without_tax_amount_in_tax_currency_output_is_unchanged(): void
    {
        $data = InvoiceDataFixture::simplified();
        $data['currencyCode'] = 'USD';

        $invoice = (new InvoiceMapper)->mapToInvoice($data);

        $this->assertSame([['SAR', '6.86'], ['SAR', '6.86']], $this->taxTotals(GeneratorInvoice::invoice($invoice)->getXML()));
        $this->assertSame([['USD', '6.86'], ['USD', '6.86']], $this->taxTotals(GeneratorInvoice::invoice($invoice, 'USD')->getXML()));
    }

    /**
     * @return array<int, array{0: string, 1: string}> currency and amount of the first two TaxTotal elements
     */
    private function taxTotals(string $xml): array
    {
        preg_match_all('#<cac:TaxTotal>\s*<cbc:TaxAmount currencyID="(\w+)">([\d.]+)<#', $xml, $matches, PREG_SET_ORDER);

        return array_map(fn ($m) => [$m[1], $m[2]], array_slice($matches, 0, 2));
    }
}
