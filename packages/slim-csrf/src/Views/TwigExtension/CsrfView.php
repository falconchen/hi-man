<?php

namespace FalconChen\SlimCsrf\Views\TwigExtension;


class CsrfView extends \Twig_Extension
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
            new \Twig_SimpleFunction('csrf_inputs', array($this, 'csrfInputs'), array('is_safe' => array('html'))),
            new \Twig_SimpleFunction('csrf_metas', array($this, 'csrfMetas'), array('is_safe' => array('html'))),
            new \Twig_SimpleFunction('csrf_json', array($this, 'csrfJson'), array('is_safe' => array('html'))),
        ];
    }

    public function csrfInputs()
    {
        // $nameKey = $this->csrf->getTokenNameKey();
        // $valueKey = $this->csrf->getTokenValueKey();

        // $name = $this->csrf->getTokenName();
        // $value = $this->csrf->getTokenValue();
        
        extract($this->csrfTokenArr());

        // Render HTML form which POSTs to /bar with two hidden input fields for the
        // name and value:
        $output  = '<input type="hidden" name="'.$nameKey .'" value="'.$name .'">'."\n" ;
        $output .= '<input type="hidden" name="'.$valueKey.'" value="'.$value.'">';

        return $output;
    }

    protected function csrfTokenArr() {

        return [
            'nameKey'=>$this->csrf->getTokenNameKey(),
            'valueKey'=>$this->csrf->getTokenValueKey(),
            'name'=>$this->csrf->getTokenName(),
            'value'=>$this->csrf->getTokenValue()
        ];

    }

    public function csrfMetas() 
    {
        extract($this->csrfTokenArr());
        
        $output = '<meta name="csrf-token-name" '.'" value="'.$name .'"/>'."\n" ;
        $output .= '<meta name="csrf-token-value" '.'" value="'.$value .'"/>'."\n" ;
        return $output;   
    }

    public function csrfJson() 
    {
        extract($this->csrfTokenArr());
        return json_encode([
            'name'=>$name,
            'value'=>$value,
        ]);                
    }

}