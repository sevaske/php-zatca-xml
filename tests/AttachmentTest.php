<?php

namespace Saleh7\Zatca\Tests;

use PHPUnit\Framework\TestCase;
use Sabre\Xml\Service;
use Saleh7\Zatca\Attachment;
use Saleh7\Zatca\GeneratorInvoice;
use Saleh7\Zatca\Mappers\InvoiceMapper;
use Saleh7\Zatca\Schema;
use Saleh7\Zatca\Tests\Fixtures\InvoiceDataFixture;

class AttachmentTest extends TestCase
{
    private static function serialize(Attachment $attachment): string
    {
        $service = new Service;
        $service->namespaceMap = ['urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2' => 'cbc'];

        return $service->write('root', $attachment);
    }

    private static function generate(array $data): string
    {
        return GeneratorInvoice::invoice((new InvoiceMapper)->mapToInvoice($data))->getXML();
    }

    public function test_filename_is_omitted_when_empty(): void
    {
        $xml = self::serialize((new Attachment)->setBase64Content('MA==', '', 'text/plain'));

        $this->assertStringContainsString('mimeCode="text/plain">MA==<', $xml);
        $this->assertStringNotContainsString('filename', $xml);
    }

    public function test_filename_is_written_when_set(): void
    {
        $xml = self::serialize((new Attachment)->setBase64Content('MA==', 'hash.txt', 'text/plain'));

        $this->assertStringContainsString('mimeCode="text/plain" filename="hash.txt">MA==<', $xml);
    }

    public function test_pih_is_written_without_filename_by_default(): void
    {
        $xml = self::generate(InvoiceDataFixture::simplified());

        $this->assertStringContainsString('<cbc:EmbeddedDocumentBinaryObject mimeCode="text/plain">MA==</cbc:EmbeddedDocumentBinaryObject>', $xml);
    }

    public function test_pih_file_name_can_be_set_in_the_mapper(): void
    {
        $data = InvoiceDataFixture::simplified();
        $data['additionalDocuments'][1]['attachment']['fileName'] = 'pih.txt';

        $this->assertStringContainsString('mimeCode="text/plain" filename="pih.txt">MA==<', self::generate($data));
    }

    public function test_attachment_without_filename_can_be_read(): void
    {
        $service = new Service;
        $service->elementMap = [Schema::CAC.'Attachment' => Attachment::class];

        /** @var Attachment $attachment */
        $attachment = $service->parse(
            '<cac:Attachment xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" '
            .'xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">'
            .'<cbc:EmbeddedDocumentBinaryObject mimeCode="text/plain">MA==</cbc:EmbeddedDocumentBinaryObject>'
            .'</cac:Attachment>'
        );

        $this->assertSame('MA==', $attachment->getBase64Content());
        $this->assertSame('', $attachment->getFileName());
        $this->assertSame('text/plain', $attachment->getMimeType());
    }
}
