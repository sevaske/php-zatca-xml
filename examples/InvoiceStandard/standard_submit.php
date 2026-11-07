<?php

/**
 * Clears a signed standard invoice with ZATCA before handing it over to the buyer.
 *
 * Run standard_invoice.php first: it saves the signed invoice to output/Standard_Invoice_Signed.xml.
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

    $invoice = SignedInvoice::fromXml(file_get_contents('output/Standard_Invoice_Signed.xml'));

    $result = Client::sandbox()->invoices($productionCsid)->clear($invoice);

    if ($result->isCleared()) {
        // Always store the cleared invoice: it is stamped by ZATCA and is the document for the buyer.
        file_put_contents('output/Standard_Invoice_Cleared.xml', $result->getClearedInvoice());
        echo 'Cleared'.($result->hasWarnings() ? ' with warnings' : '').", saved to output/Standard_Invoice_Cleared.xml\n";
    } elseif ($result->isClearanceDisabled()) {
        echo "Clearance is deactivated: report the invoice instead.\n";
    } else {
        echo "Rejected:\n";

        foreach ($result->getValidationResults()->getErrorMessages() as $error) {
            echo " - {$error}\n";
        }
    }
} catch (ZatcaException $e) {
    echo 'Error: '.$e->getMessage()."\n";
}
