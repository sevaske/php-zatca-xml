<?php

namespace Saleh7\Zatca;

use DateTime;
use InvalidArgumentException;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlDeserializable;
use Sabre\Xml\XmlSerializable;

use function Sabre\Xml\Deserializer\keyValue;

class DocumentReference implements XmlDeserializable, XmlSerializable
{
    private ?string $id = null;

    private ?DateTime $issueDate = null;

    private ?DateTime $issueTime = null;

    private ?int $documentTypeCode = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getIssueDate(): ?DateTime
    {
        return $this->issueDate;
    }

    public function setIssueDate(DateTime $issueDate): static
    {
        $this->issueDate = $issueDate;

        return $this;
    }

    public function getIssueTime(): ?DateTime
    {
        return $this->issueTime;
    }

    public function setIssueTime(DateTime $issueTime): static
    {
        $this->issueTime = $issueTime;

        return $this;
    }

    public function getDocumentTypeCode(): ?int
    {
        return $this->documentTypeCode;
    }

    public function setDocumentTypeCode(int $documentTypeCode): static
    {
        $this->documentTypeCode = $documentTypeCode;

        return $this;
    }

    /**
     * Validates required invoice data before XML serialization.
     *
     * @throws InvalidArgumentException if required data is missing.
     */
    public function validate(): void
    {
        if ($this->id === null) {
            throw new InvalidArgumentException('DocumentReference "ID" is required.');
        }
        if ($this->issueDate === null) {
            throw new InvalidArgumentException('DocumentReference "IssueDate" must be a valid DateTime instance.');
        }
        if ($this->issueTime === null) {
            throw new InvalidArgumentException('DocumentReference "IssueTime" must be a valid DateTime instance.');
        }
        if ($this->documentTypeCode === null) {
            throw new InvalidArgumentException('DocumentReference "DocumentTypeCode" is required.');
        }
        if (! in_array($this->documentTypeCode, InvoiceTypeCode::DOCUMENT_TYPE_CODES)) {
            throw new InvalidArgumentException(
                sprintf(
                    'DocumentTypeCode "%s" is not valid. Allowed values: %s',
                    $this->documentTypeCode,
                    implode(', ', InvoiceTypeCode::DOCUMENT_TYPE_CODES)
                )
            );
        }
    }

    /**
     * The xmlSerialize method is called during xml writing.
     *
     * @throws InvalidArgumentException if required data is missing.
     */
    public function xmlSerialize(Writer $writer): void
    {
        $this->validate();

        $writer->write([Schema::CBC.'ID' => $this->id]);
        $writer->write([Schema::CBC.'IssueDate' => $this->issueDate->format('Y-m-d')]);
        $writer->write([Schema::CBC.'IssueTime' => $this->issueTime->format('H:i:s')]);
        $writer->write([
            Schema::CBC.'DocumentTypeCode' => $this->documentTypeCode,
        ]);
    }

    /**
     * The xmlDeserialize method is called during xml reading.
     */
    public static function xmlDeserialize(Reader $reader): self
    {
        $keyValues = keyValue($reader);

        $instance = new self;

        $instance->setId($keyValues[Schema::CBC.'ID'] ?? null);
        if (! empty($keyValues[Schema::CBC.'IssueDate'])) {
            $instance->setIssueDate(new DateTime($keyValues[Schema::CBC.'IssueDate']));
        }
        if (! empty($keyValues[Schema::CBC.'IssueTime'])) {
            $instance->setIssueTime(new DateTime($keyValues[Schema::CBC.'IssueTime']));
        }
        $instance->setDocumentTypeCode($keyValues[Schema::CBC.'DocumentTypeCode'] ?? null);

        return $instance;
    }
}
