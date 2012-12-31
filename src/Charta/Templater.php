<?php

namespace Charta;

use StdClass,
    Traversable,
    DomainException,
    InvalidArgumentException;

class Templater implements Renderer, AutoEscaper {
    
    /**
     * @var array
     */
    private $tplVars = array();
    
    /**
     * @var array
     */
    private $unsafeVars = array();
    
    /**
     * @var Escaper
     */
    private $escaper;
    
    /**
     * @var bool
     */
    private $autoEscape = TRUE;
    
    /**
     * For PHP 5.4+ the closure that renders the template should be unbound from the current object
     * to prevent unauthorized access to the Templater instance.
     * 
     * @var bool
     */
    private $unbindClosureFlag;
    
    /**
     * @param Escaper $escaper An optional custom output escaper
     * @return void
     */
    public function __construct(Escaper $escaper = NULL) {
        $this->escaper = $escaper ?: new ContextualEscaper;
        $this->unbindClosureFlag = (version_compare(PHP_VERSION, '5.4.0') >= 0);
    }
    
    /**
     * Setter method for escaping character set option
     * 
     * @param string $charset The character set to use when escaping output
     * @return void
     */
    public function setCharset($charset) {
        return $this->escaper->setCharset($charset);
    }
    
    /**
     * Getter method for retrieving the currently assigned output character set
     * 
     * @return string
     */
    public function getCharset() {
        return $this->escaper->getCharset();
    }
    
    /**
     * Setter method for context option
     * 
     * @param string $context
     * @throws DomainException On invalid context
     * @return void
     */
    public function setContext($context) {
        return $this->escaper->setContext($context);
    }
    
    /**
     * Begin auto-escaping
     * 
     * @param bool $boolFlag A truthy auto escape setting
     * @return void
     */
    public function setAutoEscape($boolFlag) {
        $this->autoEscape = (bool) filter_var($boolFlag, FILTER_VALIDATE_BOOLEAN);
    }
    
    /**
     * Retrieves the current auto-escape status
     * 
     * @return bool Returns the current auto-escape status
     */
    public function isEscaping() {
        return $this->autoEscape;
    }
    
    /**
     * Access an assigned template variable value
     * 
     * @param string $name Variable name
     * @throws DomainException On nonexistent variable name
     * @return mixed Returns the raw (unescaped) value of the specified template variable
     */
    public function get($name) {
        if (!$this->isAssigned($name)) {
            throw new DomainException(
                'Invalid template variable'
            );
        } else {
            return $this->tplVars[$name];
        }
    }
    
    /**
     * Is the specified template value assigned?
     * 
     * @param string $name A template variable name
     * @return bool Returns TRUE if the variable has been assigned, FALSE otherwise
     */
    public function isAssigned($name) {
        return (isset($this->tplVars[$name]) || array_key_exists($name, $this->tplVars));
    }
    
    /**
     * Assign multiple template variables at once
     * 
     * @param mixed $vars A list of template values
     * @throws InvalidArgumentException On non-Traversable, non-array, non-StdClass parameter
     * @return View Returns current object instance
     */
    public function assignAll($vars) {
        if (!(is_array($vars) || $vars instanceof Traversable || $vars instanceof StdClass)) {
            $type = is_object($vars) ? get_class($vars) : gettype($vars);
            throw new InvalidArgumentException(
                'View::setAll expects an array, StdClass or Traversable '.
                "object at Argument 1: {$type} specified"
            );
        }
        
        foreach ($vars as $key => $value) {
            $this->assign($key, $value);
        }
        
        return $this;
    }
    
    /**
     * Assign a variable to the template
     * 
     * @param string $name  Variable name
     * @param mixed  $value Variable value
     * @throws InvalidArgumentException If value contains non-scalar/array
     * @return View Returns current object instance
     */
    public function assign($name, $value) {
        if (!$this->validateAssignment($value)) {
            throw new InvalidArgumentException(
                'Only scalars and one-dimensional arrays containing scalars may be assigned ' .
                'as template values'
            );
        }
        
        $this->tplVars[$name] = $value;
        
        return $this;
    }
    
    private function validateAssignment($value) {
        if (is_scalar($value)) {
            return TRUE;
        } elseif (is_array($value)) {
            foreach ($value as $nestedValue) {
                if (!is_scalar($nestedValue)) {
                    return FALSE;
                }
            }
        } else {
            return FALSE;
        }
    }
    
    /**
     * Assign an unescaped, untouched variable for availability within the template
     * 
     * @param string $name
     * @param mixed $unsaveValue
     * @throws DomainException On invalid variable name
     * @return void
     */
    public function assignUnsafe($name, $unsafeValue) {
        if (preg_match(",^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$,", $name)) {
            $this->unsafeVars[$name] = $unsafeValue;
        } else {
            throw new DomainException(
                'Invalid unsafe variable name'
            );
        }
    }
    
    /**
     * Escapes specified input according to current context
     * 
     * @param string $data          Scalar data to escape
     * @param string $oneOffContext An escaping context to apply only for this operation
     * @throws DomainException On invalid context
     * @return string Returns the escaped data string
     */
    public function escape($data, $oneOffContext = NULL) {
        if (is_scalar($data)) {
            return $this->escaper->escape($data, $oneOffContext);
        } elseif (is_array($data) || $data instanceof Traversable) {
            $clean = array();
            foreach ($data as $key => $value) {
                $clean[$key] = $this->escaper->escape($value, $oneOffContext);
            }
            return $clean;
        } else {
            throw new InvalidArgumentException(
                'Invalid type: escape only accepts scalars and one-dimensional arrays'
            );
        }
    }
    
    /**
     * Renders and outputs the specified template
     * 
     * @param string $tplPath The filepath of the template we wish to output
     * @return void
     */
    public function output($tplPath) {
        echo $this->render($tplPath);
    }
    
    /**
     * Retrieve the rendered template without sending any output
     * 
     * @param string $tplPath The filepath of the PHP template to render
     * @return string Returns the rendered PHP template
     */
    public function render($tplPath) {
        $self = $this;
        
        $autoEscape = function($boolFlag) use ($self) {
            $self->setAutoEscape($boolFlag);
        };
        
        $context = function($context) use ($self) {
            $self->setContext($context);
        };
        
        $html = function($data) use ($self) {
            $self->escape($data, Escaper::HTML);
        };
        
        $js = function($data) use ($self) {
            $self->escape($data, Escaper::JS);
        };
        
        $css = function($data) use ($self) {
            $self->escape($data, Escaper::CSS);
        };
        
        $isAssigned = function($varName) use ($self) {
            return $self->isAssigned($varName);
        };
        
        $get = function($varName) use ($self) {
            $value = $self->get($varName);
            return $self->isEscaping() ? $self->escaper->escape($value) : $value;
        };
        
        $esc = function($varName) use ($self) {
            $value = $self->get($varName);
            return $self->escaper->escape($value);
        };
        
        $raw = function($varName) use ($self) {
            return $self->get($varName);
        };
        
        $renderer = function($tplPath, $charset, $autoEscape, $context, $get, $esc, $raw, $html, $js,
            $css, $isAssigned, $unsafeVars
        ) {
            $_ = $get;
            $x = $esc;
            $r = $raw;
            
            if (!empty($unsafeVars)) {
                extract($unsafeVars, EXTR_PREFIX_ALL, 'unsafe');
            }
            unset($unsafeVars);
            
            ob_start();
            require $tplPath;
            return ob_get_clean();
        };
        
        if ($this->unbindClosureFlag) {
            $renderer = $renderer->bindTo(NULL);
        }
        
        return $renderer($tplPath, $this->escaper->getCharset(), $autoEscape, $context, $get, $esc,
            $raw, $html, $js, $css, $isAssigned, $this->unsafeVars
        );
    }
    
}

