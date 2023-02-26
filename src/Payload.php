<?php

namespace Pashamesh\PsbAcquiringPhpSdk;

class Payload
{
    public ?string $amount = null;
    public string $currency = 'RUB';
    public ?string $order = null;
    public ?string $desc = null;
    public ?string $terminal = null;
    public int $trtype = 0;
    public ?string $merch_name = null;
    public ?string $merchant = null;
    public ?string $email = null;
    public ?string $cardholder_notify = null;
    public ?string $merchant_email = null;
    public ?string $merchant_notify = null;
    public ?string $mk_token = null;
    public ?string $timestamp = null;
    public ?string $nonce = null;
    public ?string $backref = null;
    public ?string $notify_url = null;
    public ?string $org_amount = null;
    public ?string $rrn = null;
    public ?string $int_ref = null;
    public ?string $recur_freq = null;
    public ?string $recur_exp = null;
    public ?string $recur_ref = null;
    public ?string $merch_token_id = null;
    public ?string $date_till = null;

    public ?string $p_sign = null;

    /**
     * @return array<string,string|int>
     */
    public function toArray(int $case = CASE_UPPER): array
    {
        return array_change_key_case(
            array_filter(get_object_vars($this), fn ($value) => !is_null($value)),
            $case
        );
    }
}
