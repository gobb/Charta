### WARNING

> This repo is totally experimental and you should use it at your own risk. And by that, what I
> really mean is this: *DO NOT USE THIS IN PRODUCTION.* It's currently in an untagged, untested
> state. Be careful.


## Charta: Automatic output escaping for native PHP Templates

Charta is a templating system offering automatic output escaping in native
PHP templates without the need for a custom templating syntax or Domain Specific
Language. HTML, JavaScript and CSS escaping contexts are supported without 
the awkwardness of nesting another templating language inside PHP (which is
itself a templating language).

### BASIC USAGE

###### 1. Create a native PHP template:

```php
<?php /* myTemplate.php PHP Template */ ?>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <!-- IMPORTANT: Your HTML charset should always match the charset used in your escaping! -->
    <meta http-equiv="Content-Type" content="text/html; charset="<?php echo $charset; ?>" />
    <title>My First Charta Template</title>
</head>
<body>
    <p><?php echo $_('myDate'); ?></p>
    <p><?php echo $_('dangerousHtml'); ?></p>
</body>
</html>
```

###### 2. Inject `Charta\Templater` into your controller and assign template variables:

```php
<?php

use Charta\Templater;

class MyController {

    private $templater;
    private $tplPath = '/path/to/myTemplate.php';
    
    public function __construct(Templater $templater) {
        $templater->setCharset('UTF-8'); // UTF-8 is the default, so this isn't strictly necessary
        $this->templater = $templater;
    }
    
    public function doWork() {
        $this->templater->assign('myDate', date('Y-m-d'));
        $this->templater->assign('dangerousHtml', "<script>alert('attacked')</script>");
        
        return $this->templater->render($this->tplPath);
    }
}

$tpl = new Templater;
$controller = new MyController($templater);
echo $controller->doWork();
```

###### 3. Observe your safely escaped output:

Instead of outputting the vulnerable:

```HTML
<p>2012-12-31</p>
<p><script>alert('attacked')</script></p>
```

Charta's auto-escape feature safely escapes your data to prevent XSS:

```HTML
<p>2012-12-31</p>
<p>&lt;script&gt;alert(&#039;attacked&#039;)&lt;/script&gt;</p>
```


### COMMANDS

##### Utility Functions
```
Turn auto-escaping on/off:                  $autoEscape(TRUE); // Enabled by default
Switch escaping context:                    $context('html');  // HTML context used by default
                                            $context('js');
                                            $context('css');
One-off escape contexts:                    $html('someVar');
                                            $js('someVar');
                                            $css('someVar');
Test if a template variable exists:         $isAssigned('someVar');
```

##### Accessing Template Variables
```
Get value subject to autoescaping:          $get('someVar'); | $_('someVar');
Get escaped value subject to context:       $esc('someVar'); | $x('someVar');
Get raw value:                              $raw('someVar'); | $r('someVar');
```

##### Environment Vars
```
The output character set:                   $charset
The file path of the current template:      $tplPath
Accessing explicitly unsafe variables:      $unsafe_* (`*` matches the assigned variable name)
```