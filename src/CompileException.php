<?php
/**
 * @author     Ni Irrty <niirrty+code@gmail.com>
 * @copyright  © 2017-2021, Ni Irrty
 * @package    Niirrty\Plate
 * @since      2017-11-04
 * @version    0.4.0
 */

declare( strict_types=1 );


namespace Niirrty\Plate;


class CompileException extends PlateException
{


    #region // – – –   P U B L I C   C O N S T R U C T O R   – – – – – – – – – – – – – – – – – – – –

    /**
     * Initialize a new \Niirrty\Plate\CompileException instance.
     *
     * @param string          $templateFile
     * @param string          $message
     * @param int             $code
     * @param \Throwable|null $previous
     */
    public function __construct( private string $templateFile, string $message, int $code = 256, ?\Throwable $previous = null )
    {

        parent::__construct(
            'Error while compiling template file "' . $templateFile . '"! ' . $message,
            $code,
            $previous
        );

    }

    #endregion


    #region // – – –   P U B L I C   M E T H O D S   – – – – – – – – – – – – – – – – – – – – – – – –

    /**
     * @return string
     */
    public function getTemplateFile(): string
    {

        return $this->templateFile;

    }


    #endregion


}

