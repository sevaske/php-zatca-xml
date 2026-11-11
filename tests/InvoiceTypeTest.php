<?php

namespace Saleh7\Zatca\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saleh7\Zatca\InvoiceType;
use TypeError;

class InvoiceTypeTest extends TestCase
{
    public function test_values_are_normalized_case_insensitively(): void
    {
        $type = (new InvoiceType)->setInvoice('STANDARD')->setInvoiceType('Credit');

        $this->assertSame('standard', $type->getInvoice());
        $this->assertSame('credit', $type->getInvoiceType());
    }

    public function test_getters_return_null_until_set(): void
    {
        $type = new InvoiceType;

        $this->assertNull($type->getInvoice());
        $this->assertNull($type->getInvoiceType());
    }

    /** @dataProvider emptyValues */
    public function test_empty_values_are_rejected(string $method, string $value, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        (new InvoiceType)->$method($value);
    }

    public static function emptyValues(): array
    {
        return [
            'invoice empty' => ['setInvoice', '', 'Invoice category cannot be empty.'],
            'invoice blank' => ['setInvoice', '  ', 'Invoice category cannot be empty.'],
            'type empty' => ['setInvoiceType', '', 'Invoice type cannot be empty.'],
            'type blank' => ['setInvoiceType', " \t", 'Invoice type cannot be empty.'],
        ];
    }

    /** @dataProvider setters */
    public function test_null_is_not_accepted_and_raises_no_deprecation(string $method): void
    {
        $previous = error_reporting(E_ALL);
        $deprecations = [];
        set_error_handler(function (int $no, string $str) use (&$deprecations): bool {
            $deprecations[] = $str;

            return true;
        });

        try {
            $this->expectException(TypeError::class);
            (new InvoiceType)->$method(null);
        } finally {
            restore_error_handler();
            error_reporting($previous);
            $this->assertSame([], $deprecations);
        }
    }

    public static function setters(): array
    {
        return [['setInvoice'], ['setInvoiceType']];
    }

    public function test_valid_values_raise_no_deprecation(): void
    {
        $deprecations = [];
        $previous = error_reporting(E_ALL);
        set_error_handler(function (int $no, string $str) use (&$deprecations): bool {
            $deprecations[] = $str;

            return true;
        });

        try {
            (new InvoiceType)->setInvoice('Simplified')->setInvoiceType('Invoice');
        } finally {
            restore_error_handler();
            error_reporting($previous);
        }

        $this->assertSame([], $deprecations);
    }
}
