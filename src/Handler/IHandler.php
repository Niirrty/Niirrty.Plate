<?php
/**
 * @author     Ni Irrty <niirrty+code@gmail.com>
 * @copyright  © 2017-2020, Niirrty
 * @package    Niirrty\Plate\Handler
 * @since      2017-11-04
 * @version    0.3.0
 */


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
     * @param string $contents
     *
     * @return string
     */
    function execute( string $contents ): string;


}

