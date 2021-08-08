<?php
/**
 * @package Niirrty\Plate\TagParser
 * @version 0.4.0
 * @since   2021-07-03
 * @author  Ni Irrty <niirrty+code@gmail.com>
 */


declare( strict_types=1 );


namespace Niirrty\Plate\TagParser;


use Niirrty\ArrayHelper;
use Niirrty\Plate\Config;


class VarOutTagParser extends PlateTagParser
{


    #region // C O N S T R U C T O R

    public function __construct( Config $config )
    {

        parent::__construct( '$', $config );
        $this->_autoIdentify = true;

    }

    #endregion


    #region // P U B L I C   M E T H O D S

    protected function _parse(
        string $tagDefinition, string $afterTagClose = '', string $newLineAfter = '', ?string $package = null  ) : bool
    {

        // Split into var and filter
        $tmp = \preg_split( '~(?<!\\\\)\\|~', $tagDefinition );
        $var = $tagDefinition;

        if ( \count( $tmp ) > 1 )
        {

            // There are one or more filters defined

            // Get the variable name
            $var = static::normalizeVar( trim( $tmp[ 0 ] ) );

            // Get the filters and reverse them: e.g. trim|escape:'htmlall' => escape:'htmlall'|trim
            $filters = \array_reverse( ArrayHelper::Remove( $tmp, 0 ) );

            $this->_compiled =( new PlateTagCompiled() )
                ->setPhpCode( '<?php echo ' . static::setFlt( $filters, $var ) . ' ?>' )
                ->setAfterTagClose( $afterTagClose );

            return true;

        }
        else
        {

            $var = static::normalizeVar( $var );

        }

        $this->_compiled =( new PlateTagCompiled() )
            ->setPhpCode( "<?= {$var}; ?>" )
            ->setNewLineAfter( " \n" )
            ->setAfterTagClose( $afterTagClose );

        return true;

    }

    #endregion


}

