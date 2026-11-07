<?php

namespace Saleh7\Zatca\Tests;

use PHPUnit\Framework\TestCase;
use Saleh7\Zatca\Tag;
use Saleh7\Zatca\Tags\CertificateSignature;
use Saleh7\Zatca\Tags\InvoiceDate;
use Saleh7\Zatca\Tags\InvoiceDigitalSignature;
use Saleh7\Zatca\Tags\InvoiceHash;
use Saleh7\Zatca\Tags\InvoiceTaxAmount;
use Saleh7\Zatca\Tags\InvoiceTotalAmount;
use Saleh7\Zatca\Tags\PublicKey;
use Saleh7\Zatca\Tags\Seller;
use Saleh7\Zatca\Tags\TaxNumber;

/**
 * Test class for Tag and its derived classes.
 */
class TagTest extends TestCase
{
    /**
     * Data provider for tag classes.
     *
     * Returns an array of arrays containing:
     * - The class name of the Tag subclass.
     * - The expected tag number.
     * - A test value.
     *
     * @return array
     */
    public function tagProvider()
    {
        return [
            [Seller::class, 1, 'Seller Value'],
            [TaxNumber::class, 2, '1234567890'],
            [InvoiceDate::class, 3, '2025-02-12T17:00:00Z'],
            [InvoiceTotalAmount::class, 4, '100.00'],
            [InvoiceTaxAmount::class, 5, '15.00'],
            [InvoiceHash::class, 6, 'InvoiceHashValue'],
            [InvoiceDigitalSignature::class, 7, 'DigitalSignatureValue'],
            [PublicKey::class, 8, 'Public Key Value'],
            [CertificateSignature::class, 9, 'CertificateSignatureValue'],
        ];
    }

    /**
     * Test that each Tag subclass returns the correct values and TLV encoding.
     *
     * TLV encoding format is:
     *   - 1 byte: tag number (in hex)
     *   - 1 byte: length of value (in hex)
     *   - raw value (as is)
     *
     * @dataProvider tagProvider
     *
     * @param  string  $className  The Tag subclass name.
     * @param  int  $expectedTag  The expected tag number.
     * @param  string  $testValue  The value to test.
     */
    public function test_tag_encoding($className, $expectedTag, $testValue)
    {
        // Create an instance of the Tag subclass.
        $tagInstance = new $className($testValue);

        // Check that getTag returns the expected tag number.
        $this->assertEquals(
            $expectedTag,
            $tagInstance->getTag(),
            "Tag number for $className is incorrect."
        );

        // Check that getValue returns the provided test value.
        $this->assertEquals(
            $testValue,
            $tagInstance->getValue(),
            "Value for $className is incorrect."
        );

        // Check that getLength returns the correct length (in bytes) of the value.
        $this->assertEquals(
            strlen($testValue),
            $tagInstance->getLength(),
            "Length for $className is incorrect."
        );

        // Build the expected TLV encoded binary string.
        $expectedBinary = pack('H*', sprintf('%02X', $expectedTag))
                        .pack('H*', sprintf('%02X', strlen($testValue)))
                        .$testValue;
        // Check that __toString returns the correct TLV encoding.
        $this->assertEquals(
            $expectedBinary,
            $tagInstance->__toString(),
            "TLV encoding for $className does not match the expected output."
        );
    }

    /**
     * Test the __toString method of the base Tag class directly.
     *
     * This verifies that a Tag created directly (not via a subclass)
     * produces the correct TLV encoded string.
     */
    public function test_base_tag_to_string()
    {
        $tagNumber = 10;
        $value = 'TestValue';

        // Create a new Tag instance.
        $tag = new Tag($tagNumber, $value);

        // Verify that getTag, getValue, and getLength return correct values.
        $this->assertEquals($tagNumber, $tag->getTag(), 'Base Tag: tag number mismatch.');
        $this->assertEquals($value, $tag->getValue(), 'Base Tag: value mismatch.');
        $this->assertEquals(strlen($value), $tag->getLength(), 'Base Tag: length mismatch.');

        // Build the expected TLV encoded string.
        $expectedBinary = pack('H*', sprintf('%02X', $tagNumber))
                        .pack('H*', sprintf('%02X', strlen($value)))
                        .$value;

        // Verify that __toString returns the expected binary string.
        $this->assertEquals($expectedBinary, $tag->__toString(), 'Base Tag: TLV encoding mismatch.');
    }

    public function test_value_of_255_bytes_is_encoded_with_single_length_byte(): void
    {
        $value = str_repeat('a', 255);
        $encoded = (string) new Seller($value);

        $this->assertSame("\x01\xFF".$value, $encoded);
    }

    public function test_value_longer_than_255_bytes_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('QR tag 1 value is 300 bytes long');

        new Seller(str_repeat('ب', 150)); // 150 Arabic letters = 300 bytes
    }

    public function test_tag_number_out_of_range_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Tag(256, 'value');
    }
}
