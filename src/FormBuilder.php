<?php

namespace Pashamesh\PsbAcquiringPhpSdk;

class FormBuilder
{
    private Config $config;
    /**
     * @var \Closure():string $getTimestamp
     */
    private $getTimestamp;

    /**
     * @param \Closure():string|null $getTimestamp
     */
    public function __construct(
        Config $config,
        ?callable $getTimestamp = null
    ) {
        $this->config = $config;
        $this->getTimestamp = $getTimestamp ?? fn (): string => gmdate('YmdHis');
    }

    /**
     * @param array<string,string|int> $fields
     */
    public function fromArray(array $fields): string
    {
        if (!$fields) {
            return '';
        }

        $formName = 'psb_form_' . ($this->getTimestamp)();

        $form = sprintf(
            '<form action="%s" id="%s" name="%s" method="POST">',
            "{$this->config->gatewayDomain}/cgi-bin/cgi_link",
            $formName,
            $formName
        );
        foreach ($fields as $name => $value) {
            $form .= sprintf('<input type="hidden" name="%s" value="%s" />', $name, $value);
        }
        $form .= '</form>';
        $form .= '<script type="text/javascript">';
        $form .= "document.getElementById('{$formName}').submit()";
        $form .= '</script>';

        return $form;
    }
}
