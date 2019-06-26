# slim twig view csrf inputs

A slim twig template extension for easy appending the hidden csrf inputs fields.


### usage:

load the extension in the `dependecies.php`, also need to load `\Slim\Csrf\Guard` into dependecies.
 
```
...
// Load Twig
$container['view'] = function ($c) {
    $settings = $c->get('settings');
    $view = new \Slim\Views\Twig($settings['view']['template_path'], $settings['view']['twig']);

    // Add extensions
    $view->addExtension(new Slim\Views\TwigExtension($c->get('router'), $c->get('request')->getUri()));
    $view->addExtension(new Twig_Extension_Debug());
    // add the twig csrf inputs here 
    $view->addExtension(new FalconChen\Slim\Views\TwigExtension\CsrfInputs($c->csrf));
    
    return $view;
};

//need to load csrf 
$container['csrf'] = function ($c) {
    $guard = new \Slim\Csrf\Guard();
    $guard->setFailureCallable(function ($request, $response, $next) {
        $request = $request->withAttribute("csrf_status", false);
        return $next($request, $response);
    });
    return $guard;
};
...
```

then in twig templates , call the `csrf_inputs()` function.

```
<form action="login" method="post" autocomplete="off">

{{ csrf_inputs() }}
...

```

output :
```
<input type="hidden" name="csrf_name" value="csrf653024471">
<input type="hidden" name="csrf_value" value="1924fe05051c809802334ab0601aeaa1">

```
