<?php
/**
 * @author     Ni Irrty <niirrty+code@gmail.com>
 * @copyright  © 2017-2021, Niirrty
 * @package    Niirrty\Plate\Handler
 * @since      2017-11-04
 * @version    0.4.0
 */


declare( strict_types=1 );


namespace Niirrty\Plate\Handler;


/**
 * Class SmileyReplacer
 *
 * @package Niirrty\Plate\Handler
 */
class SmileyReplacer implements IHandler
{


    #region // – – –   P U B L I C   C O N S T R U C T O R   – – – – – – – – – – – – – – – – – – – –

    /**
     * SmileyReplacer constructor.
     *
     * @param array $replacements Smiley as key and image file url as value.
     */
    public function __construct( protected array $replacements ) { }

    #endregion


    #region // – – –   P U B L I C   M E T H O D S   – – – – – – – – – – – – – – – – – – – – – – – –

    /**
     * @param string $contents
     *
     * @return string
     */
    public function execute( string $contents ): string
    {

        if ( \count( $this->replacements ) < 1 )
        {
            return $contents;
        }

        $s = [];
        $r = [];
        foreach ( $this->replacements as $k => $v )
        {
            $s[] = $k;
            $r[] = "<img src=\"{$v}\" alt=\"{$k}\" />";
        }

        return \str_replace( $s, $r, $contents );

    }

    #endregion


}

