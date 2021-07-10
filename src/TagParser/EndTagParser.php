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


class EndTagParser extends PlateTagParser
{


    #region // C O N S T R U C T O R

    public function __construct( Config $config )
    {

        parent::__construct( '/', $config );
        $this->_autoIdentify = true;

    }

    #endregion


    #region // P U B L I C   M E T H O D S

    protected function _parse(
        string $tagDefinition, string $afterTagClose = '', string $newLineAfter = '', ?string $package = null  ) : bool
    {

        if ( \in_array( $tagDefinition, [ '/if', '/for', '/foreach', '/end' ] ) )
        {
            $this->_compiled = ( new PlateTagCompiled() )
                ->setPhpCode( '<?php } ?>' )
                ->setNewLineAfter( " \n" )
                ->setAfterTagClose( $afterTagClose );
            return true;
        }

        $this->_compiled = ( new PlateTagCompiled() )
            ->setPhpCode( $this->_config->getOpenChars() . $tagDefinition . $this->_config->getCloseChars() )
            ->setAfterTagClose( $afterTagClose );
        return true;

    }

    #endregion


}

