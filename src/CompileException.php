<?php
/**
 * @author     Ni Irrty <niirrty+code@gmail.com>
 * @copyright  © 2017-2020, Ni Irrty
 * @package    Niirrty\Plate
 * @since      2017-11-04
 * @version    0.3.0
 */

declare( strict_types=1 );


namespace Niirrty\Plate;


use \Throwable;


class CompileException extends PlateException
{


    // <editor-fold desc="// – – –   P R I V A T E   F I E L D S   – – – – – – – – – – – – – – – – – – – – – – – –">


    /**
     * @type string
     */
    private $_tplFile;

    // </editor-fold>


    // <editor-fold desc="// – – –   P U B L I C   C O N S T R U C T O R   – – – – – – – – – – – – – – – – – – – –">

    /**
     * Initialize a new \Niirrty\Plate\CompileException instance.
     *
     * @param string          $templateFile
     * @param string          $message
     * @param int             $code
     * @param Throwable|null $previous
     */
    public function __construct( string $templateFile, string $message, int $code = 256, Throwable $previous = null )
    {

        parent::__construct(
            'Error while compiling template file "' . $templateFile . '"! ' . $message,
            $code,
            $previous
        );

        $this->_tplFile = $templateFile;

    }

    // </editor-fold>

    // <editor-fold desc="// – – –   P U B L I C   M E T H O D S   – – – – – – – – – – – – – – – – – – – – – – – –">

    /**
     * @return string
     */
    public function getTemplateFile(): string
    {

        return $this->_tplFile;

    }


    // </editor-fold>


}

