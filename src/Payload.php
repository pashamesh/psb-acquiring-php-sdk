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

    public ?int $result = null;
    public ?string $rc = null;
    public ?string $rctext = null;
    public ?string $authcode = null;
    public ?string $token_id = null;
    public ?string $name = null;
    public ?string $card = null;
    public ?string $channel = null;
    public ?string $target_token_id = null;
    public ?string $trx_id = null;
    public ?string $addinfo = null;
    public ?string $ref = null;

    public ?string $p_sign = null;

    /**
     * @return array<string,string|int>
     */
    public function toArray(int $case = CASE_UPPER): array
    {
        /** @psalm-var array<string,string|int> $result */
        $result = array_change_key_case(
            array_filter(
                get_object_vars($this),
                fn ($value) => !is_null($value)
            ),
            $case
        );

        return $result;
    }

    /**
     * @param array<string,string|int> $attributes
     */
    public static function fromArray(array $attributes): self
    {
        $payload = new Payload();

        $attributes = array_change_key_case($attributes);

        foreach ($attributes as $attribute => $value) {
            if (!property_exists($payload, $attribute)) {
                continue;
            }

            $payload->{$attribute} = $value;
        }

        return $payload;
    }

    public function isOperationApproved(): bool
    {
        return $this->result === 0 && $this->rc === '00';
    }
}
