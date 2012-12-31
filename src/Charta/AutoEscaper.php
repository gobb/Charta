<?php

namespace Charta;

interface AutoEscaper extends Escaper {

    /**
     * Enable/disable auto-escaping
     * 
     * @param bool $boolFlag The new auto-escape setting
     */
    public function setAutoEscape($boolFlag);
    
    /**
     * Retrieves the current auto-escape status
     */
    public function isEscaping();
}

