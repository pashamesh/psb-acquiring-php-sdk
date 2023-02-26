<?php

namespace Pashamesh\PsbAcquiringPhpSdk\Interfaces;

interface SignerInterface
{
    /**
     * @param array<string,string|int>    $payload
     * @param array<array-key,string> $checksumAttributes
     */
    public function sign(array $payload, array $checksumAttributes): string;
}
