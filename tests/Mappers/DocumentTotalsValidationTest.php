<?php

namespace Saleh7\Zatca\Tests\Mappers;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saleh7\Zatca\Mappers\Validators\InvoiceAmountValidator;
use Saleh7\Zatca\Tests\Fixtures\InvoiceDataFixture;

class DocumentTotalsValidationTest extends TestCase
{
    /**
     * Fixture with a document-level allowance (10) and charge (5) on lines worth 45.75:
     * taxable 40.75, VAT 6.11, total 46.86, prepaid 6.86, payable 40.00.
     */
    private static function data(): array
    {
        $data = InvoiceDataFixture::simplified();
        $data['allowanceCharges'] = [
            ['isCharge' => false, 'reason' => 'discount', 'amount' => 10.0, 'taxCategories' => [['percent' => 15, 'taxScheme' => ['id' => 'VAT']]]],
            ['isCharge' => true, 'reason' => 'delivery', 'amount' => 5.0, 'taxCategories' => [['percent' => 15, 'taxScheme' => ['id' => 'VAT']]]],
        ];
        $data['taxTotal']['taxAmount'] = 6.11;
        $data['taxTotal']['subTotals'][0]['taxableAmount'] = 40.75;
        $data['taxTotal']['subTotals'][0]['taxAmount'] = 6.11;
        $data['legalMonetaryTotal'] = [
            'lineExtensionAmount' => 45.75,
            'allowanceTotalAmount' => 10.0,
            'chargeTotalAmount' => 5.0,
            'taxExclusiveAmount' => 40.75,
            'taxInclusiveAmount' => 46.86,
            'prepaidAmount' => 6.86,
            'payableAmount' => 40.00,
        ];

        return $data;
    }

    public function test_consistent_totals_pass(): void
    {
        (new InvoiceAmountValidator)->validateMonetaryTotals(self::data());

        $this->addToAssertionCount(1);
    }

    public function inconsistentTotalsProvider(): array
    {
        return [
            'line extension differs from the lines' => [fn (array $d) => array_replace_recursive($d, ['legalMonetaryTotal' => ['lineExtensionAmount' => 50.75, 'taxExclusiveAmount' => 45.75, 'taxInclusiveAmount' => 51.86, 'payableAmount' => 45.00]]), 'sum of the invoice lines'],
            'allowance total differs from the allowances' => [fn (array $d) => array_replace_recursive($d, ['legalMonetaryTotal' => ['allowanceTotalAmount' => 0.0]]), 'document-level allowances'],
            'charge total differs from the charges' => [fn (array $d) => array_replace_recursive($d, ['legalMonetaryTotal' => ['chargeTotalAmount' => 0.0]]), 'document-level charges'],
            'tax exclusive ignores the allowance' => [function (array $d) { unset($d['allowanceCharges']); $d['legalMonetaryTotal']['taxExclusiveAmount'] = 45.75; $d['legalMonetaryTotal']['taxInclusiveAmount'] = 51.86; $d['legalMonetaryTotal']['payableAmount'] = 45.00; return $d; }, 'lineExtensionAmount - allowanceTotalAmount + chargeTotalAmount'],
            'tax total differs from the subtotals' => [function (array $d) { $d['taxTotal']['subTotals'][0]['taxAmount'] = 6.50; return $d; }, "subtotals' taxAmount"],
            'taxable amounts differ from tax exclusive' => [function (array $d) { $d['taxTotal']['subTotals'][0]['taxableAmount'] = 45.75; return $d; }, "subtotals' taxableAmount"],
            'payable ignores the prepaid amount' => [fn (array $d) => array_replace_recursive($d, ['legalMonetaryTotal' => ['payableAmount' => 46.86]]), 'taxInclusiveAmount - prepaidAmount'],
        ];
    }

    /**
     * @dataProvider inconsistentTotalsProvider
     */
    public function test_inconsistent_totals_are_rejected(callable $break, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        (new InvoiceAmountValidator)->validateMonetaryTotals($break(self::data()));
    }
}
