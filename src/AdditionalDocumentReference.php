<?php

namespace Saleh7\Zatca;

use Sabre\Xml\Reader;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlDeserializable;
use Sabre\Xml\XmlSerializable;

use function Sabre\Xml\Deserializer\keyValue;

class AdditionalDocumentReference implements XmlDeserializable, XmlSerializable
{
    private $id;

    private $UUID;

    private $documentType;

    private $documentTypeCode;

    private $documentDescription;

    private $attachment;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getUUID(): ?string
    {
        return $this->UUID;
    }

    public function setUUID(?string $UUID): static
    {
        $this->UUID = $UUID;

        return $this;
    }

    public function getDocumentType(): ?string
    {
        return $this->documentType;
    }

    public function setDocumentType(?string $documentType): static
    {
        $this->documentType = $documentType;

        return $this;
    }

    public function getDocumentTypeCode(): ?int
    {
        return $this->documentTypeCode;
    }

    public function setDocumentTypeCode(?int $documentTypeCode): static
    {
        $this->documentTypeCode = $documentTypeCode;

        return $this;
    }

    public function getDocumentDescription(): ?string
    {
        return $this->documentDescription;
    }

    public function setDocumentDescription(?string $documentDescription): static
    {
        $this->documentDescription = $documentDescription;

        return $this;
    }

    public function getAttachment(): ?Attachment
    {
        return $this->attachment;
    }

    public function setAttachment(?Attachment $attachment): static
    {
        $this->attachment = $attachment;

        return $this;
    }

    /**
     * The xmlSerialize method is called during xml writing.
     */
    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([Schema::CBC.'ID' => $this->id]);
        if ($this->UUID !== null) {
            $writer->write([
                Schema::CBC.'UUID' => $this->UUID,
            ]);
        }
        if ($this->documentTypeCode !== null) {
            $writer->write([
                Schema::CBC.'DocumentTypeCode' => $this->documentTypeCode,
            ]);
        } elseif ($this->documentType !== null) {
            $writer->write([
                Schema::CBC.'DocumentType' => $this->documentType,
            ]);
        }

        if ($this->documentDescription !== null) {
            $writer->write([
                Schema::CBC.'DocumentDescription' => $this->documentDescription,
            ]);
        }

        if ($this->attachment !== null) {
            $writer->write([
                Schema::CAC.'Attachment' => $this->attachment,
            ]);
        }
    }

    /**
     * The xmlDeserialize method is called during xml reading.
     */
    public static function xmlDeserialize(Reader $reader): self
    {
        $keyValues = keyValue($reader);

        return (new self)
            ->setId($keyValues[Schema::CBC.'ID'] ?? null)
            ->setDocumentType($keyValues[Schema::CBC.'DocumentType'] ?? null)
            ->setDocumentTypeCode($keyValues[Schema::CBC.'DocumentTypeCode'] ?? null)
            ->setDocumentDescription($keyValues[Schema::CBC.'DocumentDescription'] ?? null)
            ->setAttachment($keyValues[Schema::CAC.'Attachment'] ?? null);
    }
}
