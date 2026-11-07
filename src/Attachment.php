<?php

namespace Saleh7\Zatca;

use Exception;
use InvalidArgumentException;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlDeserializable;
use Sabre\Xml\XmlSerializable;
use Saleh7\Zatca\Exceptions\ZatcaStorageException;

use function Sabre\Xml\Deserializer\mixedContent;

class Attachment implements XmlDeserializable, XmlSerializable
{
    private ?string $filePath = null;

    private ?string $externalReference = null;

    private ?string $base64Content = null;

    private string $fileName = '';

    private ?string $mimeType = null;

    /**
     * @throws Exception exception when the mime type cannot be determined
     */
    public function getFilePathMimeType(): string
    {
        if (($mime_type = mime_content_type($this->filePath)) !== false) {
            return $mime_type;
        }

        throw new Exception('Could not determine mime_type of '.$this->filePath);
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): static
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getExternalReference(): ?string
    {
        return $this->externalReference;
    }

    public function setExternalReference(string $externalReference): static
    {
        $this->externalReference = $externalReference;

        return $this;
    }

    public function getBase64Content(): ?string
    {
        return $this->base64Content;
    }

    /**
     * @param  string  $base64Content  Base64 encoded base64Content
     */
    public function setBase64Content(string $base64Content, string $fileName, ?string $mimeType): static
    {
        $this->base64Content = $base64Content;
        $this->fileName = $fileName;
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): static
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * The validate function that is called during xml writing to valid the data of the object.
     *
     *
     * @throws InvalidArgumentException An error with information about required data that is missing to write the XML
     */
    public function validate(): void
    {
        if ($this->filePath === null && $this->externalReference === null && $this->base64Content === null) {
            throw new InvalidArgumentException('Attachment must have a filePath, an externalReference, or a fileContent');
        }

        if ($this->base64Content !== null && $this->mimeType === null) {
            throw new InvalidArgumentException('Using base64Content, you need to define a mimeType by also using setFileMimeType');
        }

        if ($this->filePath !== null && ! file_exists($this->filePath)) {
            throw new InvalidArgumentException('Attachment at filePath does not exist');
        }
    }

    /**
     * The xmlSerialize method is called during xml writing.
     *
     * @throws ZatcaStorageException
     * @throws Exception
     */
    public function xmlSerialize(Writer $writer): void
    {
        $this->validate();

        if (! empty($this->filePath)) {
            $fileContents = base64_encode((new Storage)->get($this->filePath));
            $fileName = basename($this->filePath);
            $mimeType = $this->getFilePathMimeType();
        } else {
            $fileContents = $this->base64Content;
            $fileName = $this->fileName;
            $mimeType = $this->mimeType;
        }

        $writer->write([
            'name' => Schema::CBC.'EmbeddedDocumentBinaryObject',
            'value' => $fileContents,
            'attributes' => [
                'mimeCode' => $mimeType,
                'filename' => $fileName,
            ],
        ]);

        if ($this->externalReference) {
            $writer->writeElement(
                Schema::CAC.'ExternalReference',
                [Schema::CBC.'URI' => $this->externalReference]
            );
        }
    }

    /**
     * The xmlDeserialize method is called during xml reading.
     */
    public static function xmlDeserialize(Reader $reader): self
    {
        $mixedContent = mixedContent($reader);
        $embeddedDocumentBinaryObject = array_values(array_filter($mixedContent, fn ($element) => $element['name'] === Schema::CBC.'EmbeddedDocumentBinaryObject'))[0] ?? null;

        return (new self)
            ->setBase64Content(
                $embeddedDocumentBinaryObject['value'] ?? null,
                $embeddedDocumentBinaryObject['attributes']['filename'] ?? null,
                $embeddedDocumentBinaryObject['attributes']['mimeCode'] ?? null
            );
    }
}
