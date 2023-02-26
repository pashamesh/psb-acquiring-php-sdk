<?php

namespace Pashamesh\PsbAcquiringPhpSdk\Interfaces;

interface TransactionType
{
    public const PURCHASE = 1;

    public const REFUND = 14;

    public const START_PREAUTHORIZATION = 12;

    public const COMPLETE_PREAUTHORIZATION = 21;

    public const CANCEL_PREAUTHORIZATION = 22;

    public const RECURRING_PURCHASE = 171;

    public const VERIFY_CARD = 39;

    public const SAVE_CARD = 81;

    public const FORGET_CARD = 82;
}
