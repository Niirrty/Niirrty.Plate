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


use function class_exists;


class XHTMLTidy implements IHandler
{


    // <editor-fold desc="// – – –   P R I V A T E   F I E L D S   – – – – – – – – – – – – – – – – – – – – – – – –">

    private $config;

    // </editor-fold>


    // <editor-fold desc="// – – –   P U B L I C   C O N S T R U C T O R   – – – – – – – – – – – – – – – – – – – –">

    public function __construct(
        string $docType = 'strict', bool $fixUri = false, bool $indent = true, int $indentSpaces = 2 )
    {

        $this->config = [
            'show-body-only'              => false,
            'clean'                       => true,
            'char-encoding'               => 'utf8',
            'add-xml-decl'                => $docType == 'strict',
            'add-xml-space'               => $docType == 'strict',
            'output-html'                 => false,
            'output-xml'                  => false,
            'output-xhtml'                => true,
            'numeric-entities'            => false,
            'ascii-chars'                 => false,
            'doctype'                     => $docType,
            'bare'                        => true,
            'fix-uri'                     => $fixUri,
            'indent'                      => $indent,
            'indent-spaces'               => $indentSpaces,
            'tab-size'                    => 4,
            'wrap-attributes'             => false,
            'wrap'                        => 0,
            'indent-attributes'           => false,
            'join-classes'                => false,
            'join-styles'                 => false,
            'enclose-block-text'          => true,
            'fix-bad-comments'            => true,
            'fix-backslash'               => true,
            'replace-color'               => false,
            'wrap-asp'                    => false,
            'wrap-jste'                   => false,
            'wrap-php'                    => false,
            'write-back'                  => true,
            'drop-proprietary-attributes' => false,
            'hide-comments'               => true,
            'hide-endtags'                => false,
            'literal-attributes'          => false,
            'drop-empty-paras'            => true,
            'enclose-text'                => true,
            'quote-ampersand'             => true,
            'quote-marks'                 => false,
            'quote-nbsp'                  => true,
            'vertical-space'              => true,
            'wrap-script-literals'        => false,
            'tidy-mark'                   => true,
            'merge-divs'                  => false,
            'repeated-attributes'         => 'keep-last',
            'break-before-br'             => true,
            'newline'                     => 'LF',
            'output-bom'                  => false,
        ];

    }

    // </editor-fold>


    // <editor-fold desc="// – – –   P U B L I C   M E T H O D S   – – – – – – – – – – – – – – – – – – – – – – – –">

    /**
     * @param string $contents
     *
     * @return string
     */
    public function execute( string $contents ): string
    {

        if ( !class_exists( '\\tidy' ) )
        {
            return $contents;
        }

        $tidy = new \tidy();
        $out = $tidy->repairString( $contents, $this->config, 'UTF8' );
        unset( $tidy );

        return $out;

    }


    // </editor-fold>


}

