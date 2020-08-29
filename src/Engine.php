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


use ArrayAccess;
use Niirrty\ArgumentException;
use Niirrty\DB\DBException;
use Niirrty\IO\Path;
use Niirrty\Plate\Handler\IHandler;
use function is_null;
use function ob_end_clean;
use function ob_get_contents;
use function ob_start;


class Engine implements ArrayAccess
{


    // <editor-fold desc="// – – –   P R O T E C T E D   F I E L D S   – – – – – – – – – – – – – – – – – – – – – –">


    /** @var array */
    protected $data;

    /** @var Config */
    protected $config;

    // </editor-fold>


    // <editor-fold desc="// – – –   P U B L I C   C O N S T R U C T O R   – – – – – – – – – – – – – – – – – – – –">

    /**
     * Engine constructor.
     *
     * @param Config $config
     */
    public function __construct( Config $config )
    {

        $this->config = $config;
        $this->data = [
            'Engine'    => [
                'Version' => '0.1.0',
                'Name'    => 'Niirrty Plate template engine',
            ],
            '____plate' => '____plate',
        ];

    }

    // </editor-fold>


    // <editor-fold desc="// – – –   P U B L I C   M E T H O D S   – – – – – – – – – – – – – – – – – – – – – – – –">

    public function __get( $name )
    {

        switch ( $name )
        {
            case '_SESSION':
                return $_SESSION;
            case '_SERVER':
                return $_SERVER;
            default:
                if ( isset( $this->data[ $name ] ) )
                {
                    return $this->data[ $name ];
                }

                return '';
        }

    }

    public function assign( string $name, $value ): Engine
    {

        $this->data[ $name ] = $value;

        return $this;

    }

    public function assignMulti( array $data ): Engine
    {

        foreach ( $data as $k => $v )
        {
            $this->data[ $k ] = $v;
        }

        return $this;

    }

    public function __set( $name, $value )
    {

        $this->assign( $name, $value );

    }

    public function __isset( $name )
    {

        return isset( $this->data[ $name ] );

    }

    public function __unset( $name )
    {

        unset( $this->data[ $name ] );

    }

    /**
     * @param string        $tplFile
     * @param string|null   $package
     * @param IHandler|null $handler
     *
     * @return string
     * @throws CompileException
     * @throws ArgumentException
     * @throws DBException
     * @throws \Throwable
     */
    public function parse( string $tplFile, ?string $package = null, ?IHandler $handler = null ): string
    {

        $compiler = new Compiler( $this->config );
        $cacheFile = $compiler->compile( $tplFile, $package );

        ob_start();
        /** @noinspection PhpIncludeInspection */
        include $cacheFile;
        $result = ob_get_contents();
        ob_end_clean();

        if ( !is_null( $handler ) )
        {
            return $handler->execute( $result );
        }

        return $result;

    }

    /**
     * @param string        $tplFile
     * @param string|null   $package
     * @param IHandler|null $handler
     *
     * @throws ArgumentException
     * @throws CompileException
     * @throws DBException
     * @throws \Throwable
     */
    public function display( string $tplFile, ?string $package = null, ?IHandler $handler = null )
    {

        $compiler = new Compiler( $this->config );
        $cacheFile = $compiler->compile( $tplFile, $package );

        if ( null === $handler )
        {
            /** @noinspection PhpIncludeInspection */
            include $cacheFile;

            return;
        }

        ob_start();
        /** @noinspection PhpIncludeInspection */
        include $cacheFile;
        $result = ob_get_contents();
        ob_end_clean();

        echo $handler->execute( $result );

    }

    // </editor-fold>


    // <editor-fold desc="// - - - ArrayAccess Implementation - - - - - - - - - - - - - - - - - - - - - -">

    public function offsetExists( $offset )
    {

        return isset ( $this->data[ $offset ] );
    }

    public function offsetGet( $offset )
    {

        return $this->data[ $offset ];
    }

    public function offsetSet( $offset, $value )
    {

        $this->data[ $offset ] = $value;
    }

    public function offsetUnset( $offset )
    {

        unset ( $this->data[ $offset ] );
    }

    // </editor-fold>

    /**
     * @param string      $templateFile
     * @param string|null $package
     *
     * @throws ArgumentException
     * @throws CompileException
     * @throws DBException
     * @throws \Throwable
     */
    protected function includeWithCaching( string $templateFile, ?string $package = null )
    {

        $comp = new Compiler( $this->config );
        $cacheFile = Path::Unixize( $comp->compile( $templateFile, $package ) );
        /** @noinspection PhpIncludeInspection */
        include $cacheFile;
    }


}

