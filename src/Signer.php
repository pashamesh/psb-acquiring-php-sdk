<?php

namespace Pashamesh\PsbAcquiringPhpSdk;

use Pashamesh\PsbAcquiringPhpSdk\Interfaces\SignerInterface;

class Signer implements SignerInterface
{
    private string $component1;
    private string $component2;

    public function __construct(string $component1, string $component2)
    {
        $this->component1 = $component1;
        $this->component2 = $component2;
    }

    public function sign(array $payload, array $checksumAttributes): string
    {
        $checksum = '';

        foreach ($checksumAttributes as $param) {
            $value = !empty($payload[$param]) ? strval($payload[$param]) : null;
            if (is_null($value) || !strlen($value)) {
                $checksum .= '-';
                continue;
            }

            $checksum .= strlen($value) . $value;
        }

        return strtoupper(hash_hmac(
            'sha256',
            $checksum,
            pack('H*', $this->getSecretKey())
        ));
    }

    public function getSecretKey(): string
    {
        return strtoupper(implode(unpack(
            'H32',
            pack('H32', $this->component1) ^ pack('H32', $this->component2)
        )));
    }
}
