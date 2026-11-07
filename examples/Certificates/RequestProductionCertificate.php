<?php

/**
 * Onboarding, step 3: request the production CSID once the compliance checks are passed.
 *
 * Requires sevaske/zatca-api 3.x: composer require sevaske/zatca-api:^3.0
 */

require __DIR__.'/../../vendor/autoload.php';

use Sevaske\ZatcaApi\Client;
use Sevaske\ZatcaApi\Credentials\ComplianceCsid;
use Sevaske\ZatcaApi\Exception\ApiException;
use Sevaske\ZatcaApi\Exception\ZatcaException;

try {
    $complianceCsid = ComplianceCsid::fromArray(
        json_decode(file_get_contents(__DIR__.'/output/compliance-csid.json'), true)
    );

    $zatca = Client::sandbox(); // Client::simulation() / Client::production()

    $productionCsid = $zatca->onboarding()
        ->requestProductionCsid($complianceCsid)
        ->toProductionCsid();

    // Keep it safe: it signs and authorizes real invoices.
    $outputFile = __DIR__.'/output/production-csid.json';
    file_put_contents($outputFile, json_encode($productionCsid->toArray(), JSON_PRETTY_PRINT));

    echo "Production CSID saved to {$outputFile}\n";
} catch (ApiException $e) {
    echo 'ZATCA error: '.$e->getMessage()."\n"; // e.g. "... [Missing-ComplianceSteps]"
} catch (ZatcaException $e) {
    echo 'Error: '.$e->getMessage()."\n";
}
