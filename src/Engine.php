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


use \Niirrty\ArgumentException;
use \Niirrty\DB\DBException;
use \Niirrty\IO\Path;
use \Niirrty\Plate\Handler\IHandler;
use \Niirrty\Plate\TagParser\IPlateTagParser;
use \Niirrty\Translation\Translator;


class Engine implements \ArrayAccess
{


    #region // – – –   P R O T E C T E D   F I E L D S   – – – – – – – – – – – – – – – – – – – – – –


    /** @var array */
    protected array $data;

    protected array $userDefinedTagParser = [];

    #endregion


    #region // – – –   P U B L I C   C O N S T R U C T O R   – – – – – – – – – – – – – – – – – – – –

    /**
     * Engine constructor.
     *
     * @param Config $config
     * @param Translator|null $translator
     */
    public function __construct( protected Config $config, protected ?Translator $translator = null )
    {

        $this->data = [
            'Engine'    => [
                'Version' => '0.1.0',
                'Name'    => 'Niirrty Plate template engine',
            ],
            '____plate' => '____plate',
        ];

    }

    #endregion


    #region // – – –   P U B L I C   M E T H O D S   – – – – – – – – – – – – – – – – – – – – – – – –

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
     * Registers a user defined template tag parser, used for compiling.
     *
     * @param IPlateTagParser $parser
     * @return Engine
     */
    public function registerTagParser( IPlateTagParser $parser ) : Engine
    {

        $this->userDefinedTagParser[] = $parser;

        return $this;

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

        foreach ( $this->userDefinedTagParser as $tagParser )
        {
            $compiler->registerTagParser( $tagParser );
        }

        $cacheFile = $compiler->compile( $tplFile, $package );

        \ob_start();
        include $cacheFile;
        $result = \ob_get_contents();
        \ob_end_clean();

        if ( !\is_null( $handler ) )
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
    public function display( string $tplFile, ?string $package = null, ?IHandler $handler = null ) : void
    {

        $compiler = new Compiler( $this->config );

        foreach ( $this->userDefinedTagParser as $tagParser )
        {
            $compiler->registerTagParser( $tagParser );
        }

        $cacheFile = $compiler->compile( $tplFile, $package );

        if ( null === $handler )
        {
            include $cacheFile;
            return;
        }

        \ob_start();
        include $cacheFile;
        $result = \ob_get_contents();
        \ob_end_clean();

        echo $handler->execute( $result );

    }

    #endregion


    #region // - - - ArrayAccess Implementation - - - - - - - - - - - - - - - - - - - - - -

    public function offsetExists( mixed $offset ): bool
    {

        return isset ( $this->data[ $offset ] );
    }

    public function offsetGet( mixed $offset ) : mixed
    {

        return $this->data[ $offset ];
    }

    public function offsetSet( mixed $offset, mixed $value ) : void
    {

        $this->data[ $offset ] = $value;
    }

    public function offsetUnset( mixed $offset ) : void
    {

        unset ( $this->data[ $offset ] );
    }

    #endregion

    /**
     * @param string      $templateFile
     * @param string|null $package
     *
     * @throws ArgumentException
     * @throws CompileException
     * @throws DBException
     * @throws \Throwable
     */
    protected function includeWithCaching( string $templateFile, ?string $package = null ) : void
    {

        $comp = new Compiler( $this->config );
        $cacheFile = Path::Unixize( $comp->compile( $templateFile, $package ) );
        include $cacheFile;
    }


}

