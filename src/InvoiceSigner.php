<?php

namespace Saleh7\Zatca;

use DOMException;
use Saleh7\Zatca\Helpers\Certificate;
use Saleh7\Zatca\Helpers\InvoiceExtension;
use Saleh7\Zatca\Helpers\InvoiceSignatureBuilder;
use Saleh7\Zatca\Helpers\QRCodeGenerator;

class InvoiceSigner
{
    private mixed $signedInvoice;  // Signed invoice XML string

    private string $hash;           // Invoice hash (base64 encoded)

    private string $qrCode;         // QR Code (base64 encoded)

    private Certificate $certificate;    // Certificate used for signing

    private mixed $digitalSignature; // Digital signature (base64 encoded)

    // Private constructor to force usage of signInvoice method
    private function __construct() {}

    /**
     * Signs the invoice XML and returns an InvoiceSigner object.
     *
     * @param  string  $xmlInvoice  Invoice XML as a string
     * @param  Certificate  $certificate  Certificate for signing
     *
     * @throws DOMException
     */
    public static function signInvoice(string $xmlInvoice, Certificate $certificate): self
    {
        $instance = new self;
        $instance->certificate = $certificate;

        // Convert XML string to DOM
        $xmlDom = InvoiceExtension::fromString($xmlInvoice);

        // Remove unwanted tags per guidelines
        $xmlDom->removeByXpath('ext:UBLExtensions');
        $xmlDom->removeByXpath('cac:Signature');
        $xmlDom->removeParentByXpath('cac:AdditionalDocumentReference/cbc:ID[. = "QR"]');

        // Compute hash using SHA-256
        $invoiceHashBinary = hash('sha256', $xmlDom->getElement()->C14N(false, false), true);
        $instance->hash = base64_encode($invoiceHashBinary);

        // Create digital signature using the private key
        $instance->digitalSignature = base64_encode(
            $certificate->getPrivateKey()->sign($invoiceHashBinary)
        );

        // Prepare UBL Extension with certificate, hash, and signature
        $ublExtension = (new InvoiceSignatureBuilder)
            ->setCertificate($certificate)
            ->setInvoiceDigest($instance->hash)
            ->setSignatureValue($instance->digitalSignature)
            ->buildSignatureXml();

        // Generate QR Code
        $instance->qrCode = QRCodeGenerator::createFromTags(
            $xmlDom->generateQrTagsArray($certificate, $instance->hash, $instance->digitalSignature)
        )->encodeBase64();

        // Insert UBL Extension and QR Code into the XML
        $signedInvoice = str_replace(
            [
                '<cbc:ProfileID>',
                '<cac:AccountingSupplierParty>',
            ],
            [
                '<ext:UBLExtensions>'.$ublExtension.'</ext:UBLExtensions>'.PHP_EOL.'    <cbc:ProfileID>',
                $instance->getQRNode($instance->qrCode).PHP_EOL.'    <cac:AccountingSupplierParty>',
            ],
            $xmlDom->toXml()
        );

        // Remove extra blank lines and save
        $instance->signedInvoice = preg_replace('/^[ \t]*[\r\n]+/m', '', $signedInvoice);

        return $instance;
    }

    /**
     * Get the signed XML string.
     */
    public function getXML(): string
    {
        return $this->signedInvoice;
    }

    /**
     * Returns the QR node string.
     */
    private function getQRNode(string $QRCode): string
    {
        return "<cac:AdditionalDocumentReference>
        <cbc:ID>QR</cbc:ID>
        <cac:Attachment>
            <cbc:EmbeddedDocumentBinaryObject mimeCode=\"text/plain\">$QRCode</cbc:EmbeddedDocumentBinaryObject>
        </cac:Attachment>
    </cac:AdditionalDocumentReference>
    <cac:Signature>
        <cbc:ID>urn:oasis:names:specification:ubl:signature:Invoice</cbc:ID>
        <cbc:SignatureMethod>urn:oasis:names:specification:ubl:dsig:enveloped:xades</cbc:SignatureMethod>
    </cac:Signature>";
    }

    /**
     * Get signed invoice XML.
     */
    public function getInvoice(): string
    {
        return $this->signedInvoice;
    }

    /**
     * Get invoice hash.
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * Get QR Code.
     */
    public function getQRCode(): string
    {
        return $this->qrCode;
    }

    /**
     * Get the certificate used for signing.
     */
    public function getCertificate(): Certificate
    {
        return $this->certificate;
    }
}
