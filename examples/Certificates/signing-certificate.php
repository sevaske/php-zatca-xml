<?php

/**
 * Signing certificate for the invoice examples.
 *
 * By default the examples sign with the library's own self-signed test certificate (test/), which is only
 * good for trying the library offline: ZATCA does not accept it.
 *
 * To sign with another certificate, point these environment variables to files:
 *   ZATCA_CERT         certificate, base64 DER (the binarySecurityToken of a CSID, decoded once) or PEM
 *   ZATCA_PRIVATE_KEY  private key, base64 DER or PEM
 *   ZATCA_SECRET       secret of the CSID (optional)
 *
 * For example, the sample certificate of the ZATCA SDK (Data/Certificates/cert.pem and
 * ec-secp256k1-priv-key.pem) to validate the signed documents with the SDK.
 * For documents sent to ZATCA, the seller VAT number must match the certificate.
 */

use Saleh7\Zatca\Helpers\Certificate;

$readCertificateFile = static function (string $variable, string $default): string {
    $path = getenv($variable) ?: $default;
    $contents = is_file($path) ? file_get_contents($path) : false;

    if ($contents === false) {
        throw new RuntimeException("Cannot read the file '{$path}' ({$variable}).");
    }

    return trim($contents);
};

return new Certificate(
    $readCertificateFile('ZATCA_CERT', __DIR__.'/test/certificate.txt'),
    $readCertificateFile('ZATCA_PRIVATE_KEY', __DIR__.'/test/private-key.txt'),
    getenv('ZATCA_SECRET') ?: 'secret'
);
