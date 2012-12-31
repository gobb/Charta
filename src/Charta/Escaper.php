<?php

namespace Charta;

interface Escaper {
    
    /**
     * Setter method for escaping character set option
     * 
     * @param string $charset The character set to use when escaping output
     */
    public function setCharset($charset);
    
    /**
     * Getter method for retrieving the current character set used when escaping data
     */
    public function getCharset();
    
    /**
     * Setter method for context option
     * 
     * @param string $context
     */
    public function setContext($context);
    
    /**
     * Escapes specified input according to current context
     * 
     * @param string $data          Raw data to be escaped
     * @param string $oneOffContext An escaping context to apply only for this operation
     */
    public function escape($data, $oneOffContext = NULL);
    
}

