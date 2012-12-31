<?php

namespace Charta;

interface Renderer {

    /**
     * Template variable setter method     *      * @param string $name Template variable name
     * @param mixed  $var  Template variable value
     */
    public function assign($name, $var);
    
    /**
     * Assign an array of multiple template variables at once
     * 
     * @param mixed $vars An array, StdClass or Traversable object used for
     *                    mass template variable assignment
     */
    public function assignAll($vars);
    
    /**
     * Determines if a template variable has been assigned
     * 
     * @param string $name Template variable name
     */
    public function isAssigned($name);
    
    /**
     * Output a rendered template
     * 
     * @param mixed $opts Template rendering options
     */
    public function output($opts);
    
    /**
     * Fetch rendered template without sending output
     * 
     * @param mixed $opts Template rendering options
     */
    public function render($opts);
}

