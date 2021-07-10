<?php
/**
 * @package Niirrty\Plate\TagParser
 * @version 0.3.1
 * @since   2021-07-03
 * @author  Ni Irrty <niirrty+code@gmail.com>
 */


declare( strict_types=1 );


namespace Niirrty\Plate\TagParser;


use \Niirrty\IO\Path;
use \Niirrty\Plate\Compiler;
use \Niirrty\Plate\Config;


class IncludeTagParser extends PlateTagParser
{


    #region // C O N S T R U C T O R

    public function __construct( Config $config )
    {

        parent::__construct( '#', $config );
        $this->_autoIdentify = true;

    }

    #endregion


    #region // P U B L I C   M E T H O D S

    protected function _parse(
        string $tagDefinition, string $afterTagClose = '', string $newLineAfter = '', ?string $package = null  ) : bool
    {

        // {# file/to/include.tpl}
        // or
        // {# $fileFromEngineVariable}

        // Remove the leading # and whitespaces before and after.
        $tagDefinition = \trim( \ltrim( $tagDefinition, '#' ) );

        if ( \preg_match( '~^\\$[A-Za-z_][A-Za-z0-9_]*$~', $tagDefinition ) )
        {

            $this->_compiled = ( new PlateTagCompiled() )
                ->setPhpCode( '<?php $this->includeWithCaching( ' .
                              $tagDefinition .
                              ', ' .
                              \json_encode( $package ) .
                              ' ); ?> ' )
                ->setNewLineAfter( "" )
                ->setAfterTagClose( $afterTagClose );

            return true;

        }

        if ( null !== $package && '' !== $package )
        {
            $tplFile = Path::Combine( $this->_config->getTemplatesFolder(), $package, $tagDefinition );
        }
        else
        {
            $tplFile = Path::Combine( $this->_config->getTemplatesFolder(), $tagDefinition );
        }

        if ( ! \file_exists( $tplFile ) )
        {

            $this->_compiled = ( new PlateTagCompiled() )
                ->setPhpCode( '<!-- PLATE-ERROR: Template ' . $tagDefinition . ' not exists! -->' )
                ->setNewLineAfter( "" )
                ->setAfterTagClose( $afterTagClose );

            return true;

        }

        $comp      = new Compiler( $this->_config );
        $cacheFile = Path::Unixize( $comp->compile( $tagDefinition, $package ) );

        $this->_compiled = ( new PlateTagCompiled() )
            ->setPhpCode( "<?php include '{$cacheFile}'; ?> " )
            ->setNewLineAfter( "" )
            ->setAfterTagClose( $afterTagClose );

        return true;

    }

    #endregion


}

