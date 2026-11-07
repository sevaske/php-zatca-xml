<?php

namespace Saleh7\Zatca;

use InvalidArgumentException;

class Tag
{
    /**
     * ZATCA QR TLV encodes the tag number and the value length in one byte each.
     */
    public const MAX_BYTE = 255;

    protected int $tag;

    protected string $value;

    /**
     * @throws InvalidArgumentException If the tag number or the value length does not fit into one byte.
     */
    public function __construct($tag, $value)
    {
        $this->tag = $tag;
        $this->value = $value;

        if ($this->tag < 1 || $this->tag > self::MAX_BYTE) {
            throw new InvalidArgumentException(sprintf('QR tag number must be between 1 and %d, %d given.', self::MAX_BYTE, $this->tag));
        }

        if ($this->getLength() > self::MAX_BYTE) {
            throw new InvalidArgumentException(sprintf(
                'QR tag %d value is %d bytes long; the TLV format allows at most %d bytes.',
                $this->tag,
                $this->getLength(),
                self::MAX_BYTE
            ));
        }
    }

    public function getTag(): int
    {
        return $this->tag;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * its important to get the number of bytes of a string instated of number of characters
     */
    public function getLength(): int
    {
        return strlen($this->value);
    }

    /**
     * @return string Returns a string representing the encoded TLV data structure.
     */
    public function __toString()
    {
        $value = (string) $this->getValue();

        return $this->toHex($this->getTag()).$this->toHex($this->getLength()).($value);
    }

    /**
     * To convert the string value to hex.
     */
    protected function toHex($value): string
    {
        return pack('H*', sprintf('%02X', $value));
    }
}
