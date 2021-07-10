<?php
/**
 * @package ${NAMESPACE}
 * @version 0.3.1
 * @since   2021-07-10
 * @author  Ni Irrty <niirrty+code@gmail.com>
 */


declare( strict_types=1 );


class MyHelpTagParser extends \Niirrty\Plate\TagParser\PlateTagParser
{


    #region // P R I V A T E   F I E L D S

    /**
     * The …
     *
     * @var mixed
     */
    private $_linkHTML;

    #endregion


    #region // C O N S T R U C T O R

    public function __construct( \Niirrty\Plate\Config $config )
    {

        // Use the ? identifier
        parent::__construct( '?', $config );

        // Enable automatic check of the tag definition, if it starts with the defined identifier.
        $this->_autoIdentify = true;

    }

    #endregion


    #region // P R O T E C T E D   M E T H O D S

    protected function _parse(
        string $tagDefinition, string $afterTagClose = '', string $newLineAfter = '', ?string $package = null ): bool
    {

        // Getting HelpId and OptionalAnchorName
        $attributes = \explode( '#', \trim( \ltrim( $tagDefinition, "? " ) ), 2 );
        $id         = \intval( $attributes[ 0 ] );
        $anchor     = ! empty( $attributes[ 1 ] ) ? \trim( $attributes[ 1 ] ) : null;

        $cmd  = 'generateHelpLinkHTML( '
              . $id
              . ', ';
        $cmd .= ( ! empty( $anchor ) )
              ? ( '\'' . \str_replace( "'", '', $anchor ) . '\');' )
              : 'null);';

        $this->_compiled = ( new \Niirrty\Plate\TagParser\PlateTagCompiled() )
            ->setPhpCode( '<' . '?php echo ' . $cmd . ' ?>' )
            ->setNewLineAfter( " \n" )
            ->setAfterTagClose( $afterTagClose );

        return true;

    }

    #endregion


}

function generateHelpLinkHTML( int $id, ?string $anchor ) : string
{

    $links = [
        1 => [
            'url' => 'https://example.com/help/foo/',
            'text' => 'The link text',
            'title' => 'A optional link title text'
        ],
        8 => [
            'url' => 'https://example.com/help/bar/',
            'text' => 'The other link text',
            'title' => 'A other optional link title text'
        ]
    ];

    $tag = '<a href="';

    if ( ! isset( $links[ $id ] ) )
    {
        $tag .= '" title="Missing link...">Missing link (' . $id . ')</a>';
    }
    else
    {
        $tag .= $links[ $id ][ 'url' ];
        if ( ! empty( $anchor ) )
        {
            $tag .= '#' . \urlencode( $anchor );
        }
        $tag .= '"';
        if ( ! empty( $links[ $id ][ 'title' ] ) )
        {
            $tag .= ' title="' . \htmlspecialchars( $links[ $id ][ 'title' ] ) . '"';
        }
        $tag .= '>' . \htmlspecialchars( $links[ $id ][ 'text' ] ) . '</a> ';
    }

    return $tag;

}

