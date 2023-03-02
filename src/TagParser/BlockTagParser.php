<?php
/**
 * @package Niirrty\Plate\TagParser
 * @version 0.4.0
 * @since   2021-07-03
 * @author  Ni Irrty <niirrty+code@gmail.com>
 */


declare( strict_types=1 );


namespace Niirrty\Plate\TagParser;


use \Niirrty\ArrayHelper;
use \Niirrty\Plate\Config;


class BlockTagParser extends PlateTagParser
{


    #region // C O N S T R U C T O R

    public function __construct( Config $config )
    {

        parent::__construct( 'e|f|i', $config );
        $this->_autoIdentify = false;

    }

    #endregion


    #region // P U B L I C   M E T H O D S

    protected function _parse(
        string $tagDefinition, string $afterTagClose = '', string $newLineAfter = '', ?string $package = null  ) : bool
    {

        if ( ! \preg_match( '~^(if|else|elseif|end|for|foreach)~i', $tagDefinition ) )
        {
            return false;
        }

        if ( 'end' === $tagDefinition )
        {
            $this->_compiled = ( new PlateTagCompiled() )
                ->setPhpCode( '<'.'?php } ?>' )
                ->setNewLineAfter( " \n" )
                ->setAfterTagClose( $afterTagClose );
            return true;
        }

        if ( $tagDefinition == 'else' )
        {
            $this->_compiled = ( new PlateTagCompiled() )
                ->setPhpCode( '<' . '?php } else { ?>' )
                ->setNewLineAfter( " \n" )
                ->setAfterTagClose( $afterTagClose );
            return true;
        }

        $tmp = \explode( ' ', $tagDefinition, 2 );

        if ( 2 !== \count( $tmp ) )
        {
            $this->_compiled = ( new PlateTagCompiled() )
                ->setPhpCode( $this->config->getOpenChars() . $tagDefinition . $this->config->getCloseChars() )
                ->setAfterTagClose( $afterTagClose );
            return true;
        }

        $tmp[ 0 ] = \strtolower( $tmp[ 0 ] );

        if ( 'foreach' === $tmp[ 0 ] )
        {
            // foreach from=$Varname key=key value=value
            $attr = ArrayHelper::ParseHtmlAttributes( $tmp[ 1 ] );
            if ( !isset( $attr[ 'from' ], $attr[ 'value' ] ) )
            {
                $this->_compiled = ( new PlateTagCompiled() )
                    ->setPhpCode( $this->config->getOpenChars() . $tagDefinition . $this->config->getCloseChars() )
                    ->setAfterTagClose( $afterTagClose );
                return true;
            }
            $attr[ 'from' ] = '$' . \ltrim( \trim( static::normalizeVar( $attr[ 'from' ] ) ), '$' );
            $attr[ 'value' ] = '$' . \ltrim( \trim( $attr[ 'value' ] ), '$' );
            if ( isset( $attr[ 'key' ] ) )
            {
                $attr[ 'key' ] = ( '$' . \ltrim( \trim( $attr[ 'key' ] ) ) );
                $attr[ 'as' ] = $attr[ 'key' ] . ' => ' . $attr[ 'value' ];
            }
            else
            {
                $attr[ 'key' ] = '';
                $attr[ 'as' ] = $attr[ 'value' ];
            }
            $this->_compiled = ( new PlateTagCompiled() )
                ->setPhpCode( "<?php foreach( {$attr['from']} as {$attr['as']} ) { ?>" )
                ->setAfterTagClose( $afterTagClose );
            return true;
        }

        if ( 'for' === $tmp[ 0 ] )
        {
            // for from=$array index=i count=c step=1 init=0
            $attr = ArrayHelper::ParseHtmlAttributes( $tmp[ 1 ] );
            if ( !isset( $attr[ 'from' ] ) )
            {
                $this->_compiled = ( new PlateTagCompiled() )
                    ->setPhpCode( $this->config->getOpenChars() . $tagDefinition . $this->config->getCloseChars() )
                    ->setAfterTagClose( $afterTagClose );
                return true;
            }
            $attr[ 'from' ] = '$' . \ltrim( \trim( static::normalizeVar( $attr[ 'from' ] ) ), '$' );
            $attr[ 'index' ] = isset( $attr[ 'index' ] ) ? ( '$' . \ltrim( \trim( $attr[ 'index' ] ), '$' ) ) : '$i';
            $attr[ 'count' ] = isset( $attr[ 'count' ] ) ? ( '$' . \ltrim( \trim( $attr[ 'count' ] ), '$' ) ) : '$c';
            $attr[ 'step' ] = isset( $attr[ 'step' ] ) ? (int) $attr[ 'step' ] : 1;
            $attr[ 'reverse' ] = false;
            if ( 0 === $attr[ 'step' ] )
            {
                $attr[ 'step' ] = 1;
            }
            if ( 0 > $attr[ 'step' ] )
            {
                $attr[ 'step' ] = \abs( $attr[ 'step' ] );
                $attr[ 'reverse' ] = true;
            }

            if ( $attr[ 'reverse' ] )
            {
                // for ( $i = \count( $from ); $i > -1; $i-- ) {
                $dec = ( 1 === $attr[ 'step' ] )
                    ? ( $attr[ 'index' ] . '--' )
                    : $attr[ 'index' ] . ' -= ' . $attr[ 'step' ];
                $this->_compiled = ( new PlateTagCompiled() )
                    ->setPhpCode(
                        "<" . "?php for( {$attr['index']} = count( {$attr['from']} ); {$attr['index']} > -1; {$dec} ) { ?" . ">" )
                    ->setAfterTagClose( $afterTagClose );
                return true;
            }

            $attr[ 'init' ] = isset( $attr[ 'init' ] ) ? ( (int) $attr[ 'init' ] ) : 0;
            // for ( $i = 0, $c = \count( $from ); $i < 0; $i++ ) {
            $inc = ( 1 === $attr[ 'step' ] )
                ? ( $attr[ 'index' ] . '++' )
                : $attr[ 'index' ] . ' += ' . $attr[ 'step' ];
            $this->_compiled = ( new PlateTagCompiled() )
                ->setPhpCode(
                    "<" . "?php for( {$attr['index']} = {$attr['init']}, {$attr['count']} = count( {$attr['from']} ); " .
                    "{$attr['index']} < {$attr['count']}; {$inc} ) { ?>" )
                ->setAfterTagClose( $afterTagClose );
            return true;
        }
        
        $tmp[ 1 ] = \trim( $tmp[ 1 ] );

        if ( 'elseif' === $tmp[ 0 ] )
        {
            $this->_compiled = ( new PlateTagCompiled() )
                 ->setPhpCode( "<" . "?php } else if ( {$tmp[1]} ) { ?>" )
                 ->setAfterTagClose( $afterTagClose );
            return true;
        }

        $this->_compiled = ( new PlateTagCompiled() )
             ->setPhpCode( "<" . "?php {$tmp[0]} ( {$tmp[1]} ) { ?>" )
             ->setAfterTagClose( $afterTagClose );
        return true;

    }

    #endregion


}

