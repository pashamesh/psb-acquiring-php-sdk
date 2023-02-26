<?php

namespace Pashamesh\PsbAcquiringPhpSdk\Tests\Unit;

use Pashamesh\PsbAcquiringPhpSdk\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testFromArrayMinimal(): void
    {
        $config = Config::fromArray([
            'merchantName' => 'the-merchantName',
            'merchantNumber' => 'the-merchantNumber',
            'terminalNumber' => 'the-terminalNumber',
            'merchantEmail' => 'the-merchantEmail',
        ]);

        $this->assertMinimal($config);
    }

    public function testFromArrayFull(): void
    {
        $config = Config::fromArray([
            'component1' => 'the-component1',
            'component2' => 'the-component2',
            'merchantName' => 'the-merchantName',
            'merchantNumber' => 'the-merchantNumber',
            'terminalNumber' => 'the-terminalNumber',
            'merchantEmail' => 'the-merchantEmail',
            'merchantNotify' => true,
            'notifyUrl' => 'the-notifyUrl',
            'returnUrl' => 'the-returnUrl',
        ]);

        $this->assertFull($config);
    }

    public function testFromPsbArrayMinimal(): void
    {
        $config = Config::fromPsbArray([
            'merch_name' => 'the-merchantName',
            'merchant' => 'the-merchantNumber',
            'terminal' => 'the-terminalNumber',
            'merchant_notify_email' => 'the-merchantEmail',
        ]);

        $this->assertMinimal($config);
    }

    public function testFromPsbArrayFull(): void
    {
        $config = Config::fromPsbArray([
            'comp1' => 'the-component1',
            'comp2' => 'the-component2',
            'merch_name' => 'the-merchantName',
            'merchant' => 'the-merchantNumber',
            'terminal' => 'the-terminalNumber',
            'merchant_notify_email' => 'the-merchantEmail',
            'merchant_notify' => true,
            'notify_url' => 'the-notifyUrl',
            'backref' => 'the-returnUrl',
        ]);

        $this->assertFull($config);
    }

    private function assertMinimal(Config $config): void
    {
        $this->assertEquals('C50E41160302E0F5D6D59F1AA3925C45', $config->component1);
        $this->assertEquals('00000000000000000000000000000000', $config->component2);
        $this->assertEquals('https://test.3ds.payment.ru', $config->gatewayDomain);
        $this->assertEquals('the-merchantName', $config->merchantName);
        $this->assertEquals('the-merchantNumber', $config->merchantNumber);
        $this->assertEquals('the-terminalNumber', $config->terminalNumber);
        $this->assertFalse($config->merchantNotify);
        $this->assertEquals('the-merchantEmail', $config->merchantEmail);
        $this->assertNull($config->notifyUrl);
        $this->assertNull($config->returnUrl);
    }

    private function assertFull(Config $config): void
    {
        $this->assertEquals('the-component1', $config->component1);
        $this->assertEquals('the-component2', $config->component2);
        $this->assertEquals('https://3ds.payment.ru', $config->gatewayDomain);
        $this->assertEquals('the-merchantName', $config->merchantName);
        $this->assertEquals('the-merchantNumber', $config->merchantNumber);
        $this->assertEquals('the-terminalNumber', $config->terminalNumber);
        $this->assertTrue($config->merchantNotify);
        $this->assertEquals('the-merchantEmail', $config->merchantEmail);
        $this->assertEquals('the-notifyUrl', $config->notifyUrl);
        $this->assertEquals('the-returnUrl', $config->returnUrl);
    }
}
