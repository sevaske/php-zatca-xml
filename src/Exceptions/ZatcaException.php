<?php

namespace Saleh7\Zatca\Exceptions;

use Throwable;

/**
 * Class ZatcaException
 *
 * Base exception class for ZATCA-related errors.
 */
class ZatcaException extends \Exception implements ZatcaExceptionInterface
{
    protected string $defaultMessage = 'An error occurred.';

    /**
     * @var array<string, mixed>
     */
    protected array $context = [];

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(?string $message = null, array $context = [], int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message ?? $this->getDefaultMessage(), $code, $previous);

        $this->context = $context;
    }

    /**
     * Adds context data to the exception.
     *
     * @param  array<string, mixed>  $context
     */
    public function withContext(array $context): static
    {
        $this->context = array_merge($this->context, $context);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }

    protected function getDefaultMessage(): string
    {
        return $this->defaultMessage;
    }
}
