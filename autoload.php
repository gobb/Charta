<?php

spl_autoload_register(function($class) {
    if (0 === strpos($class, 'Charta\\')) {
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
        $file = __DIR__ . "/src/$class.php";
        require $file;
    }
});