<?php

$_context('js');

echo $xss1->r() . ' - ' . $xss1 . ' | ';

$_context('html');

echo$xss2->r() . ' - ' . $xss2;

?>
