<?php

namespace Charta;

use RuntimeException;

class ContextualEscaper implements Escaper {
    
    const HTML = 'html';
    const JS = 'js';
    const CSS = 'css';
    
    const UTF8 = 'UTF-8';
    const CHARSET_CONVERTER_MB = 'mb_convert_encoding';
    const CHARSET_CONVERTER_ICONV = 'iconv';
    
    /**
     * The default character set for escaping
     * @var string
     */
    private $charset = self::UTF8;
    
    /**
     * The name of the system function used for charset encoding conversion
     * @var string
     */
    private $charsetConversionFunc;
    
    /**
     * The current auto-escaping context
     * @var string
     */
    private $context = self::HTML;
    
    /**
     * A list of valid character set values
     * @var array
     * @link http://php.net/manual/function.htmlspecialchars.php
     */
    private static $supportedCharsets = array(
        'iso-8859-1' => 1,
        'iso8859-1' => 1,
        'iso-8859-15' => 1,
        'iso8859-15' => 1,
        'utf-8' => 1,
        'cp866' => 1,
        'ibm866' => 1,
        '866' => 1,
        'cp1251' => 1,
        'windows-1251' => 1,
        'win-1251' => 1,
        '1251' => 1,
        'cp1252' => 1,
        'windows-1252' => 1,
        '1252' => 1,
        'koi8-r' => 1,
        'koi8-ru' => 1,
        'koi8r' => 1,
        'big5' => 1,
        '950' => 1,
        'gb2312' => 1,
        '936' => 1,
        'big5-hkscs' => 1,
        'shift_jis' => 1,
        'sjis' => 1,
        '932' => 1,
        'euc-jp' => 1,
        'eucjp' => 1,
        'iso8859-5' => 1,
        'iso-8859-5' => 1,
        'macroman' => 1
    );
    
    public function __construct() {
        $this->charsetConversionFunc = $this->selectCharsetConverter();
    }
    
    private function selectCharsetConverter() {
        if (function_exists(self::CHARSET_CONVERTER_MB)) {
            return self::CHARSET_CONVERTER_MB;
        } elseif (function_exists(self::CHARSET_CONVERTER_ICONV)) {
            return self::CHARSET_CONVERTER_ICONV;
        } else {
            return NULL;
        }
    }
    
    /**
     * Setter method for escaping character set option
     * 
     * @param string $charset The character set to use when escaping output
     * @return void
     */
    public function setCharset($charset) {
        $this->charset = $charset;
    }
    
    public function getCharset() {
        return $this->charset;
    }
    
    /**
     * Setter method for context option
     * 
     * @param string $context An escaping context in the domain: [html|js|css]
     * @throws DomainException On invalid context
     * @return void
     */
    public function setContext($context) {
        $context = strtolower($context);
        
        switch ($context) {
            case self::HTML:
                $this->context = self::HTML;
                break;
            case self::JS:
                $this->context = self::JS;
                break;
            case self::CSS:
                $this->context = self::CSS;
                break;
            default:
                throw new DomainException(
                    'Invalid escape context'
                );
        }
    }
    
    /**
     * Escapes specified input according to current context
     * 
     * @param string $data          Raw data to be escaped
     * @param string $oneOffContext An escaping context to apply only for this operation
     * @throws DomainException On invalid context
     * @return string Returns the escaped data string
     */
    public function escape($data, $oneOffContext = NULL) {
        $context = $oneOffContext ?: $this->context;
        
        switch ($context) {
            case self::HTML:
                return $this->escapeHtml($data);
            case self::JS:
                return $this->escapeJsVal($data);
            case self::CSS:
                return $this->escapeCssVal($data);
            default:
                throw new DomainException(
                    'Invalid escape context'
                );
        }
    }
    
    /**
     * Escapes the specified string for output in an HTML context
     * 
     * As per the PHP manual entry for `htmlspecialchars()`:
     * 
     * > Calling htmlspecialchars() is sufficient if the encoding supports 
     * > all characters in the input string.
     * 
     * The raw `$data` string is converted if necessary to ensure that our 
     * input string matches the encoding passed to `htmlspecialchars`. Beyond 
     * this, it is the developer's responsibility to specify the correct 
     * character set on the HTML page and `ContextEscaper::setCharset()`.
     * 
     * @param string $data The raw data string to escape
     * @return string Returns the escaped string
     * @link http://php.net/manual/function.htmlspecialchars.php
     */
    private function escapeHtml($data) {
        if (isset(self::$supportedCharsets[strtolower($this->charset)])) {
            $targetCharset = $this->charset;
        } else {
            $targetCharset = self::UTF8;
            $data = $this->convertEncoding($data, $targetCharset, $this->charset);
        }
        
        return htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, $targetCharset);
    }
    
    /**
     * Convert the charset encoding of the specified data string
     * 
     * @param string $data The source data to be converted
     * @param string $to   The target character set
     * @param string $from The source character set
     * 
     * @return string Returns the converted data string
     * @throws RuntimeException If no valid conversion functions are loaded in the PHP install
     */
    private function convertEncoding($data, $to, $from) {
        switch ($this->charsetConversionFunc) {
            case self::CHARSET_CONVERTER_MB:
                return mb_convert_encoding($data, $to, $from);
                break;
            case self::CHARSET_CONVERTER_ICONV:
                return iconv($from, $to, $data);
                break;
            default:
                throw new RuntimeException(
                    'No charset encoding conversion function exists. Please '.
                    'use the UTF-8 character set or install either iconv or '.
                    'the mbstring extension.'
                );
        }
    }
    
    /**
     * Escapes strings for output in a javascript context; all non-alphanumerics are replaced with 
     * their \xHH or \uHHHH equivalents.
     * 
     * @param string $data A raw data string to escape for javascript output
     * @return string Returns escaped string
     * @throws RuntimeException On invalid UTF-8 string
     */
    private function escapeJsVal($data) {
        if ($conversionRequired = strcasecmp($this->charset, self::UTF8)) {
            $data = $this->convertEncoding($data, self::UTF8, $this->charset);
        }
        
        $data = preg_replace_callback("/[^ \p{N}\p{L}]/u", array($this, 'jsReplaceCallback'), $data);
        
        if (NULL === $data) {
            throw new RuntimeException(
                'Escape failure: invalid UTF-8 string specified'
            );
        }
        
        // convert back to the original charset after we're finished
        if ($conversionRequired) {
            $data = $this->convertEncoding($data, $this->charset, self::UTF8);
        }
        
        return $data;
    }
    
    private function jsReplaceCallback($regexMatch) {
        $char = $regexMatch[0];
        
        if (!isset($char[1])) {
            return '\\x'.substr('00'.bin2hex($char), -2); // \xHH
        } else {
            $char = $this->convertEncoding($char, 'UTF-16BE', 'UTF-8'); // \uHHHH
            return '\\u'.substr('0000'.bin2hex($char), -4);
        }
    }
    
    /**
     * Escapes strings for output in a css context; all non-alphanumerics are replaced with their
     * their \HHHHHH equivalents.
     * 
     * @param string $data A raw data string to escape for javascript output
     * @return string Returns escaped string
     * @throws RuntimeException On invalid UTF-8 string
     */
    private function escapeCssVal($data) {
        if ($conversionRequired = strcasecmp($this->charset, self::UTF8)) {
            $data = $this->convertEncoding($data, self::UTF8, $this->charset);
        }
        
        $data = preg_replace_callback("/./", array($this, 'cssReplaceCallback'), $data);
        
        if (NULL === $data) {
            throw new RuntimeException(
                'Escape failure: invalid UTF-8 string specified'
            );
        }
        
        // convert back to the original charset after we're finished
        if ($conversionRequired) {
            $data = $this->convertEncoding($data, $this->charset, self::UTF8);
        }
        
        return $data;
    }
    
    private function cssReplaceCallback($regexMatch) {
        $char = $regexMatch[0];
        
        if (!isset($char[1])) {
            return '\\'.substr('00'.bin2hex($char), -2).' '; // \HH
        } else {
            $char = $this->convertEncoding($char, 'UTF-16BE', 'UTF-8'); // \HHHHHH
            return '\\'.substr('000000'.bin2hex($char), -6);
        }
    }
}
