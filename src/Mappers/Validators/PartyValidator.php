<?php

namespace Saleh7\Zatca\Mappers\Validators;

use InvalidArgumentException;

/**
 * Class PartyValidator
 *
 * Base validator for party (supplier/customer) data validation.
 * Provides common validation methods for address and identification.
 */
abstract class PartyValidator
{
    /**
     * Validate party address.
     *
     * @param  array  $data  Party data containing address information.
     * @param  string  $partyType  The type of party (e.g., 'Supplier', 'Customer') for error messages.
     *
     * @throws InvalidArgumentException if validation fails.
     */
    protected function validateAddress(array $data, string $partyType): void
    {
        if (empty($data['address'])) {
            throw new InvalidArgumentException("{$partyType} address is required.");
        }

        $addressRequired = [
            'street' => 'Street',
            'buildingNumber' => 'Building Number',
            'city' => 'City',
            'postalZone' => 'Postal Zone',
            'country' => 'Country',
        ];

        foreach ($addressRequired as $field => $friendlyName) {
            if (empty($data['address'][$field])) {
                throw new InvalidArgumentException(
                    "The field '{$partyType} Address {$friendlyName}' is required and cannot be empty."
                );
            }
        }
    }

    /**
     * Validate party identification fields.
     *
     * If identificationId is provided, identificationType must also be present.
     *
     * @param  array  $data  Party data containing identification information.
     * @param  string  $partyType  The type of party (e.g., 'Supplier', 'Customer') for error messages.
     *
     * @throws InvalidArgumentException if validation fails.
     */
    protected function validateIdentification(array $data, string $partyType): void
    {
        if (isset($data['identificationId']) && !empty($data['identificationId'])) {
            if (empty($data['identificationType'])) {
                throw new InvalidArgumentException(
                    "{$partyType} identificationType is required when identificationId is provided."
                );
            }
        }
    }
}
