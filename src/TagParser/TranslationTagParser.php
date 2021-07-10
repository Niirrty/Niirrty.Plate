<?php
/**
 * @package Niirrty\Plate\TagParser
 * @version 0.3.1
 * @since   2021-07-03
 * @author  Ni Irrty <niirrty+code@gmail.com>
 */


declare( strict_types=1 );


namespace Niirrty\Plate\TagParser;


use \Niirrty\Plate\Config;
use function \Niirrty\strStartsWith;
use function \Niirrty\substring;


class TranslationTagParser extends PlateTagParser
{


    #region // C O N S T R U C T O R

    public function __construct( Config $config )
    {

        parent::__construct( '~', $config );
        $this->_autoIdentify = true;

    }

    #endregion


    #region // P U B L I C   M E T H O D S

    protected function _parse(
        string $tagDefinition, string $afterTagClose = '', string $newLineAfter = '', ?string $package = null  ) : bool
    {

        // {~ file/to/include.tpl}
        // or
        // {# $fileFromEngineVariable}

        // Remove the leading ~ and whitespaces before and after.

        $clearTagDefinition = \trim( substring( $tagDefinition, 2 ) );

        if ( ! \preg_match( '/^(\\$?[a-zA-Z0-9_]+)::(\\$?[a-zA-Z0-9 _,:.;-]+)(=([^|]+))?(\\|(.+))?$/', $clearTagDefinition, $matches ) )
        {
            return false;
        }

        $srcName      = \trim( $matches[ 1 ] );
        $identifier   = \trim( $matches[ 2 ] );
        $defaultTrans = '';
        $handler      = '';
        $isSnVar      = strStartsWith( $srcName, '$' );
        $isIdVar      = strStartsWith( $identifier, '$' );

        if ( $isSnVar ) { $srcName = static::normalizeVar( \trim( $srcName ) ); }
        else            { $srcName = \json_encode( $srcName ); }
        if ( $isIdVar ) { $identifier = static::normalizeVar( \trim( $identifier ) ); }
        else            { $identifier = \json_encode( $identifier ); }
        if ( ! empty( $matches[ 4 ] ) )
        {
            $defaultTrans = $matches[ 4 ];
            $isDtVar      = strStartsWith( $defaultTrans, '$' );
            if ( $isDtVar ) { $defaultTrans = static::normalizeVar( \trim( $defaultTrans ) ); }
            else            { $defaultTrans = \json_encode( $defaultTrans ); }
        }
        if ( ! empty( $matches[ 6 ] ) )
        {
            $handler = \trim( $matches[ 6 ] );
        }
        $cmd = '( $hasTranslator ? $this->translator->read( '
               . "{$identifier}, {$srcName}"
               . ( ! empty( $defaultTrans ) ? ( ', ' . $defaultTrans ) : '' )
               . ' ) : '
               . ( ! empty( $defaultTrans ) ? $defaultTrans : '\'\'' )
               . ' )';

        $filters = \preg_split( '~(?<!\\\\)\\|~', $handler );
        $filters = \array_reverse( $filters );

        $this->_compiled = ( new PlateTagCompiled() )
            ->setPhpCode( '<?php echo ' . static::setFlt( $filters, $cmd ) . ' ?>' )
            ->setNewLineAfter( " \n" )
            ->setAfterTagClose( $afterTagClose );

        return true;

    }

    #endregion


}

