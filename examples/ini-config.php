<?php

include dirname( __DIR__ ) . '/vendor/autoload.php';

$config = \Niirrty\Plate\Config::FromINIFile(
   'tpl-root:/plate-config.ini',
   'ini',
   \Niirrty\IO\Vfs\Manager::Create()
      ->addHandler(
         \Niirrty\IO\Vfs\Handler::Create( 'Templates root folder' )
            ->setProtocol( 'tpl-root', ':/' )
            ->setRootFolder( __DIR__ )
      )
);

#$config->setCacheCompileLifetime( 5 );
$config->setCacheMode( \Niirrty\Plate\Config::CACHE_MODE_EDITOR );


$engine = new \Niirrty\Plate\Engine( $config );

$engine
   ->assign( 'int', 1 )
   ->assign( 'float', 0.14 )
   ->assign( 'bool', false )
   ->assign( 'string', 'Überleben wird keiner!' )
   ->assign( 'array1', [ 2, 4, 5, 7, 9  ] )
   ->assign( 'array2', [ [ 'foo' => 'FOO', 'bar' => 'Bar' ] ] )
   ->assign( 'datetime', new DateTime() )
   ->assign( 'user', 'John Who' )
   ->assign( 'subTemplateFile', 'sub-template.tpl' );

$engine->display( 'example1.tpl' );


