<?php

namespace Pashamesh\PsbAcquiringPhpSdk\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use LogicException;
use Pashamesh\PsbAcquiringPhpSdk\Config;
use Pashamesh\PsbAcquiringPhpSdk\FormBuilder;
use Pashamesh\PsbAcquiringPhpSdk\Interfaces\SignerInterface;
use Pashamesh\PsbAcquiringPhpSdk\Interfaces\TransactionType;
use Pashamesh\PsbAcquiringPhpSdk\InvalidSignatureException;
use Pashamesh\PsbAcquiringPhpSdk\Payload;
use Pashamesh\PsbAcquiringPhpSdk\PsbClient;
use PHPUnit\Framework\TestCase;

class PsbClientTest extends TestCase
{
    private const CONFIG_ARRAY = [
        'merchantName' => 'the-merchantName',
        'merchantNumber' => 'the-merchantNumber',
        'terminalNumber' => 'the-terminalNumber',
        'merchantEmail' => 'the-merchantEmail',
        'notifyUrl' => 'the-notifyUrl',
    ];

    private PsbClient $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = new PsbClient(Config::fromArray(self::CONFIG_ARRAY));
    }

    public function testCustomer(): void
    {
        $email = 'some@email.tld';

        $this->assertInstanceOf(PsbClient::class, $this->client->customer($email));
        $this->assertEquals($email, $this->client->getPayload()->email);
        $this->assertNull($this->client->getPayload()->cardholder_notify);

        $this->client->customer($email, true);
        $this->assertEquals('EMAIL', $this->client->getPayload()->cardholder_notify);
    }

    public function testOrder(): void
    {
        $orderNumber = '123456';
        $description = 'Some description';

        $this->assertInstanceOf(
            PsbClient::class,
            $this->client->order($orderNumber, $description)
        );

        $payload = $this->client->getPayload();
        $this->assertEquals($orderNumber, $payload->order);
        $this->assertEquals($description, $payload->desc);
    }

    public function testTransaction(): void
    {
        $retrievalReferenceNumber = 123456;
        $internalReference = 'the-internal-reference';

        $this->assertInstanceOf(
            PsbClient::class,
            $this->client->transaction($retrievalReferenceNumber, $internalReference)
        );

        $payload = $this->client->getPayload();
        $this->assertEquals($retrievalReferenceNumber, $payload->rrn);
        $this->assertEquals($internalReference, $payload->int_ref);
        $this->assertNull($payload->org_amount);
    }

    public function testTransactionWithAmount(): void
    {
        $retrievalReferenceNumber = 123456;
        $internalReference = 'the-internal-reference';
        $originalAmount = 123.45;

        $this->client->transaction(
            $retrievalReferenceNumber,
            $internalReference,
            $originalAmount
        );

        $payload = $this->client->getPayload();
        $this->assertEquals($retrievalReferenceNumber, $payload->rrn);
        $this->assertEquals($internalReference, $payload->int_ref);
        $this->assertEquals($originalAmount, $payload->org_amount);
    }

    public function testNotifyUrl(): void
    {
        $url = 'the-notify-url';

        $this->assertInstanceOf(PsbClient::class, $this->client->notifyUrl($url));

        $this->assertEquals($url, $this->client->getPayload()->notify_url);
    }

    public function testReturnUrl(): void
    {
        $url = 'the-return-url';

        $this->assertInstanceOf(PsbClient::class, $this->client->returnUrl($url));

        $this->assertEquals($url, $this->client->getPayload()->backref);
    }

    public function testAdditionalInfo(): void
    {
        $info = 'Some additional info.';

        $this->assertInstanceOf(PsbClient::class, $this->client->additionalInfo($info));

        $this->assertEquals($info, $this->client->getPayload()->addinfo);
    }

    /**
     * @testWith [1, "purchase"]
     *           [12, "preauthorize"]
     */
    public function testPurchase(int $transactionType, string $method): void
    {
        $amount = 123.45;

        $this->assertInstanceOf(PsbClient::class, $this->client->{$method}($amount));

        $payload = $this->client->getPayload();
        $this->assertEquals($amount, $payload->amount);
        $this->assertEquals($transactionType, $payload->trtype);
        $this->assertNull($payload->mk_token);
        $this->assertNull($payload->cardholder_notify);
    }

    /**
     * @testWith [1, "purchaseAndSaveCard"]
     *           [12, "preauthorizeAndSaveCard"]
     */
    public function testPurchaseAndSaveCard(int $transactionType, string $method): void
    {
        $amount = 123.45;

        $this->assertInstanceOf(
            PsbClient::class,
            $this->client->{$method}($amount)
        );

        $payload = $this->client->getPayload();
        $this->assertEquals($amount, $payload->amount);
        $this->assertEquals($transactionType, $payload->trtype);
        $this->assertEquals('MERCH', $payload->mk_token);
        $this->assertEquals('EMAIL', $payload->cardholder_notify);
    }

    /**
     * @testWith [1, "purchaseUsingCard"]
     *           [12, "preauthorizeUsingCard"]
     */
    public function testPurchaseUsingCard(int $transactionType, string $method): void
    {
        $amount = 123.45;
        $cardToken = 'the-card-token';

        $this->assertInstanceOf(
            PsbClient::class,
            $this->client->{$method}($amount, $cardToken)
        );

        $payload = $this->client->getPayload();
        $this->assertEquals($amount, $payload->amount);
        $this->assertEquals($transactionType, $payload->trtype);
        $this->assertEquals($cardToken, $payload->merch_token_id);
    }

    public function testRegisterRecurring(): void
    {
        $frequency = 99;
        $expirationDate = '20231201';

        $this->assertInstanceOf(
            PsbClient::class,
            $this->client->registerRecurring($frequency, $expirationDate)
        );

        $payload = $this->client->getPayload();
        $this->assertEquals($frequency, $payload->recur_freq);
        $this->assertEquals($expirationDate, $payload->recur_exp);
    }

    public function testDoRecurringPayment(): void
    {
        $amount = 123.45;
        $internalReference = 'the-internal-recurring-reference';
        $recurringReference = 123456;

        $this->assertInstanceOf(
            PsbClient::class,
            $this->client->doRecurringPayment($amount, $internalReference, $recurringReference)
        );

        $payload = $this->client->getPayload();
        $this->assertEquals(TransactionType::RECURRING_PURCHASE, $payload->trtype);
        $this->assertEquals($amount, $payload->amount);
        $this->assertEquals($internalReference, $payload->int_ref);
        $this->assertEquals($recurringReference, $payload->recur_ref);
    }

    public function testCheckCard(): void
    {
        $this->assertInstanceOf(PsbClient::class, $this->client->checkCard());

        $payload = $this->client->getPayload();
        $this->assertEquals(TransactionType::VERIFY_CARD, $payload->trtype);
        $this->assertEquals(0, $payload->amount);
    }

    public function testForgetCard(): void
    {
        $cardToken = 'the-card-token';

        $this->assertInstanceOf(PsbClient::class, $this->client->forgetCard($cardToken));

        $payload = $this->client->getPayload();
        $this->assertEquals(TransactionType::FORGET_CARD, $payload->trtype);
        $this->assertEquals(0, $payload->amount);
        $this->assertEquals($cardToken, $payload->merch_token_id);
    }

    public function testSaveCard(): void
    {
        $this->assertInstanceOf(PsbClient::class, $this->client->saveCard());

        $payload = $this->client->getPayload();
        $this->assertEquals(TransactionType::SAVE_CARD, $payload->trtype);
        $this->assertEquals(0, $payload->amount);
        $this->assertEquals('EMAIL', $payload->cardholder_notify);
    }

    public function testRefund(): void
    {
        $amount = 123.45;

        $this->assertInstanceOf(PsbClient::class, $this->client->refund($amount));

        $payload = $this->client->getPayload();
        $this->assertEquals(TransactionType::REFUND, $payload->trtype);
        $this->assertEquals($amount, $payload->amount);
    }

    public function testCancelPreauthorization(): void
    {
        $amount = 123.45;

        $this->assertInstanceOf(
            PsbClient::class,
            $this->client->cancelPreauthorization($amount)
        );

        $payload = $this->client->getPayload();
        $this->assertEquals(TransactionType::CANCEL_PREAUTHORIZATION, $payload->trtype);
        $this->assertEquals($amount, $payload->amount);
    }

    public function testCompletePreauthorization(): void
    {
        $amount = 123.45;

        $this->assertInstanceOf(
            PsbClient::class,
            $this->client->completePreauthorization($amount)
        );

        $payload = $this->client->getPayload();
        $this->assertEquals(TransactionType::COMPLETE_PREAUTHORIZATION, $payload->trtype);
        $this->assertEquals($amount, $payload->amount);
    }

    public function testGetForm(): void
    {
        $amount = 123.45;
        $config = Config::fromArray(self::CONFIG_ARRAY);
        $timestamp = 'the-timestamp';
        $nonce = 'the-nonce';

        $expectedFormContent = 'the-form';

        $formBuilder = $this->createMock(FormBuilder::class);
        $formBuilder->expects($this->exactly(2))
            ->method('fromArray')
            ->with([
                'AMOUNT' => strval($amount),
                'CURRENCY' => 'RUB',
                'TERMINAL' => $config->terminalNumber,
                'TRTYPE' => strval(TransactionType::PURCHASE),
                'MERCH_NAME' => $config->merchantName,
                'MERCHANT' => $config->merchantNumber,
                'TIMESTAMP' => $timestamp,
                'NONCE' => $nonce,
                'NOTIFY_URL' => $config->notifyUrl,
                'P_SIGN' => '72C7434ABF2DE21685A58FBAF138BE3427865C2975960B3060917ADAA5B671C8',
            ])
            ->willReturn($expectedFormContent);

        $client = new PsbClient(
            $config,
            null,
            fn () => $timestamp,
            fn () => $nonce,
            $formBuilder
        );

        $this->assertEquals($expectedFormContent, $client->purchase($amount)->getForm());

        $this->expectOutputString($expectedFormContent);
        $client->purchase($amount)->sendForm();
    }

    public function testSendRequest(): void
    {
        $amount = 123.45;
        $config = Config::fromArray(self::CONFIG_ARRAY);
        $timestamp = 'the-timestamp';
        $nonce = 'the-nonce';

        $expectedRequestUrl = 'https://test.3ds.payment.ru/cgi-bin/cgi_link';
        $expectedRequestData = [
            'AMOUNT' => strval($amount),
            'CURRENCY' => 'RUB',
            'TERMINAL' => $config->terminalNumber,
            'TRTYPE' => strval(TransactionType::PURCHASE),
            'MERCH_NAME' => $config->merchantName,
            'MERCHANT' => $config->merchantNumber,
            'TIMESTAMP' => $timestamp,
            'NONCE' => $nonce,
            'NOTIFY_URL' => $config->notifyUrl,
            'P_SIGN' => '72C7434ABF2DE21685A58FBAF138BE3427865C2975960B3060917ADAA5B671C8',
        ];

        $history = [];
        $mock = new MockHandler([
            new Response(200, [], json_encode(['KEY' => 'value'])),
        ]);
        $handler = HandlerStack::create($mock);
        $handler->push(Middleware::history($history));
        $httpClient = new Client(['handler' => $handler]);

        $client = new PsbClient(
            $config,
            null,
            fn () => $timestamp,
            fn () => $nonce,
            null,
            $httpClient
        );

        $this->assertEquals(Payload::fromArray(['KEY' => 'value']), $client->purchase($amount)->sendRequest());
        /* @var Request $request */
        $request = $history[0]['request'];
        $this->assertEquals($expectedRequestUrl, (string)$request->getUri());
        $this->assertEquals($expectedRequestData, $this->getRequestData($request));
    }

    /**
     * @testWith [null]
     *           ["123"]
     */
    public function testGetLink(?string $dateTill): void
    {
        $url = 'the-payment-lint';
        $amount = 123.45;
        $config = Config::fromArray(self::CONFIG_ARRAY);
        $timestamp = 'the-timestamp';
        $nonce = 'the-nonce';

        $expectedRequestUrl = 'https://test.3ds.payment.ru/cgi-bin/payment_ref/generate_payment_ref';
        $expectedRequestData = [
            'AMOUNT' => strval($amount),
            'CURRENCY' => 'RUB',
            'TERMINAL' => $config->terminalNumber,
            'TRTYPE' => strval(TransactionType::PURCHASE),
            'MERCH_NAME' => $config->merchantName,
            'MERCHANT' => $config->merchantNumber,
            'TIMESTAMP' => $timestamp,
            'NONCE' => $nonce,
            'NOTIFY_URL' => $config->notifyUrl,
            'P_SIGN' => '74BF94D08518E54B25049AA7AD672995F43C9286BE131C8C72198F745849B0F6',
        ];
        if ($dateTill) {
            $expectedRequestData['DATE_TILL'] = $dateTill;
        }

        $history = [];
        $mock = new MockHandler([
            new Response(200, [], json_encode(['REF' => $url])),
        ]);
        $handler = HandlerStack::create($mock);
        $handler->push(Middleware::history($history));
        $httpClient = new Client(['handler' => $handler]);

        $client = new PsbClient(
            $config,
            null,
            fn () => $timestamp,
            fn () => $nonce,
            null,
            $httpClient
        );
        $client->purchase($amount);

        $this->assertEquals($url, $client->getLink($dateTill));
        /* @var Request $request */
        $request = $history[0]['request'];
        $this->assertEquals($expectedRequestUrl, (string) $request->getUri());
        $this->assertEquals($expectedRequestData, $this->getRequestData($request));
    }

    public function testGetLinkThrowsLogicException(): void
    {
        $this->expectExceptionObject(new LogicException(
            'Link generation is only available for Purchase and Preauthorization.'
        ));

        $this->client->refund(123.45);
        $this->client->getLink();
    }

    public function testGetStatus(): void
    {
        $url = 'the-payment-lint';
        $amount = 123.45;
        $config = Config::fromArray(self::CONFIG_ARRAY);
        $timestamp = 'the-timestamp';
        $nonce = 'the-nonce';

        $expectedRequestUrl = 'https://test.3ds.payment.ru/cgi-bin/check_operation/ecomm_check';
        $expectedRequestData = [
            'AMOUNT' => strval($amount),
            'CURRENCY' => 'RUB',
            'TERMINAL' => $config->terminalNumber,
            'TRTYPE' => strval(TransactionType::PURCHASE),
            'MERCH_NAME' => $config->merchantName,
            'MERCHANT' => $config->merchantNumber,
            'TIMESTAMP' => $timestamp,
            'NONCE' => $nonce,
            'NOTIFY_URL' => $config->notifyUrl,
            'P_SIGN' => '72C7434ABF2DE21685A58FBAF138BE3427865C2975960B3060917ADAA5B671C8',
        ];

        $history = [];
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                    "AMOUNT" => strval($amount),
                    "CURRENCY" => "RUB",
                    "MERCH_NAME" => "TEST RETAIL",
                    "TERMINAL" => $config->terminalNumber,
                    "TRTYPE" => "1",
                    "TIMESTAMP" => $timestamp,
                    "NONCE" => $nonce,
                    "RESULT" => "0",
                    "RC" => "00",
                    "RCTEXT" => "Approved",
                    "AUTHCODE" => "263763",
                    "RRN" => "432393334442",
                    "INT_REF" => "B5DEBAF106686C1F",
                    "P_SIGN" => "e7da0e098f5d5184489c8253599e53f595ff7e6b91567d7378922d17cbfafbac",
            ]))
        ]);
        $handler = HandlerStack::create($mock);
        $handler->push(Middleware::history($history));
        $httpClient = new Client(['handler' => $handler]);

        $client = new PsbClient(
            $config,
            null,
            fn () => $timestamp,
            fn () => $nonce,
            null,
            $httpClient
        );

        $payload = $client->purchase($amount)->getStatus();
        $this->assertTrue($payload->isOperationApproved());

        $request = $history[0]['request'];
        $this->assertEquals($expectedRequestUrl, (string) $request->getUri());
        $this->assertEquals($expectedRequestData, $this->getRequestData($request));
    }

    public function testGetStatusReturnsEmptyArray(): void
    {
        $url = 'the-payment-lint';
        $amount = 123.45;
        $config = Config::fromArray(self::CONFIG_ARRAY);
        $timestamp = 'the-timestamp';
        $nonce = 'the-nonce';

        $expectedRequestUrl = 'https://test.3ds.payment.ru/cgi-bin/check_operation/ecomm_check';
        $expectedRequestData = [
            'AMOUNT' => strval($amount),
            'CURRENCY' => 'RUB',
            'TERMINAL' => $config->terminalNumber,
            'TRTYPE' => strval(TransactionType::PURCHASE),
            'MERCH_NAME' => $config->merchantName,
            'MERCHANT' => $config->merchantNumber,
            'TIMESTAMP' => $timestamp,
            'NONCE' => $nonce,
            'NOTIFY_URL' => $config->notifyUrl,
            'P_SIGN' => '72C7434ABF2DE21685A58FBAF138BE3427865C2975960B3060917ADAA5B671C8',
        ];

        $history = [];
        $mock = new MockHandler([
            new Response(200, [], json_encode([]))
        ]);
        $handler = HandlerStack::create($mock);
        $handler->push(Middleware::history($history));
        $httpClient = new Client(['handler' => $handler]);
        $client = new PsbClient(
            $config,
            null,
            fn () => $timestamp,
            fn () => $nonce,
            null,
            $httpClient
        );

        $payload = $client->purchase($amount)->getStatus();
        $this->assertFalse($payload->isOperationApproved());

        $request = $history[0]['request'];
        $this->assertEquals($expectedRequestUrl, (string) $request->getUri());
        $this->assertEquals($expectedRequestData, $this->getRequestData($request));
    }

    private function getRequestData(Request $request): array
    {
        parse_str($request->getBody()->getContents(), $requestData);

        return $requestData;
    }

    public function testHandleCallbackRequest(): void
    {
        $requestAttributes = [
            'AMOUNT' => '300',
            'ORG_AMOUNT' => '',
            'CURRENCY' => 'RUB',
            'ORDER' => '620749153',
            'DESC' => 'Order #620749153',
            'MERCH_NAME' => 'Test Shop',
            'MERCHANT' => '000599979036777',
            'TERMINAL' => '79036777',
            'EMAIL' => 'cardholder@mail.test',
            'TRTYPE' => '1',
            'TIMESTAMP' => '20230317022004',
            'NONCE' => '4ca62d89dba536b1a96772b32bcc56d3',
            'BACKREF' => 'https://some.host/',
            'RESULT' => '0',
            'RC' => '00',
            'RCTEXT' => 'Approved',
            'AUTHCODE' => '279498',
            'RRN' => '307692025788',
            'INT_REF' => '82EB65226A4CBA3F',
            'P_SIGN' => 'some-signature',
            'NAME' => 'TEST CARDHOLDER',
            'CARD' => '5547XXXXXXXX4672',
            'CHANNEL' => '',
            'TOKEN_ID' => '',
            'TARGET_TOKEN_ID' => '',
            'TRX_ID' => '',
            'ADDINFO' => '',
        ];
        $checkAttributes = [
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
            'result',
            'rc',
            'rctext',
            'authcode',
            'rrn',
            'int_ref',
        ];

        $config = Config::fromArray(self::CONFIG_ARRAY);

        $signer = $this->createMock(SignerInterface::class);
        $signer->expects($this->once())
            ->method('sign')
            ->with(array_change_key_case($requestAttributes), $checkAttributes)
            ->willReturn('some-signature');

        $this->assertInstanceOf(
            Payload::class,
            (new PsbClient($config, $signer))->handleCallbackRequest($requestAttributes)
        );
    }

    public function testHandleCallbackRequestThrowsInvalidSignatureException(): void
    {
        $requestAttributes = [
            'CURRENCY' => 'RUB',
            'TRTYPE' => TransactionType::REFUND,
            'P_SIGN' => 'some-wrong-signature',
        ];
        $checkAttributes = [
            'order',
            'amount',
            'currency',
            'org_amount',
            'rrn',
            'int_ref',
            'trtype',
            'terminal',
            'backref',
            'email',
            'timestamp',
            'nonce',
            'result',
            'rc',
            'rctext',
        ];

        $config = Config::fromArray(self::CONFIG_ARRAY);

        $signer = $this->createMock(SignerInterface::class);
        $signer->expects($this->once())
            ->method('sign')
            ->with(array_change_key_case($requestAttributes), $checkAttributes)
            ->willReturn('some-signature');

        $this->expectException(InvalidSignatureException::class);
        (new PsbClient($config, $signer))->handleCallbackRequest($requestAttributes);
    }
}
