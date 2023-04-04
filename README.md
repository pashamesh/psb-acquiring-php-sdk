<p align="center">
    <img src="https://github.com/pashamesh/psb-acquiring-php-sdk/actions/workflows/code_style.yml/badge.svg" alt="Code style">
    <img src="https://github.com/pashamesh/psb-acquiring-php-sdk/actions/workflows/tests.yml/badge.svg" alt="Tests">
</p>

# <a href="https://www.psbank.ru/"><img src=".media/logo.svg" width="100" height="30"/></a> acquiring API PHP SDK.

This package provides Software Development Kit for PromSvyazBank (PSB) Acquiring API.

## Installation
You can install the package via composer:
```shell
composer require pashamesh/psb-acquiring-php-sdk
```

## Usage

### Setup client
Setup instance of `Config`: 
```php
use Pashamesh\PsbAcquiringPhpSdk\Config;
use Pashamesh\PsbAcquiringPhpSdk\PsbClient;

$config = Config::fromArray([
    'component1' => '<COMPONENT1>',
    'component2' => '<COMPONENT2>',
    'merchantName' => 'Real Shop',
    'merchantNumber' => '<MERCHANT_NUMBER>',
    'terminalNumber' => '<TERMINAL_NUMBER>',
    'merchantEmail' => '<MERCHANT_EMAIL>',
    'notifyUrl' => 'https://some.domain/notify.php',
    'returnUrl' => 'https://some.domain/',
]);
$psb = new PsbClient($config);
```
where `component1`, `component2`, `merchantNumber` and `terminalNumber` are credentials provided by bank.

#### Test environment
To configure client to use test environment for test and development purposes just skip `component1` and `component2`:
```php
use Pashamesh\PsbAcquiringPhpSdk\Config;
use Pashamesh\PsbAcquiringPhpSdk\PsbClient;

$testEnvironmentConfig = Config::fromArray([
    'merchantName' => 'Test Shop',
    'merchantNumber' => '000599979036777',
    'terminalNumber' => '79036777',
    'merchantEmail' => 'merchant@mail.test',
    'notifyUrl' => 'https://some.domain/notify.php',
    'returnUrl' => 'https://some.domain/',
]);
$psb = new PsbClient($testEnvironmentConfig);
```

## How to use
### Preauthorization
#### Start preauthorization
Render and automatically submit preauthorization form which will redirect customer to the bank payment page:
```php
$customerEmail = 'cardholder@mail.test';
$orderId = '620749153';
$amount = 300.;

$psb
    ->customer($customerEmail)
    ->order($orderId, "Order #{$orderId}")
    ->preauthorize($amount)
    ->sendForm();
```
Or it's possible to get form HTML content if you need more control:
```php
$preauthorizeFormHtml = $psb
    ->customer($customerEmail)
    ->order($orderId, "Order #{$orderId}")
    ->preauthorize($amount)
    ->getForm();
```
It's also possible to get payment link for preauthorization which maybe sent to customer by email for example:
```php
$expiresAt = '01.04.2023 03:30:00';

$preauthorizeLink = $psb
    ->customer($customerEmail)
    ->order($orderId, "Order #{$orderId}")
    ->preauthorize($amount)
    ->getLink($expiresAt);
```
To save card during the preauthorization process use `preauthorizeAndSaveCard` method: 
```php
$psb
    ->customer($customerEmail)
    ->order($orderId, "Order #{$orderId}")
    ->preauthorizeAndSaveCard($amount)
    ->sendForm();
```
Notification HTTP call will contain `TOKEN_ID` identifying saved card.

There is a `preauthorizeUsingCard` to do preauthorization and use already saved card:
```php
$cardTokenId = '<CARD_TOKEN_UUID>';

$psb
    ->customer($customerEmail)
    ->order($orderId, "Order #{$orderId}")
    ->preauthorizeUsingCard($amount, $cardTokenId)
    ->sendForm();
```

#### Complete preauthorization
```php
$rrn = '<PREAUTHORIZATION_RETRIEVAL_REFERENCE_NUMBER>';
$intRef = '<PREAUTHORIZATION_INTERNAL_REFERENCE>';

$finalAmount = 300.;

$syncResponse = $psb
    ->customer($customerEmail)
    ->order($orderId, "Order #{$orderId}")
    ->transaction($rrn, $intRef, $amount)
    ->completePreauthorization($finalAmount)
    ->sendRequest();
```

#### Cancel preauthorization
```php
$syncResponse = $psb
    ->customer($customerEmail)
    ->order($orderId, "Order #{$orderId}")
    ->transaction($rrn, $intRef, $amount)
    ->cancelPreauthorization($finalAmount)
    ->sendRequest();
```

## Purchase
Purchase uses similar to preauthorization workflow.
Render and automatically submit preauthorization form which will redirect customer to the bank payment page:

```php
$psb
    ->customer($customerEmail)
    ->order($orderId, "Order #{$orderId}")
    ->purchase($amount)
    ->sendForm();
```
Or get form HTML content :
```php
$preauthorizeFormHtml = $psb
    ->customer($customerEmail)
    ->order($orderId, "Order #{$orderId}")
    ->purchase($amount)
    ->getForm();
```

Or get payment link for purchase:
```php
$expiresAt = '01.04.2023 03:30:00';

$purchaseLink = $psb
    ->customer($customerEmail)
    ->order($orderId, "Order #{$orderId}")
    ->purchase($amount)
    ->getLink($expiresAt);
```
To save card during the purchase process use `purchaseAndSaveCard` method:
```php
$psb
    ->customer($customerEmail)
    ->order($orderId, "Order #{$orderId}")
    ->purchaseAndSaveCard($amount)
    ->sendForm();
```

There is a `purchaseUsingCard` to do purchase and use already saved card:
```php
$cardTokenId = '<CARD_TOKEN_UUID>';

$psb
    ->customer($customerEmail)
    ->order($orderId, "Order #{$orderId}")
    ->purchaseUsingCard($amount, $cardTokenId)
    ->sendForm();
```

## Recurring payment
### Register
```php
$frequency = 1;
$expirationDate = '20240101';

$psb
    ->customer($customerEmail)
    ->order($orderId, "Order #{$orderId}")
    ->purchase($amount)
    ->registerRecurring($frequency, $expirationDate)
    ->sendForm();
```

### Do payment
```php
$recurringRrn = '<RECURRING_RETRIEVAL_REFERENCE_NUMBER>';
$recurringIntRef = '<RECURRING_INTERNAL_REFERENCE>';

$syncResponse = $psb
    ->customer($customerEmail)
    ->order($orderId, "Order #{$orderId}")
    ->doRecurringPayment($amount, $recurringRrn, $recurringIntRef)
    ->sendRequest();
```

## Refund
```php
$rrn = '<PURCHASE_RETRIEVAL_REFERENCE_NUMBER>';
$intRef = '<PURCHASE_INTERNAL_REFERENCE>';

$response = $psb
    ->customer($customerEmail)
    ->order($orderId, "Order #{$orderId}")
    ->transaction($rrn, $intRef, $amount)
    ->refund($finalAmount)
    ->sendRequest();
```

## Cards

### Save card from existing transaction
```php
$rrn = '<RETRIEVAL_REFERENCE_NUMBER>';
$intRef = '<INTERNAL_REFERENCE>';

$syncResponse = $psb
    ->customer($customerEmail)
    ->order($orderId, "Test payment")
    ->transaction($rrn, $intRef)
    ->saveCard()
    ->sendRequest();
```
`$syncResponse` will contain `TOKEN_ID` identifying saved card.

### Forget saved card
```php
$cardTokenId = '<CARD_TOKEN_UUID>';

$syncResponse = $psb
    ->forgetCard($cardTokenId)
    ->sendRequest();

if ($syncResponse->isOperationApproved()) {
    // The card token was forgotten.
}
```

### Handle callback HTTP call
Payload of asynchronous HTTP callback request must be validated for valid signature.
SDK provide convenient method `handleCallbackRequest`.
It validates signature and returns [Payload](src/Payload.php) model with request attributes.
#### Example
```php
try {
    $payload = $client->handleCallbackRequest($_POST);
    if ($payload->isOperationApproved()) {
        $orderId = $payload->order;
        $referenceReturnNumber = $payload->rrn;
        $internalReference = $payload->int_ref;

        // Process data here. For example store in database.
    }

    echo "OK";
} catch (Exception $exception) {
    echo $exception->getMessage();
}
```

## Development
There is a Docker-compose setup and set of handy `make` shortcuts for development purposes.

### Install dependencies

Use next command to install composer dependencies:
```shell
make
```

### Code styling

This package follows
the [PSR-12](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-12-extended-coding-style-guide.md) coding
standard and the [PSR-4](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md) autoload
standard.

Use next command to fix code styling:
```shell
make lint
```

### Running tests
Use next command to run `Unit` and code static analysis:

```shell
make test
```

## Links
- [Original docs (RU)](https://www.psbank.ru/-/media/Files/Business/Acquiring/Internet/TechDoc/Interaction_procedure_standard.pdf)