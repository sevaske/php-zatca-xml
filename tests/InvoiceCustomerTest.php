<?php

namespace Saleh7\Zatca\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Saleh7\Zatca\GeneratorInvoice;
use Saleh7\Zatca\Invoice;
use Saleh7\Zatca\Mappers\InvoiceMapper;
use Saleh7\Zatca\Tests\Fixtures\InvoiceDataFixture;

/**
 * The buyer is optional for simplified (B2C) invoices built directly with the Invoice API.
 */
class InvoiceCustomerTest extends TestCase
{
    /**
     * Build an invoice from the fixture and drop the customer, as if setAccountingCustomerParty() was never called.
     */
    private static function invoiceWithoutCustomer(string $category): Invoice
    {
        $data = InvoiceDataFixture::simplified();
        $data['invoiceType']['invoice'] = $category;
        $invoice = (new InvoiceMapper)->mapToInvoice($data);

        (new ReflectionProperty(Invoice::class, 'accountingCustomerParty'))->setValue($invoice, null);

        return $invoice;
    }

    public function test_simplified_invoice_without_customer_gets_an_empty_party(): void
    {
        $xml = GeneratorInvoice::invoice(self::invoiceWithoutCustomer('simplified'))->getXML();

        $this->assertMatchesRegularExpression('#<cac:AccountingCustomerParty>\s*<cac:Party/>\s*</cac:AccountingCustomerParty>#', $xml);
    }

    public function test_simplified_invoice_without_customer_matches_the_mapper_output(): void
    {
        $data = InvoiceDataFixture::simplified();
        unset($data['customer']);
        $mapped = GeneratorInvoice::invoice((new InvoiceMapper)->mapToInvoice($data))->getXML();

        $direct = GeneratorInvoice::invoice(self::invoiceWithoutCustomer('simplified'))->getXML();

        $this->assertSame($mapped, $direct);
    }

    public function test_standard_invoice_without_customer_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('accountingCustomerParty');

        GeneratorInvoice::invoice(self::invoiceWithoutCustomer('standard'));
    }
}
