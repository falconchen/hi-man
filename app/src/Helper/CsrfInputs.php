<?php

namespace App\Helper;


class CsrfInputs extends \Twig_Extension
{


    public function __construct($container)
    {
        $this->container = $container;
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
        $nameKey = $this->container->csrf->getTokenNameKey();
        $valueKey = $this->container->csrf->getTokenValueKey();

        $name = $this->container->csrf->getTokenName();
        $value = $this->container->csrf->getTokenValue();

        // Render HTML form which POSTs to /bar with two hidden input fields for the
        // name and value:
        $output  = '<input type="hidden" name="'.$nameKey .'" value="'.$name .'">';
        $output .= '<input type="hidden" name="'.$valueKey.'" value="'.$value.'">';

        return $output;
    }

}