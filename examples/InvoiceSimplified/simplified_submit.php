<?php

/**
 * Reports a signed simplified invoice to ZATCA (within 24 hours of issuing it).
 *
 * Run simplified_invoice.php first: it saves the signed invoice to output/Simplified_Invoice_Signed.xml.
 * Requires sevaske/zatca-api 3.x: composer require sevaske/zatca-api
 */

require __DIR__.'/../../vendor/autoload.php';

use Sevaske\ZatcaApi\Client;
use Sevaske\ZatcaApi\Credentials\ProductionCsid;
use Sevaske\ZatcaApi\Exception\ZatcaException;
use Sevaske\ZatcaApi\Invoice\SignedInvoice;

try {
    $productionCsid = ProductionCsid::fromArray(
        json_decode(file_get_contents(__DIR__.'/../Certificates/output/production-csid.json'), true)
    );

    // UUID read from the XML, invoice hash computed the same way ZATCA does
    $invoice = SignedInvoice::fromXml(file_get_contents('output/Simplified_Invoice_Signed.xml'));

    $result = Client::sandbox()->invoices($productionCsid)->report($invoice);

    if ($result->isReported()) {
        echo 'Reported'.($result->hasWarnings() ? ' with warnings' : '').".\n";
    } elseif ($result->isAlreadyReported()) {
        echo "Already reported earlier.\n";
    } else {
        echo "Rejected:\n";
    }

    foreach ($result->getValidationResults()->getErrorMessages() as $error) {
        echo " - error {$error}\n";
    }

    foreach ($result->getValidationResults()->getWarningMessages() as $warning) {
        echo " - warning {$warning}\n";
    }
} catch (ZatcaException $e) {
    echo 'Error: '.$e->getMessage()."\n";
}
