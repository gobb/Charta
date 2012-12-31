<?php

/**
 * Unit Testing Bootstrap File
 * 
 * Registers an autoloader for Charta and vfsStream classes and initializes
 * an in-memory virtual file system.
 * 
 * @category   Charta
 * @author     Daniel Lowrey <rdlowrey@gmail.com>
 */

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;

define('CHARTA_SYSDIR', dirname(__DIR__));
define('VFS_SYSDIR', CHARTA_SYSDIR .'/vendor/vfsStream');

/*
 * --------------------------------------------------------------------
 * Register Charta & vfsStream autoloader
 * --------------------------------------------------------------------
 */

spl_autoload_register(function($cls) {
    if (0 === strpos($cls, 'Charta\\')) {
        $cls = str_replace('\\', '/', $cls);        
        require CHARTA_SYSDIR . "/src/$cls.php";
    } elseif (0 === strpos($cls, 'org\\bovigo\\vfs\\')) {
        $cls = str_replace('\\', '/', $cls);        
        require VFS_SYSDIR . "/src/main/php/$cls.php";
    }
});

/*
 * --------------------------------------------------------------------
 * Load virtual file system (vfsStream)
 * --------------------------------------------------------------------
 */

vfsStreamWrapper::register();
vfsStream::copyFromFileSystem(__DIR__.'/fixtures/vfs', vfsStream::setup('root'));
