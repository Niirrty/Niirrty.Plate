<?php
/**
 * @package Niirrty\Plate\TagParser
 * @version 0.4.0
 * @since   2021-07-04
 * @author  Ni Irrty <niirrty+code@gmail.com>
 */


declare( strict_types=1 );


namespace Niirrty\Plate\TagParser;


class PlateTagCompiled
{


    #region // P R I V A T E   F I E L D S

    /** @var string */
    private string $_phpCode;

    /** @var string */
    private string $_afterTagClose;

    /** @var string */
    private string $_newLineAfter;

    #endregion


    #region // C O N S T R U C T O R

    public function __construct()
    {

        $this->_phpCode       = '';
        $this->_afterTagClose = '';
        $this->_newLineAfter  = '';

    }

    #endregion


    #region // G E T T E R   M E T H O D S

    /**
     * @return string
     */
    public function getPhpCode() : string
    {

        return $this->_phpCode;

    }

    /**
     * @return string
     */
    public function getAfterTagClose() : string
    {

        return $this->_afterTagClose;

    }

    /**
     * @return string
     */
    public function getNewLineAfter() : string
    {

        return $this->_newLineAfter;

    }

    #endregion


    #region // S E T T E R   M E T H O D S

    /**
     * @param string $code
     * @return PlateTagCompiled
     */
    public function setPhpCode( string $code ) : PlateTagCompiled
    {

        $this->_phpCode = $code;

        return $this;

    }

    /**
     * @param string $afterTagClose
     * @return PlateTagCompiled
     */
    public function setAfterTagClose( string $afterTagClose ) : PlateTagCompiled
    {

        $this->_afterTagClose = $afterTagClose;

        return $this;

    }

    /**
     * @param string $newLineAfter
     * @return PlateTagCompiled
     */
    public function setNewLineAfter( string $newLineAfter ) : PlateTagCompiled
    {

        $this->_newLineAfter = $newLineAfter;

        return $this;

    }

    #endregion


}

