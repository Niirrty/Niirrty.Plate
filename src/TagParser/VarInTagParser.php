<?php
/**
 * @package Niirrty\Plate\TagParser
 * @version 0.3.1
 * @since   2021-07-03
 * @author  Ni Irrty <niirrty+code@gmail.com>
 */


declare( strict_types=1 );


namespace Niirrty\Plate\TagParser;


use Niirrty\Plate\Config;


class VarInTagParser extends PlateTagParser
{


    #region // C O N S T R U C T O R

    public function __construct( Config $config )
    {

        parent::__construct( '+', $config );
        $this->_autoIdentify = false;

    }

    #endregion


    #region // P U B L I C   M E T H O D S

    protected function _parse(
        string $tagDefinition, string $afterTagClose = '', string $newLineAfter = '', ?string $package = null  ) : bool
    {

        if ( $this->_identifier != $tagDefinition[ 0 ] || ! isset( $tagDefinition[ 1 ] ) || '$' != $tagDefinition[ 1 ] )
        {
            return false;
        }

        $this->_compiled = ( new PlateTagCompiled() )
            ->setPhpCode( '<?php ' . \ltrim( $tagDefinition, '+' ) . '; ?> ' )
            ->setNewLineAfter( '' )
            ->setAfterTagClose( $afterTagClose );

        return true;

    }

    #endregion


}

