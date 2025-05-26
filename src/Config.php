<?php

namespace Pashamesh\PsbAcquiringPhpSdk;

final class Config
{
    /* @see comp1 */
    public string $component1 = 'C50E41160302E0F5D6D59F1AA3925C45';

    /* @see comp2 */
    public string $component2 = '00000000000000000000000000000000';

    public string $gatewayDomain = 'https://test.3ds.payment.ru';

    /* @see MERCH_NAME */
    public string $merchantName;

    /* @see MERCHANT */
    public string $merchantNumber;

    /* @see TERMINAL */
    public string $terminalNumber;

    /* @see MERCHANT_NOTIFY */
    public bool $merchantNotify;

    /* @see MERCHANT_NOTIFY_EMAIL */
    public ?string $merchantEmail;

    /* @see NOTIFY_URL */
    public ?string $notifyUrl = null;

    /* @see BACKREF */
    public ?string $returnUrl = null;

    private function __construct(
        ?string $component1,
        ?string $component2,
        string $merchantName,
        string $merchantNumber,
        string $terminalNumber,
        string $merchantEmail = null,
        bool $merchantNotify = false,
        string $notifyUrl = null,
        string $returnUrl = null
    ) {
        if (! is_null($component1) && ! is_null($component2)) {
            $this->component1 = $component1;
            $this->component2 = $component2;
            $this->gatewayDomain = 'https://3ds.payment.ru';
        }
        $this->merchantName = $merchantName;
        $this->merchantNumber = $merchantNumber;
        $this->terminalNumber = $terminalNumber;
        $this->merchantNotify = $merchantNotify;
        $this->merchantEmail = $merchantEmail;
        $this->notifyUrl = $notifyUrl;
        $this->returnUrl = $returnUrl;
    }

    /**
     * @param array<string,string> $parameters
     *
     */
    public static function fromArray(array $parameters): self
    {
        return new self(
            $parameters['component1'] ?? null,
            $parameters['component2'] ?? null,
            $parameters['merchantName'],
            $parameters['merchantNumber'],
            $parameters['terminalNumber'],
            $parameters['merchantEmail'],
            boolval($parameters['merchantNotify'] ?? false),
            $parameters['notifyUrl'] ?? null,
            $parameters['returnUrl'] ?? null
        );
    }

    /**
     * @param array<string,string> $parameters
     *
     */
    public static function fromPsbArray(array $parameters): self
    {
        $parameters = array_change_key_case($parameters);

        return new self(
            $parameters['comp1'] ?? null,
            $parameters['comp2'] ?? null,
            $parameters['merch_name'],
            $parameters['merchant'],
            $parameters['terminal'],
            $parameters['merchant_notify_email'],
            boolval($parameters['merchant_notify'] ?? false),
            $parameters['notify_url'] ?? null,
            $parameters['backref'] ?? null
        );
    }
}
