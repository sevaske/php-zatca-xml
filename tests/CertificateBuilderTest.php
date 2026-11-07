<?php

namespace Saleh7\Zatca\Tests;

use PHPUnit\Framework\TestCase;
use Saleh7\Zatca\CertificateBuilder;
use Saleh7\Zatca\Exceptions\CertificateBuilderException;

final class CertificateBuilderTest extends TestCase
{
    /**
     * Test that CertificateBuilder generates a CSR and a private key.
     */
    public function test_generate_csr_and_private_key(): void
    {
        $builder = (new CertificateBuilder)
            ->setOrganizationIdentifier('312345678901233')
            ->setSerialNumber('Saleh', '1n', 'SME00023')
            ->setCommonName('My Organization')
            ->setOrganizationName('My Company')
            ->setOrganizationalUnitName('IT Department')
            ->setAddress('Riyadh 1234 Street')
            ->setInvoiceType(1100)
            ->setProduction(true)
            ->setBusinessCategory('Technology')
            ->generate();

        $this->assertStringContainsString('BEGIN CERTIFICATE REQUEST', $builder->getCsr());
        $this->assertMatchesRegularExpression('/BEGIN (EC )?PRIVATE KEY/', $builder->getPrivateKey());
    }

    /**
     * Test that missing a required parameter throws an exception.
     */
    public function test_missing_required_parameter(): void
    {
        $this->expectException(CertificateBuilderException::class);
        $this->expectExceptionMessage('Missing required parameter: businessCategory');

        // Create CertificateBuilder without setting businessCategory.
        $builder = new CertificateBuilder;
        $builder->setOrganizationIdentifier('312345678901233')
            ->setSerialNumber('Saleh', '1n', 'SME00023')
            ->setCommonName('My Organization')
            ->setOrganizationName('My Company')
            ->setOrganizationalUnitName('IT Department')
            ->setAddress('Riyadh 1234 Street')
            ->setInvoiceType(1100)
            ->setProduction(true);
        // Missing setBusinessCategory

        // Calling generate() should throw an exception.
        $builder->generate();
    }
}
