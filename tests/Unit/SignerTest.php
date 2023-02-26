<?php

namespace Pashamesh\PsbAcquiringPhpSdk\Tests\Unit;

use Pashamesh\PsbAcquiringPhpSdk\Signer;
use PHPUnit\Framework\TestCase;

class SignerTest extends TestCase
{
    private const COMPONENT1 = 'C50E41160302E0F5D6D59F1AA3925C45';
    private const COMPONENT2 = '00000000000000000000000000000000';

    /**
     * @dataProvider signProvider
     */
    public function testSign(
        string $expected,
        array $payload,
        array $checksumAttributes
    ): void {
        $signer = new Signer(self::COMPONENT1, self::COMPONENT2);

        $this->assertEquals(
            $expected,
            $signer->sign($payload, $checksumAttributes)
        );
    }

    public function signProvider(): array
    {
        return [
            'purchase' => [
                'F0C07D4DC0134AF60D63847AC2E69B7A10156355E83D8900E2A368DF79C09B44',
                [
                    'amount' => '300',
                    'currency' => 'RUB',
                    'order' => '620749153',
                    'desc' => 'Order #620749153',
                    'terminal' => '79036777',
                    'trtype' => 1,
                    'merch_name' => 'Test Shop',
                    'merchant' => '000599979036777',
                    'email' => 'cardholder@mail.test',
                    'cardholder_notify' => 'EMAIL',
                    'mk_token' => 'MERCH',
                    'timestamp' => '20230226155419',
                    'nonce' => '2837a5da0ea46afc89186ecace243bbe',
                    'backref' => 'https://some.domain/',
                    'notify_url' => 'https://some.domain/notify.php',
                ],
                [
                    'amount',
                    'currency',
                    'order',
                    'merch_name',
                    'merchant',
                    'terminal',
                    'email',
                    'trtype',
                    'timestamp',
                    'nonce',
                    'backref',
                ],
            ],
            'refund' => [
                'FF0160A7B28D9D39470E3FD3E7A1D4D70811B01175311CAC229AF806E4939E9A',
                [
                    'AMOUNT' => '300',
                    'CURRENCY' => 'RUB',
                    'ORDER' => '620749153',
                    'DESC' => 'Order #620749153',
                    'TERMINAL' => '79036777',
                    'TRTYPE' => 14,
                    'EMAIL' => 'cardholder@mail.test',
                    'TIMESTAMP' => '20230226155911',
                    'NONCE' => 'bb4c4e9d5f6eae72da56b63dbbf5f628',
                    'NOTIFY_URL' => 'https://some.domain/notify.php',
                    'ORG_AMOUNT' => '300',
                    'RRN' => '305191843295',
                    'INT_REF' => '0023767C24E89274',
                ],
                [
                    'ORDER',
                    'AMOUNT',
                    'CURRENCY',
                    'ORG_AMOUNT',
                    'RRN',
                    'INT_REF',
                    'TRTYPE',
                    'TERMINAL',
                    'BACKREF',
                    'EMAIL',
                    'TIMESTAMP',
                    'NONCE',
                ],
            ],
            'generate link' => [
                '961A976FD6378E72C80DEB1FA010FA7FDA218DB6BE79ECF52936C75763BE8D07',
                [
                    'AMOUNT' => '300',
                    'CURRENCY' => 'RUB',
                    'ORDER' => '620749153',
                    'DESC' => 'Order #620749153',
                    'TERMINAL' => '79036777',
                    'TRTYPE' => 12,
                    'MERCH_NAME' => 'Test Shop',
                    'MERCHANT' => '000599979036777',
                    'EMAIL' => 'cardholder@mail.test',
                    'TIMESTAMP' => '20230226160147',
                    'NONCE' => '1d7e21e1a51d360b15740a3c5b992d56',
                    'BACKREF' => 'https://some.domain/',
                    'NOTIFY_URL' => 'https://some.domain/notify.php',
                    'DATE_TILL' => '01.03.2023 03:30:00',
                ],
                [
                    'AMOUNT',
                    'CURRENCY',
                    'TERMINAL',
                    'TRTYPE',
                    'BACKREF',
                    'ORDER',
                ],
            ],
        ];
    }

    /** @dataProvider getSecretKeyProvider */
    public function testGetSecretKey(
        string $expected,
        string $component1,
        string $component2
    ): void {
        $this->assertEquals(
            $expected,
            (new Signer($component1, $component2))->getSecretKey()
        );
    }

    public function getSecretKeyProvider(): array
    {
        return [
            'test credentials (no packing)' => [
                self::COMPONENT1,
                self::COMPONENT1,
                self::COMPONENT2,
            ],
            'Packing to 0' => [
                '00000000000000000000000000000000',
                self::COMPONENT1,
                self::COMPONENT1,
            ],
        ];
    }
}
