<?php

namespace FalconChen\Slim\Views\TwigExtension;


class CsrfInputs extends \Twig_Extension
{


    public function __construct($csrf)
    {
        $this->csrf = $csrf;
    }

    public function getName()
    {
        return 'csrf';
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('csrf_inputs', array($this, 'csrfInputs'), array('is_safe' => array('html'))
            ),
        ];
    }

    public function csrfInputs()
    {
        $nameKey = $this->csrf->getTokenNameKey();
        $valueKey = $this->csrf->getTokenValueKey();

        $name = $this->csrf->getTokenName();
        $value = $this->csrf->getTokenValue();

        // Render HTML form which POSTs to /bar with two hidden input fields for the
        // name and value:
        $output  = '<input type="hidden" name="'.$nameKey .'" value="'.$name .'">'."\n" ;
        $output .= '<input type="hidden" name="'.$valueKey.'" value="'.$value.'">';

        return $output;
    }

}