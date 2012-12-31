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
    <p><?php echo $unsafe_myDateTimeObj->format('Y-m-d'); ?></p>
    <p>
        <?php
        foreach ($_('dangerousArray') as $dangerousVal) {
            echo "$dangerousVal<br />";
        }
        ?>
    </p>
</body>
</html>
```

###### 2. Assign template variables:

```php
<?php

$tpl = new Templater;
$tpl->setCharset('UTF-8'); // UTF-8 is the default, so this isn't strictly necessary
$tpl->assign('myDate', date('Y-m-d'));
$tpl->assign('dangerousHtml', "<script>alert('attacked')</script>");
$tpl->assign('dangerousArray', array(
    "<script>alert('you')</script>",
    "<script>alert('got')</script>",
    "<script>alert('h4x0rd')</script>"
));

$tpl->assignUnsafe('myDateTimeObj', new DateTime);

$tpl->output($this->tplPath);
```

###### 3. Observe your safely escaped output:

Instead of outputting the vulnerable:

```HTML
<p>2012-12-31</p>
<p><script>alert('attacked')</script></p>
<p>
    <script>alert('you')</script>
    <script>alert('got')</script>
    <script>alert('h4x0rd')</script>
</p>
<p>2012-12-31</p>
```

Charta's auto-escape feature safely escapes your data to prevent XSS:

```HTML
<p>2012-12-31</p>
<p>&lt;script&gt;alert(&#039;attacked&#039;)&lt;/script&gt;</p>
<p>
    &lt;script&gt;alert(&#039;you&#039;)&lt;/script&gt;<br />
    &lt;script&gt;alert(&#039;got&#039;)&lt;/script&gt;<br />
    &lt;script&gt;alert(&#039;h4x0rd&#039;)&lt;/script&gt;<br />
</p>
<p>2012-12-31</p>
```


### INSIDE YOUR TEMPLATES ...

##### Utilities
```
Toggle auto-escaping (On by default):       $autoEscapeOn(); | $autoEscapeOff();
Switch escaping context (HTML default):     $contextHtml(); | $contextJs(); | $contextCss();
One-off escape contexts:                    $html('someVar');
                                            $js('someVar');
                                            $css('someVar');
Test if a template variable exists:         $isAssigned('someVar');
```

##### Accessing Assigned Template Variables
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