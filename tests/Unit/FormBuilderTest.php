<?php

namespace Pashamesh\PsbAcquiringPhpSdk\Tests\Unit;

use Pashamesh\PsbAcquiringPhpSdk\Config;
use Pashamesh\PsbAcquiringPhpSdk\FormBuilder;
use PHPUnit\Framework\TestCase;

class FormBuilderTest extends TestCase
{
    public function testFromArray(): void
    {
        $config = Config::fromArray([
            'merchantName' => 'the-merchantName',
            'merchantNumber' => 'the-merchantNumber',
            'terminalNumber' => 'the-terminalNumber',
            'merchantEmail' => 'the-merchantEmail',
        ]);

        $getTimestamp = fn () => 'SOMETIMESTAMP';

        $payload = [
            'field1' => 1,
            'field2' => 2.99,
            'field3' => 'string-value',
        ];

        $this->assertEquals(
            '<form action="https://test.3ds.payment.ru/cgi-bin/cgi_link" '
            . 'id="psb_form_SOMETIMESTAMP" name="psb_form_SOMETIMESTAMP" method="POST">'
            . '<input type="hidden" name="field1" value="1" />'
            . '<input type="hidden" name="field2" value="2.99" />'
            . '<input type="hidden" name="field3" value="string-value" /></form>'
            . '<script type="text/javascript">document.getElementById(\'psb_form_SOMETIMESTAMP\').submit()</script>',
            (new FormBuilder($config, $getTimestamp))->fromArray($payload)
        );
    }
}
