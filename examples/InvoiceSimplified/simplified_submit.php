<?php

require __DIR__.'/../../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Saleh7\Zatca\InvoiceSigner;
use Sevaske\ZatcaApi\Exceptions\ZatcaException;
use Sevaske\ZatcaApi\ZatcaAuth;
use Sevaske\ZatcaApi\ZatcaClient;

try {
    $simulationPath = __DIR__.'/output/simulation.json';
    $simulation = json_decode(file_get_contents($simulationPath), true);

    $httpClient = new Client;
    $factory = new HttpFactory;
    $client = new ZatcaClient(
        $httpClient,
        $factory, // RequestFactoryInterface
        $factory, // StreamFactoryInterface
        'simulation' // environment: sandbox | simulation | production
    );

    $client->setAuthToken(new ZatcaAuth(
        certificate: $simulation['certificate'],
        secret: $simulation['secret'])
    );

    /**
     * @var InvoiceSigner $signedInvoice
     */
    $response = $client->reportingInvoice(
        invoice: $signedInvoice->getInvoice(),
        invoiceHash: $signedInvoice->getHash(),
        uuid: 'generated uuid',
    );

    if (! $response->success()) {
        throw new ZatcaException('Failed request.', $response->errors());
    }

    print_r($response->toArray());
} catch (ZatcaException $e) {
    echo 'API Error: '.$e->getMessage()."\n";
    print_r($e->context());
} catch (\Exception $e) {
    echo 'Error: '.$e->getMessage();
}
