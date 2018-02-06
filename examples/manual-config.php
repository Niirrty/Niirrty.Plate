<?php

include dirname( __DIR__ ) . '/vendor/autoload.php';

$config = ( new \Niirrty\Plate\Config() )
   ->setTemplatesFolder( __DIR__ . '/templates' )
   ->setOpenChars( '{{' )
   ->setCloseChars( '}}' )
   ->setCacheCompileFolder( __DIR__ . '/tpl-cache' )
   ->setCacheCompileLifetime( 120 );

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


