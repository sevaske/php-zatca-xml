<?php

namespace Saleh7\Zatca\Tests;

use LogicException;
use PHPUnit\Framework\TestCase;
use Sabre\Xml\Service;
use Sabre\Xml\Writer;
use Saleh7\Zatca\CurrencyXmlService;
use Saleh7\Zatca\GeneratorInvoice;
use Saleh7\Zatca\LegalMonetaryTotal;
use Saleh7\Zatca\Mappers\InvoiceMapper;
use Saleh7\Zatca\Tests\Fixtures\InvoiceDataFixture;

class CurrencyContextTest extends TestCase
{
    public function xml(string $currency): string
    {
        $data = InvoiceDataFixture::simplified();
        $data['currencyCode'] = $currency;
        if ($currency !== 'SAR') {
            $data['taxTotal']['taxAmountInTaxCurrency'] = 25.73;
        }

        return GeneratorInvoice::invoice((new InvoiceMapper)->mapToInvoice($data))->getXML();
    }

    /** @return string[] distinct currencyID attribute values */
    private function currencies(string $xml): array
    {
        preg_match_all('/currencyID="(\w+)"/', $xml, $m);

        return array_values(array_unique($m[1]));
    }

    public function test_consecutive_documents_use_their_own_currency(): void
    {
        $sar = $this->xml('SAR');
        $usd = $this->xml('USD');
        $sarAgain = $this->xml('SAR');

        $this->assertSame(['SAR'], $this->currencies($sar));
        // A foreign-currency invoice has the VAT total in SAR as well as in the document currency.
        $this->assertSame(['SAR', 'USD'], $this->sorted($this->currencies($usd)));
        $this->assertStringContainsString('<cbc:PayableAmount currencyID="USD">', $usd);
        $this->assertSame($sar, $sarAgain);
    }

    public function test_explicit_currency_override_does_not_leak_to_later_documents(): void
    {
        $data = InvoiceDataFixture::simplified();
        $invoice = (new InvoiceMapper)->mapToInvoice($data);

        $this->assertSame(['EUR', 'SAR'], $this->sorted($this->currencies(GeneratorInvoice::invoice($invoice, 'EUR')->getXML())));
        $this->assertStringContainsString('<cbc:PayableAmount currencyID="EUR">', GeneratorInvoice::invoice($invoice, 'EUR')->getXML());
        $this->assertSame(['SAR'], $this->currencies(GeneratorInvoice::invoice($invoice)->getXML()));
    }

    public function test_generation_started_inside_another_serialization_is_isolated(): void
    {
        $invoice = (new InvoiceMapper)->mapToInvoice(InvoiceDataFixture::simplified());
        $expected = GeneratorInvoice::invoice($invoice)->getXML();
        $inner = null;

        // Generate a second document with another currency while the first is still being written.
        $service = new CurrencyXmlService('SAR');
        $service->namespaceMap = [
            'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2' => '',
            'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2' => 'cac',
            'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2' => 'cbc',
            'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2' => 'ext',
        ];
        $test = $this;
        $invoice->setLegalMonetaryTotal(new class($invoice->getLegalMonetaryTotal(), function () use ($test, &$inner): void {
            $inner = $test->xml('USD');
        }) extends LegalMonetaryTotal
        {

            public function __construct(private LegalMonetaryTotal $real, private \Closure $hook) {}

            public function xmlSerialize(Writer $writer): void
            {
                ($this->hook)();
                $this->real->xmlSerialize($writer);
            }
        });
        $service->write('Invoice', [$invoice]);

        $this->assertSame(['SAR', 'USD'], $this->sorted($this->currencies($inner)));
        $this->assertSame($expected, GeneratorInvoice::invoice($invoice)->getXML());
        $this->assertSame(['SAR'], $this->currencies($expected));
    }

    public function test_serializing_an_amount_part_without_a_currency_context_throws_a_clear_exception(): void
    {
        $total = (new LegalMonetaryTotal)->setLineExtensionAmount(1.0);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('document currency is unknown');

        (new Service)->write('LegalMonetaryTotal', $total);
    }

    /** @param string[] $values */
    private function sorted(array $values): array
    {
        sort($values);

        return $values;
    }
}
