<?php

namespace Pashamesh\PsbAcquiringPhpSdk\Tests\Unit;

use Pashamesh\PsbAcquiringPhpSdk\Interfaces\TransactionType;
use Pashamesh\PsbAcquiringPhpSdk\Payload;
use PHPUnit\Framework\TestCase;

class PayloadTest extends TestCase
{
    /** @dataProvider payloadProvider */
    public function testToArray(Payload $payload, array $expectedLower): void
    {
        $this->assertEquals($expectedLower, $payload->toArray(CASE_LOWER));
        $this->assertEquals(array_change_key_case($expectedLower, CASE_UPPER), $payload->toArray());
    }

    public function payloadProvider(): array
    {
        $minimalPayload = new Payload();
        $minimalPayload->amount = '123.99';
        $minimalPayload->order = '123456';
        $minimalPayload->terminal = '234567';
        $minimalPayload->trtype = TransactionType::RECURRING_PURCHASE;
        $minimalPayload->email = 'customer@mail.test';
        $minimalPayload->cardholder_notify = 'TEST';
        $minimalPayload->timestamp = '20230226120000';
        $minimalPayload->nonce = 'abc123def456';
        $minimalPayload->notify_url = 'https://some.host/notify_url';
        $minimalPayload->p_sign = 'SOMESIGNATURE';

        $expectedMinimalArray = [
            'amount' => '123.99',
            'currency' => 'RUB',
            'notify_url' => 'https://some.host/notify_url',
            'order' => '123456',
            'terminal' => '234567',
            'trtype' => TransactionType::RECURRING_PURCHASE,
            'email' => 'customer@mail.test',
            'cardholder_notify' => 'TEST',
            'timestamp' => '20230226120000',
            'nonce' => 'abc123def456',
            'p_sign' => 'SOMESIGNATURE',
        ];

        $fullPayload = clone $minimalPayload;
        $fullPayload->desc = 'Some description';
        $fullPayload->merch_name = 'Some merchant';
        $fullPayload->merchant = '345678';
        $fullPayload->merchant_email = 'merchant@mail.test';
        $fullPayload->merchant_notify = 'TEST';
        $fullPayload->mk_token = 'TEST';
        $fullPayload->backref = 'https://some.host/backref';
        $fullPayload->org_amount = '120.11';
        $fullPayload->rrn = '456789';
        $fullPayload->int_ref = 'BCD234EFG567';
        $fullPayload->recur_freq = 99;
        $fullPayload->recur_exp = '20230326';
        $fullPayload->recur_ref = '567890';
        $fullPayload->merch_token_id = 'abc123';
        $fullPayload->date_till = '20230426';


        return [
            'minimal' => [
                $minimalPayload,
                $expectedMinimalArray,
            ],
            'full' => [
                $fullPayload,
                $expectedMinimalArray + [
                    'desc' => 'Some description',
                    'merch_name' => 'Some merchant',
                    'merchant' => '345678',
                    'merchant_email' => 'merchant@mail.test',
                    'merchant_notify' => 'TEST',
                    'mk_token' => 'TEST',
                    'backref' => 'https://some.host/backref',
                    'org_amount' => '120.11',
                    'rrn' => '456789',
                    'int_ref' => 'BCD234EFG567',
                    'recur_freq' => 99,
                    'recur_exp' => '20230326',
                    'recur_ref' => '567890',
                    'merch_token_id' => 'abc123',
                    'date_till' => '20230426',
                ],
            ],
        ];
    }

    /** @dataProvider payloadProvider */
    public function testFromArray(Payload $expected, array $attributes): void
    {
        $this->assertEquals($expected, Payload::fromArray($attributes));
        $this->assertEquals(
            $expected,
            Payload::fromArray(array_change_key_case($attributes, CASE_UPPER))
        );
    }

    public function testFromArrayIgnoresNotExistingAttributes(): void
    {
        $this->assertEquals(Payload::fromArray([]), Payload::fromArray(['foo' => 'bar']));
    }

    /** @dataProvider isOperationApprovedProvider */
    public function testIsOperationApproved(bool $expected, array $data): void
    {
        $this->assertEquals(
            $expected,
            (Payload::fromArray($data))->isOperationApproved()
        );
    }

    public function isOperationApprovedProvider(): array
    {
        return [
            'Empty' => [false, []],
            'Success' => [true, ['RESULT' => 0, 'RC' => '00']],
            'Duplicate transaction' => [false, ['RESULT' => 3, 'RC' => '-21']],
        ];
    }
}
