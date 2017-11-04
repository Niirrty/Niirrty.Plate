<?php
/**
 * @author     Ni Irrty <niirrty+code@gmail.com>
 * @copyright  ©2017, Ni Irrty
 * @package    Niirrty\Plate
 * @since      2017-11-04
 * @version    0.1.0
 */

declare( strict_types=1 );


namespace Niirrty\Plate;


use Niirrty\NiirrtyException;


/**
 * Plate template engine core exception.
 *
 * @package Niirrty\Plate
 */
class PlateException extends NiirrtyException
{


   // <editor-fold desc="// – – –   P U B L I C   C O N S T R U C T O R   – – – – – – – – – – – – – – – – – – – –">

   /**
    * Initialize a new \Niirrty\Plate\PlateException instance.
    *
    * @param string          $message
    * @param int             $code
    * @param \Throwable|null $previous
    */
   public function __construct( string $message, int $code = 256, \Throwable $previous = null )
   {

      parent::__construct( $message, $code, $previous );

   }

   // </editor-fold>


}

