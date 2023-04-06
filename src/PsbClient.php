<?php

namespace Pashamesh\PsbAcquiringPhpSdk;

use GuzzleHttp\Psr7\Request;
use LogicException;
use Pashamesh\PsbAcquiringPhpSdk\Interfaces\SignerInterface;
use Pashamesh\PsbAcquiringPhpSdk\Interfaces\TransactionType;
use GuzzleHttp\Client as Guzzle;

class PsbClient
{
    private Payload $payload;
    private Config $config;
    private SignerInterface $signer;
    /** @var \Closure():string $getTimestamp */
    private $getTimestamp;
    /** @var \Closure():string $getRandomHex */
    private $getRandomHex;
    private FormBuilder $formBuilder;
    private Guzzle $httpClient;

    /**
     * @param \Closure():string|null        $getTimestamp
     * @param \Closure():string|null        $getRandomHex
     */
    public function __construct(
        Config $config,
        ?SignerInterface $signer = null,
        ?callable $getTimestamp = null,
        ?callable $getRandomHex = null,
        ?FormBuilder $formBuilder = null,
        ?Guzzle $httpClient = null
    ) {
        $this->config = $config;
        $this->signer = $signer ?? new Signer(
            $config->component1,
            $config->component2
        );
        $this->getTimestamp = $getTimestamp ?? fn (): string => gmdate('YmdHis');
        $this->getRandomHex = $getRandomHex ?? fn (): string => bin2hex(random_bytes(16));
        $this->formBuilder = $formBuilder ?? new FormBuilder($config);
        $this->httpClient = $httpClient ?? new Guzzle();

        $this->reset();
    }

    public function getPayload(): Payload
    {
        return $this->payload;
    }

    private function reset(): void
    {
        $this->payload = new Payload();
    }

    public function getResponseSignature(): string
    {
        $attributes = [
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

        if (
            in_array($this->payload->trtype, [
                TransactionType::REFUND,
                TransactionType::COMPLETE_PREAUTHORIZATION,
                TransactionType::CANCEL_PREAUTHORIZATION,
            ], true)
        ) {
            $attributes = [
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
        }

        return $this->signer->sign(
            $this->payload->toArray(CASE_LOWER),
            $attributes
        );
    }

    /**
     * @param array<array-key,string>|null $attributes
     */
    public function signRequest(array $attributes = null): string
    {
        $attributes ??= [
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
        ];

        if (
            in_array($this->payload->trtype, [
                TransactionType::REFUND,
                TransactionType::COMPLETE_PREAUTHORIZATION,
                TransactionType::CANCEL_PREAUTHORIZATION,
            ], true)
        ) {
            $attributes = [
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
            ];
        }
        $this->payload->p_sign = $this->signer->sign(
            $this->payload->toArray(CASE_LOWER),
            $attributes
        );

        return $this->payload->p_sign;
    }

    private function fillConfigDefaults(): void
    {
        if (!in_array($this->payload->trtype, [
            TransactionType::REFUND,
            TransactionType::COMPLETE_PREAUTHORIZATION,
            TransactionType::CANCEL_PREAUTHORIZATION,
        ], true)
        ) {
            $this->payload->merchant = $this->config->merchantNumber;
            $this->payload->merch_name = $this->config->merchantName;
            $this->payload->backref ??= $this->config->returnUrl;
        }

        if ($this->config->merchantNotify) {
            $this->payload->merchant_email = $this->config->merchantEmail;
            $this->payload->merchant_notify = 'EMAIL';
        }
        $this->payload->terminal = $this->config->terminalNumber;
        $this->payload->notify_url ??= $this->config->notifyUrl;
        $this->payload->timestamp = ($this->getTimestamp)();
        $this->payload->nonce = ($this->getRandomHex)();
    }

    public function customer(string $email, bool $notify = false): self
    {
        $this->payload->email = $email;
        if ($email && $notify) {
            $this->payload->cardholder_notify = 'EMAIL';
        }

        return $this;
    }

    public function order(int $number, string $description): self
    {
        $this->payload->order = sprintf('%06d', $number);
        $this->payload->desc = $description;

        return $this;
    }

    public function transaction(
        int $retrievalReferenceNumber,
        string $internalReference,
        float $amount = null
    ): self {
        $this->payload->rrn = strval($retrievalReferenceNumber);
        $this->payload->int_ref = $internalReference;
        if ($amount) {
            $this->payload->org_amount = strval($amount);
        }

        return $this;
    }

    public function notifyUrl(string $url): self
    {
        $this->payload->notify_url = $url;

        return $this;
    }

    public function returnUrl(string $url): self
    {
        $this->payload->backref = $url;

        return $this;
    }

    public function preauthorize(float $amount, bool $saveCard = false): self
    {
        $this->payload->trtype = TransactionType::START_PREAUTHORIZATION;
        $this->payload->amount = strval($amount);
        if ($saveCard) {
            $this->payload->mk_token = 'MERCH';
            $this->payload->cardholder_notify = 'EMAIL';
        }

        return $this;
    }

    public function preauthorizeAndSaveCard(float $amount): self
    {
        return $this->preauthorize($amount, true);
    }

    public function preauthorizeUsingCard(float $amount, string $cardToken): self
    {
        $this->payload->trtype = TransactionType::START_PREAUTHORIZATION;
        $this->payload->amount = strval($amount);
        $this->payload->merch_token_id = $cardToken;

        return $this;
    }

    public function purchase(float $amount, bool $saveCard = false): self
    {
        $this->payload->trtype = TransactionType::PURCHASE;
        $this->payload->amount = strval($amount);
        if ($saveCard) {
            $this->payload->mk_token = 'MERCH';
            $this->payload->cardholder_notify = 'EMAIL';
        }

        return $this;
    }

    public function purchaseAndSaveCard(float $amount): self
    {
        return $this->purchase($amount, true);
    }

    public function purchaseUsingCard(float $amount, string $cardToken): self
    {
        $this->payload->trtype = TransactionType::PURCHASE;
        $this->payload->amount = strval($amount);
        $this->payload->merch_token_id = $cardToken;

        return $this;
    }

    public function registerRecurring(
        int $frequency,
        string $expirationDate
    ): self {
        $this->payload->recur_freq = strval($frequency);
        $this->payload->recur_exp = $expirationDate;

        return $this;
    }

    public function doRecurringPayment(
        float $amount,
        string $internalReference,
        int $recurringReference
    ): self {
        $this->payload->trtype = TransactionType::RECURRING_PURCHASE;
        $this->payload->amount = strval($amount);
        $this->payload->int_ref = $internalReference;
        $this->payload->recur_ref = strval($recurringReference);

        return $this;
    }

    public function checkCard(): self
    {
        $this->payload->trtype = TransactionType::VERIFY_CARD;
        $this->payload->amount = '0';

        return $this;
    }

    public function forgetCard(string $cardToken): self
    {
        $this->payload->trtype = TransactionType::FORGET_CARD;
        $this->payload->amount = '0';
        $this->payload->merch_token_id = $cardToken;

        return $this;
    }

    public function saveCard(): self
    {
        $this->payload->trtype = TransactionType::SAVE_CARD;
        $this->payload->amount = '0';
        $this->payload->cardholder_notify = 'EMAIL';

        return $this;
    }

    public function refund(float $amount): self
    {
        $this->payload->trtype = TransactionType::REFUND;
        $this->payload->amount = strval($amount);

        return $this;
    }

    public function cancelPreauthorization(float $amount): self
    {
        $this->payload->trtype = TransactionType::CANCEL_PREAUTHORIZATION;
        $this->payload->amount = strval($amount);

        return $this;
    }

    public function completePreauthorization(float $amount): self
    {
        $this->payload->trtype = TransactionType::COMPLETE_PREAUTHORIZATION;
        $this->payload->amount = strval($amount);

        return $this;
    }

    public function getLink(string $dateTill = null): ?string
    {
        if (
            empty($this->payload->trtype) ||
            (
                $this->payload->trtype !== TransactionType::PURCHASE &&
                $this->payload->trtype !== TransactionType::START_PREAUTHORIZATION
            )
        ) {
            throw new LogicException(
                'Link generation is only available for Purchase and Preauthorization.'
            );
        }
        $this->payload->date_till = $dateTill;

        $this->fillConfigDefaults();
        $this->signRequest([
            'amount',
            'currency',
            'terminal',
            'trtype',
            'backref',
            'order',
        ]);

        $response = $this->doRequest('/cgi-bin/payment_ref/generate_payment_ref');

        return $response->ref;
    }

    public function getForm(): string
    {
        $this->fillConfigDefaults();
        $this->signRequest();

        $fields = $this->payload->toArray();
        $this->reset();

        return $this->formBuilder->fromArray($fields);
    }

    public function sendForm(): void
    {
        echo $this->getForm();
    }

    public function sendRequest(): Payload
    {
        $this->fillConfigDefaults();
        $this->signRequest();

        return $this->doRequest('/cgi-bin/cgi_link');
    }

    private function doRequest(string $url): Payload
    {
        // TODO: Use request factory.
        $request = new Request(
            'POST',
            "{$this->config->gatewayDomain}{$url}",
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            http_build_query($this->payload->toArray())
        );

        $response = $this->httpClient->send($request);
        $this->reset();

        // TODO: Check response status.

        $content = $response->getBody()->getContents();
        /** @var array<string, int|string> $contentArray */
        $contentArray = (array)json_decode($content, true);

        return Payload::fromArray($contentArray);
    }

    /**
     * @param array<string,string|int> $attributes
     *
     * @throws \Exception
     */
    public function handleCallbackRequest(array $attributes): Payload
    {
        $this->payload = Payload::fromArray($attributes);

        if ($this->getResponseSignature() !== $this->payload->p_sign) {
            throw new InvalidSignatureException(
                'The signature is not matching payload!'
            );
        }

        return $this->payload;
    }
}
