<?php
namespace Einvoicing\Writer;

use Einvoicing\Invoice\Invoice;

abstract class AbstractWriter {
    const ALGORITHM_SHA1   = "sha1";
    const ALGORITHM_SHA224 = "sha224";
    const ALGORITHM_SHA256 = "sha256";
    const ALGORITHM_SHA384 = "sha384";
    const ALGORITHM_SHA512 = "sha512";

    protected $publicKey = null;
    protected $privateKey = null;
    protected $digestAlgorithm = self::ALGORITHM_SHA256;
    protected $signingTime = null;

    /**
     * Set signing certificate
     * @param  string      $pkcsPath   PKCS#12 certificate store path
     * @param  string|null $passphrase Certificate store passphrase
     * @return self                    Writer instance
     * @throws \InvalidArgumentException if failed to parse certificate store
     */
    public function setSigningCertificate(string $pkcsPath, ?string $passphrase=null): self {
        if (!is_file($pkcsPath)) {
            throw new \InvalidArgumentException('Invalid certificate store path');
        }
        if (!openssl_pkcs12_read(file_get_contents($pkcsPath), $certs, $passphrase)) {
            throw new \InvalidArgumentException('Failed to parse certificate store');
        }
        $this->publicKey = openssl_x509_read($certs['cert']);
        $this->privateKey = openssl_pkey_get_private($certs['pkey']);
        return $this;
    }


    /**
     * Set signing keys
     * @param  resource|string $publicKey  OpenSSL public key or path to PEM file
     * @param  resource|string $privateKey OpenSSL private key or path to PEM file
     * @param  string|null     $passphrase Private key passphrase (in case of path to PEM file)
     * @return self                        Writer instance
     * @throws \InvalidArgumentException if failed to parse key pair
     */
    public function setSigningKeys($publicKey, $privateKey, ?string $passphrase=null): self {
        // Parse public key
        if (is_resource($publicKey)) {
            $this->publicKey = $publicKey;
        } elseif (is_file($publicKey)) {
            $this->publicKey = openssl_x509_read(file_get_contents($publicKey));
            if ($this->publicKey === false) {
                throw new \InvalidArgumentException('Failed to parse public key');
            }
        } else {
            throw new \InvalidArgumentException('Invalid public key path');
        }

        // Parse private key
        if (is_resource($privateKey)) {
            $this->privateKey = $privateKey;
        } elseif (is_file($privateKey)) {
            $this->privateKey = openssl_pkey_get_private(file_get_contents($privateKey), $passphrase);
            if ($this->privateKey === false) {
                throw new \InvalidArgumentException('Failed to parse private key');
            }
        } else {
            throw new \InvalidArgumentException('Invalid private key path');
        }

        return $this;
    }


    /**
     * Set digest algorithm
     * @param  string $algorithm Digest algorithm name
     * @return self              Writer instance
     */
    public function setDigestAlgorithm(string $algorithm) {
        $this->digestAlgorithm = $algorithm;
        return $this;
    }


    /**
     * Set signing time
     * NOTE: if not specified, it will use the current time
     * @param  \DateTime|null $signingTime Signing time
     * @return self                        Writer instance
     */
    public function setSigningTime(?\DateTime $signingTime) {
        $this->signingTime = $signingTime;
        return $this;
    }


    /**
     * Can sign invoice
     * @return boolean Whether invoice can be signed by this writer or not
     */
    protected function canSign(): bool {
        return is_resource($this->publicKey) && is_resource($this->privateKey);
    }


    /**
     * Export invoice
     * @param  Invoice $invoice Invoice instance
     * @return string           Generated invoice contents
     * @throws ExportException if failed to export invoice
     */
    public abstract function export(Invoice $invoice): string;
}
