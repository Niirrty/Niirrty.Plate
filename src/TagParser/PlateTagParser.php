<?php
/**
 * @package Niirrty\Plate\TagParser
 * @version 0.4.0
 * @since   2021-07-03
 * @author  Ni Irrty <niirrty+code@gmail.com>
 */


declare( strict_types=1 );


namespace Niirrty\Plate\TagParser;


use Niirrty\Plate\Config;


abstract class PlateTagParser implements IPlateTagParser
{


    #region // P R O T E C T E D   F I E L D S

    /**
     * Defines if a check for identifier ($this->hasIdentifier()) should be called automatic.
     *
     * @var bool
     */
    protected bool $_autoIdentify;

    /**
     * All parts of the tag, excluding open chars, close chars, and identifier
     *
     * @var array
     */
    protected array $_data;

    /**
     * The names of the filters.
     *
     * @var array
     */
    protected array $_filter;

    /**
     * @var PlateTagCompiled|null
     */
    protected ?PlateTagCompiled $_compiled;

    #endregion


    #region // C O N S T R U C T O R

    /**
     * PlateTagParser constructor.
     *
     * @param string $identifier The tag identifier character (only a single character is accepted)
     * @param Config $config
     */
    protected function __construct( protected string $identifier, protected Config $config )
    {

        $this->_autoIdentify = true;
        $this->_data = [];
        $this->_filter = [];
        $this->_compiled = null;

    }

    #endregion


    #region // G E T T E R   M E T H O D S

    /** @inheritDoc */
    public function getIdentifier() : string
    {

        return $this->identifier;

    }

    /** @inheritDoc */
    public function getData() : array
    {

        return $this->_data;

    }

    /** @inheritDoc */
    public function getFilters() : array
    {

        return $this->_filter;

    }

    /** @inheritDoc */
    public function isParsed() : bool
    {

        return null !== $this->_compiled;

    }

    /** @inheritDoc */
    public function getCompiled() : ?PlateTagCompiled
    {

        return $this->_compiled;

    }

    #endregion

    public final function parse(
        string $tagDefinition, string $afterTagClose = '', string $newLineAfter = '', ?string $package = null ) : bool
    {

        if ( $this->_autoIdentify )
        {
            if ( $this->identifier != $tagDefinition[ 0 ] )
            {
                return false;
            }
        }

        return $this->_parse( $tagDefinition, $afterTagClose, $newLineAfter, $package );

    }

    #region // P R O T E C T E D   S T A T I C   M E T H O D S

    protected static function normalizeVar( string $varDefinition ): string
    {

        $parts = \preg_split( '~([."\'-])~', $varDefinition, -1, PREG_SPLIT_DELIM_CAPTURE );

        $partsCount = \count( $parts );

        if ( 2 > $partsCount )
        {
            return $varDefinition;
        }

        $tmp = [];

        for ( $i = 0, $j = 0; $i < $partsCount; $i += 2 )
        {

            if ( $i === 0 )
            {
                $tmp[] = [ 'operator' => false, 'parts' => [ \trim( $parts[ $i ] ) ] ];
                continue;
            }

            switch ( $parts[ $i - 1 ] )
            {

                case '.':
                    if ( '.' === $tmp[ $j ][ 'operator' ] )
                    {
                        $tmp[ $j ][ 'parts' ][] = \trim( $parts[ $i ] );
                        break;
                    }
                    if ( '"' === $tmp[ $j ][ 'operator' ] || '\'' === $tmp[ $j ][ 'operator' ] )
                    {
                        $tmp[ $j ][ 'parts' ][] = $parts[ $i - 1 ];
                        $tmp[ $j ][ 'parts' ][] = $parts[ $i ];
                        break;
                    }
                    $j++;
                    $tmp[] = [ 'operator' => '.', 'parts' => [ \trim( $parts[ $i ] ) ] ];
                    break;

                case '-':
                    if ( false === $tmp[ $j ][ 'operator' ] )
                    {
                        $tmp[ $j ][ 'parts' ][] = \trim( $parts[ $i - 1 ] );
                        $tmp[ $j ][ 'parts' ][] = \trim( $parts[ $i ] );
                        break;
                    }
                    if ( '"' === $tmp[ $j ][ 'operator' ] || '\'' === $tmp[ $j ][ 'operator' ] )
                    {
                        $tmp[ $j ][ 'parts' ][] = $parts[ $i - 1 ];
                        $tmp[ $j ][ 'parts' ][] = $parts[ $i ];
                        break;
                    }
                    $j++;
                    $tmp[] = [ 'operator' => false, 'parts' => [ \trim( $parts[ $i - 1 ] ) ] ];
                    $tmp[ $j ][ 'parts' ][] = \trim( $parts[ $i ] );
                    break;

                case '"':
                    if ( '"' === $tmp[ $j ][ 'operator' ] )
                    {
                        // Current " maybe close the open string "…
                        if ( static::strEndsWithEscapeChar( $tmp[ $j ][ 'parts' ][ \count( $tmp[ $j ][ 'parts' ] ) - 1 ] ) )
                        {
                            // The " is escaped
                            $tmp[ $j ][ 'parts' ][] = $parts[ $i - 1 ];
                            $tmp[ $j ][ 'parts' ][] = $parts[ $i ];
                            break;
                        }
                        // Current " closes the open string "…
                        $tmp[ $j ][ 'operator' ] = false;
                        $tmp[ $j ][ 'parts' ][] = $parts[ $i - 1 ];
                        $j++;
                        $tmp[] = [ 'operator' => false, 'parts' => [ \trim( $parts[ $i ] ) ] ];
                        break;
                    }
                    // Current " opens a new string
                    $j++;
                    $tmp[] = [ 'operator' => '"', 'parts' => [ $parts[ $i - 1 ], $parts[ $i ] ] ];
                    break;

                case '\'':
                    if ( '\'' === $tmp[ $j ][ 'operator' ] )
                    {
                        // Current ' maybe close the open string '…
                        if ( static::strEndsWithEscapeChar( $tmp[ $j ][ 'parts' ][ \count( $tmp[ $j ][ 'parts' ] ) - 1 ] ) )
                        {
                            // The ' is escaped
                            $tmp[ $j ][ 'parts' ][] = $parts[ $i - 1 ];
                            $tmp[ $j ][ 'parts' ][] = $parts[ $i ];
                            break;
                        }
                        // Current ' close the open string '…
                        $tmp[ $j ][ 'parts' ][] = $parts[ $i - 1 ];
                        $tmp[ $j ][ 'operator' ] = false;
                        $j++;
                        $tmp[] = [ 'operator' => false, 'parts' => [ $parts[ $i ] ] ];
                        break;
                    }
                    // Current ' opens a new string
                    $j++;
                    $tmp[] = [ 'operator' => '\'', 'parts' => [ $parts[ $i - 1 ], $parts[ $i ] ] ];
                    break;

            }

        }

        $normalized = '';
        foreach ( $tmp as $partsGroup )
        {

            if ( false === $partsGroup[ 'operator' ] )
            {
                $normalized .= \implode( '', $partsGroup[ 'parts' ] );
                continue;
            }

            for ( $i = 0, $c = \count( $partsGroup[ 'parts' ] ); $i < $c; $i++ )
            {
                if ( '' !== $partsGroup[ 'parts' ][ $i ] &&
                     ( '$' === $partsGroup[ 'parts' ][ $i ][ 0 ] || \is_numeric( $partsGroup[ 'parts' ][ $i ] ) ) )
                {
                    continue;
                }
                $partsGroup[ 'parts' ][ $i ] = \json_encode( $partsGroup[ 'parts' ][ $i ] );
            }

            $normalized .= '[' . \implode( '][', $partsGroup[ 'parts' ] ) . ']';

        }

        return $normalized;

    }

    protected static function strEndsWithEscapeChar( string $str ): bool
    {

        if ( ! \preg_match( '~(\\\\+)$~', $str, $matches ) )
        {
            return false;
        }
        $backSlashCount = \strlen( $matches[ 1 ] );

        return 0 !== $backSlashCount && 0 !== ( $backSlashCount % 2 );

    }

    protected static function setFlt( array $filters, string $command ) : string
    {

        // Set default filter `escape`, if no filter is defined
        if ( 1 > \count( $filters ) )
        {
            $filters = [ 'escape' ];
        }

        $appendix = '';
        $contents = '';

        // Loop all defined filters
        foreach ( $filters as $filter )
        {
            switch ( \strtolower( $filter ) )
            {
                case 'escape':
                case 'escape-html':
                case 'escapehtml':
                    $filter = '\\Niirrty\\escapeXML';
                    break;
                case 'asit':
                    $filter = '';
                    break;
                case 'asjson':
                    $filter = '\\json_encode';
                    break;
                default:
                    if ( ! \function_exists( $filter ) )
                    {
                        $filter = '';
                    }
                    break;
            }
            if ( '' === $filter )
            {
                continue;
            }
            $appendix .= ' )';
            if ( $filter[ 0 ] !== '\\' )
            {
                $contents .= '\\';
            }
            $contents .= "{$filter}( ";
        }
        $contents .= "{$command}{$appendix};";

        return $contents;

    }

    protected function hasIdentifier( string $tagDefinition ) : bool
    {

        return \str_starts_with( $tagDefinition, $this->identifier );

    }

    protected abstract function _parse(
        string $tagDefinition, string $afterTagClose = '', string $newLineAfter = '', ?string $package = null ): bool;

    #endregion


}

