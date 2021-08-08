<?php


include dirname( __DIR__ ) . '/vendor/autoload.php';
include __DIR__ . '/MyHelpTagParser.php';

use Niirrty\IO\Vfs\VfsManager;


$locale = \Niirrty\Locale\Locale::Create( new \Niirrty\Locale\Locale( 'de', '', 'utf-8' ) );
$tplVfs = \Niirrty\IO\Vfs\VfsHandler::Create(
    'Templates root folder', 'tpl', '://', __DIR__ . DIRECTORY_SEPARATOR . 'templates' );
$cacheVfs = \Niirrty\IO\Vfs\VfsHandler::Create(
    'Template cache root folder', 'cache', '://', __DIR__ . DIRECTORY_SEPARATOR . 'tpl-cache' );
$transVfs = \Niirrty\IO\Vfs\VfsHandler::Create(
    'Translations folder', 'trans', '://', __DIR__ . DIRECTORY_SEPARATOR . 'translations' );
$vfsManager = VfsManager::Create();
$vfsManager->addHandler( $tplVfs );
$vfsManager->addHandler( $cacheVfs );
$vfsManager->addHandler( $transVfs );

$config = \Niirrty\Plate\Config::FromINIFile(
   __DIR__ . DIRECTORY_SEPARATOR . 'plate-config.ini', 'ini', $vfsManager
);

#$config->setCacheCompileLifetime( 5 );
$config->setCacheMode( \Niirrty\Plate\Config::CACHE_MODE_EDITOR );

$translator = new \Niirrty\Translation\Translator( $locale );
$translatorSource = new \Niirrty\Translation\Sources\PHPFileSource( 'trans://', $locale, $vfsManager );
$translator->addSource( 'MyAPP', $translatorSource );



$engine = new \Niirrty\Plate\Engine( $config, $translator );
$engine->registerTagParser(
    new MyHelpTagParser( $config )
);

$engine
   ->assign( 'int', 1 )
   ->assign( 'float', 0.14 )
   ->assign( 'bool', false )
   ->assign( 'string', 'Überleben wird keiner!' )
   ->assign( 'array1', [ 2, 4, 5, 7, 9  ] )
   ->assign( 'array2', [ [ 'foo' => 'FOO', 'bar' => 'Bar' ] ] )
   ->assign( 'datetime', new DateTime() )
   ->assign( 'user', 'John Who' )
   ->assign( 'formatDtm', $translator->read( 'FormatDtm', 'MyApp', '' ) )
   ->assign( 'subTemplateFile', 'sub-template.tpl' );

$engine->display( 'example1.tpl' );


