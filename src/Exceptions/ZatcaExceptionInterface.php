<?php

namespace Saleh7\Zatca\Exceptions;

/**
 * Implemented by every exception of this library that carries context.
 */
interface ZatcaExceptionInterface extends \Throwable
{
    /**
     * Additional data describing the error (paths, invalid values, ...).
     *
     * @return array<string, mixed>
     */
    public function context(): array;
}
