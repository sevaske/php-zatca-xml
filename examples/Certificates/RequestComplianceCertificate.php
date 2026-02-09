<?php

require __DIR__.'/../../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Sevaske\ZatcaApi\Exceptions\ZatcaException;
use Sevaske\ZatcaApi\ZatcaAuth;
use Sevaske\ZatcaApi\ZatcaClient;

try {
    $certificatePath = __DIR__.'/output/certificate.csr';
    $csr = file_get_contents($certificatePath);

    $httpClient = new Client;
    $factory = new HttpFactory;
    $client = new ZatcaClient(
        $httpClient,
        $factory, // RequestFactoryInterface
        $factory, // StreamFactoryInterface
        'simulation' // environment: sandbox | simulation | production
    );
    $response = $client->complianceCertificate(csr: $csr, otp: '123123');
    $credentials = [
        'requestId' => $response->requestId(),
        'certificate' => $response->certificate(),
        'secret' => $response->secret(),
    ];

    print_r($credentials);

    $outputFile = __DIR__.'/output/simulation.json';
    file_put_contents($outputFile, json_encode($credentials, JSON_PRETTY_PRINT));

    echo "\nCertificate data saved to {$outputFile}\n";

    // to make authorized requests
    $authToken = new ZatcaAuth($response->certificate(), $response->secret());
    $client->setAuthToken($authToken);

} catch (ZatcaException $e) {
    echo 'API Error: '.$e->getMessage()."\n";
    print_r($e->context());
} catch (\Exception $e) {
    echo 'Error: '.$e->getMessage();
}
