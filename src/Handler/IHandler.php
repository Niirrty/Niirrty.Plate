<?php


declare( strict_types=1 );


namespace Niirrty\Plate\Handler;


/**
 * The output handler interface.
 *
 * @package Niirrty\Plate\Handler
 */
interface IHandler
{


   /**
    * Calls the output handler with defined content.
    *
    * @param  string $contents
    * @return string
    */
   function execute( string $contents ) : string;


}

