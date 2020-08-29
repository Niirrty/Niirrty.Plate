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


use Niirrty\ArgumentException;
use Niirrty\IO\File;
use Niirrty\IO\FileNotFoundException;
use Niirrty\IO\IOException;
use Niirrty\IO\Vfs\VfsManager;
use SimpleXMLElement;
use Throwable;
use function file_get_contents;
use function in_array;
use function intval;
use function is_array;
use function is_dir;
use function is_numeric;
use function is_string;
use function is_writable;
use function json_decode;
use function ltrim;
use function max;
use function min;
use function simplexml_load_file;
use function strtolower;
use function trim;


class Config
{


    // <editor-fold desc="// – – –   P R I V A T E   F I E L D S   – – – – – – – – – – – – – – – – – – – – – – – –">


    /**
     * The config data.
     *
     * @var array
     */
    private $_data = null;

    /**
     * Path of the loaded config file.
     *
     * @var null|string
     */
    private $_file;

    /**
     * The config file type. (see FILE_TYPE_* class constants)
     *
     * @var null|string
     */
    private $_type;

    /**
     * @var Config|null
     */
    private static $_instance = null;

    // </editor-fold>


    // <editor-fold desc="// – – –   C O N S T A N T S   – – – – – – – – – – – – – – – – – – – – – – – – – – – – –">

    /**
     * The default settings
     */
    public const DEFAULTS = [
        'openChars'       => '{',
        'closeChars'      => '}',
        'templatesFolder' => null,
        'cache'           => [
            'mode'     => 'user',
            'folder'   => null,
            'lifetime' => 60,
        ],
    ];

    /**
     * Configuration by a PHP file.
     */
    public const FILE_TYPE_PHP = 'php';

    /**
     * Configuration by a JSON file.
     */
    public const FILE_TYPE_JSON = 'json';

    /**
     * Configuration by a INI file.
     */
    public const FILE_TYPE_INI = 'ini';

    /**
     * Configuration by a XML file.
     */
    public const FILE_TYPE_XML = 'xml';

    /**
     * Defines a numeric indicated array with all known file types.
     */
    public const KNOWN_FILE_TYPES = [
        self::FILE_TYPE_INI, self::FILE_TYPE_JSON, self::FILE_TYPE_PHP, self::FILE_TYPE_XML,
    ];

    /**
     * Use the current defined cache settings.
     */
    public const CACHE_MODE_USER = 'user';

    /**
     * Ignore all cache settings and rebuild all required caches if a template is used.
     */
    public const CACHE_MODE_EDITOR = 'editor';

    /**
     * All known cache modes
     */
    public const KNOWN_CACHE_MODES = [ self::CACHE_MODE_USER, self::CACHE_MODE_EDITOR ];

    // </editor-fold>


    // <editor-fold desc="// – – –   P U B L I C   C O N S T R U C T O R   – – – – – – – – – – – – – – – – – – – –">

    /**
     * Initialize a new \Niirrty\Plate\Config instance.
     *
     * @param array       $data The config data array. NULL also means => use the DEFAULTS
     * @param null|string $file If a file was used to get the data, define it here.
     *
     * @throws ArgumentException
     */
    public function __construct( ?array $data = self::DEFAULTS, ?string $file = null )
    {

        $this->setData( $data );
        $this->_file = $file;
        $this->_type = static::_getFileType( $file );

    }

    // </editor-fold>


    // <editor-fold desc="// – – –   P U B L I C   M E T H O D S   – – – – – – – – – – – – – – – – – – – – – – – –">


    // <editor-fold desc="// * * * * * *   G E T T E R   * * * * * *">

    /**
     * Gets the config data.
     *
     * @return array
     */
    public function getData(): array
    {

        return $this->_data;

    }

    /**
     * Gets the path of the loaded config file.
     *
     * @return null|string
     */
    public function getFile(): ?string
    {

        return $this->_file;

    }

    /**
     * Gets the config file type. (see FILE_TYPE_* class constants)
     *
     * @return null|string
     */
    public function getType(): ?string
    {

        return $this->_type;

    }

    /**
     * Gets the templates folder.
     *
     * @return null|string
     */
    public function getTemplatesFolder(): ?string
    {

        return $this->_data[ 'templatesFolder' ];

    }

    /**
     * Gets the template tag start character sequence. The default value is `{`.
     *
     * @return string
     */
    public function getOpenChars(): string
    {

        return $this->_data[ 'openChars' ];

    }

    /**
     * Gets the template tag end character sequence. The default value is `}`.
     *
     * @return string
     */
    public function getCloseChars(): string
    {

        return $this->_data[ 'closeChars' ];

    }

    /**
     * Gets the template engine cache mode. (see `::CACHE_MODE_*` class constants)
     *
     * It can be used in 2 different modes.
     *
     * The default mode `user` means, use the current defined cache settings.
     *
     * The other `editor` mode means, ignore all cache setting and rebuild all required caches if a template is used.
     *
     * @return string
     */
    public function getCacheMode(): string
    {

        return $this->_data[ 'cache' ][ 'mode' ];

    }

    /**
     * Gets the template compiler always must output to a file. It defines the folder/directory that should be used to
     * store the compiled templates. This setting is required and must point to an existing folder. PHP must have the
     * right to white the compiled template cache file into this folder.
     *
     * @return string
     */
    public function getCacheCompileFolder(): string
    {

        return $this->_data[ 'cache' ][ 'folder' ];

    }

    /**
     * Gets how long (how many seconds) a compiled template file should life before it becomes a invalid state.
     *
     * 0 means, on each template usage it should be checked if something has been changed. And only if so, the cache is
     * newly created.
     *
     * The default value is `60`. It means the compiled template (e.g. The HTML + PHP mix) is marked as valid for the
     * next 60 seconds after compilation without some change checks.
     *
     * @return int
     */
    public function getCacheCompileLifetime(): int
    {

        return $this->_data[ 'cache' ][ 'lifetime' ];

    }

    // </editor-fold>


    // <editor-fold desc="// * * * * * *   S E T T E R   * * * * * *">

    /**
     * Sets the template tag start character sequence. The default value is `{`.
     *
     * You only have to change it if you need an other one.
     *
     * @param string $chars
     *
     * @return Config
     * @throws ArgumentException
     */
    public function setOpenChars( string $chars ): Config
    {

        if ( '' === trim( $chars ) )
        {
            throw new ArgumentException(
                'openChars', $chars, 'Can not use a empty template tag open chars string!'
            );
        }

        $this->_data[ 'openChars' ] = $chars;

        return $this;

    }

    /**
     * Sets the template tag end character sequence. The default value is `}`.
     *
     * You only have to change it if you need an other one.
     *
     * @param string $chars
     *
     * @return Config
     * @throws ArgumentException
     */
    public function setCloseChars( string $chars ): Config
    {

        if ( '' === trim( $chars ) )
        {
            throw new ArgumentException(
                'closeChars', $chars, 'Can not use a empty template tag close chars string!'
            );
        }

        $this->_data[ 'closeChars' ] = $chars;

        return $this;

    }

    /**
     * Sets the template engine cache mode. (see `::CACHE_MODE_*` class constants)
     *
     * It can be used in 2 different modes.
     *
     * The default mode `user` means, use the current defined cache settings.
     *
     * The other `editor` mode means, ignore all cache setting and rebuild all required caches if a template is used.
     *
     * @param string $mode
     *
     * @return Config
     * @throws ArgumentException
     */
    public function setCacheMode( string $mode ): Config
    {

        if ( !in_array( $mode, static::KNOWN_CACHE_MODES, true ) )
        {
            throw new ArgumentException(
                'cacheMode', $mode, 'Can not use a unknown cache mode!'
            );
        }

        $this->_data[ 'cache' ][ 'mode' ] = $mode;

        return $this;

    }

    /**
     * Sets the template compiler always must output to a file. It defines the folder/directory that should be used to
     * store the compiled templates. This setting is required and must point to an existing folder. PHP must have the
     * right to white the compiled template cache file into this folder.
     *
     * @param string $folder
     *
     * @return Config
     * @throws ArgumentException
     */
    public function setCacheCompileFolder( string $folder ): Config
    {

        if ( '' === trim( $folder ) )
        {
            throw new ArgumentException(
                'cacheCompileFolder', $folder,
                'The template engine requires a cache compile folder!'
            );
        }

        if ( !is_dir( $folder ) )
        {
            throw new ArgumentException(
                'cacheCompileFolder', $folder,
                'The template engine cache compile folder must exist!'
            );
        }

        if ( !is_writable( $folder ) )
        {
            throw new ArgumentException(
                'cacheCompileFolder', $folder,
                'The template engine cache compile folder must be writable by PHP!'
            );
        }

        $this->_data[ 'cache' ][ 'folder' ] = $folder;

        return $this;

    }

    /**
     * Sets how long (how many seconds) a compiled template file should life before it becomes a invalid state.
     *
     * 0 means, on each template usage it should be checked if something has been changed. And only if so, the cache is
     * newly created.
     *
     * The default value is `60`. It means the compiled template (e.g. The HTML + PHP mix) is marked as valid for the
     * next 60 seconds after compilation without some change checks.
     *
     * @param int $lifetime min value is 0 and max value is 2678400 is 31 days
     *
     * @return Config
     */
    public function setCacheCompileLifetime( int $lifetime ): Config
    {

        $this->_data[ 'cache' ][ 'lifetime' ] = min( 2678400, max( 0, $lifetime ) );

        return $this;

    }

    /**
     * @param array|null $data
     *
     * @return Config
     * @throws ArgumentException
     */
    public function setData( ?array $data ): Config
    {

        if ( null === $this->_data || null === $data )
        {
            $this->_data = static::DEFAULTS;
        }

        if ( null === $data )
        {
            return $this;
        }

        if ( isset( $data[ 'openChars' ] ) && is_string( $data[ 'openChars' ] ) )
        {
            $this->setOpenChars( $data[ 'openChars' ] );
        }
        if ( isset( $data[ 'closeChars' ] ) && is_string( $data[ 'closeChars' ] ) )
        {
            $this->setCloseChars( $data[ 'closeChars' ] );
        }
        if ( isset( $data[ 'templatesFolder' ] ) && is_string( $data[ 'templatesFolder' ] ) )
        {
            $this->setTemplatesFolder( $data[ 'templatesFolder' ] );
        }
        if ( isset( $data[ 'cache' ] ) && is_array( $data[ 'cache' ] ) )
        {
            if ( isset( $data[ 'cache' ][ 'mode' ] ) && in_array( $data[ 'cache' ][ 'mode' ], [ 'user', 'editor' ] ) )
            {
                $this->setCacheMode( $data[ 'cache' ][ 'mode' ] );
            }
            if ( isset( $data[ 'cache' ][ 'folder' ] ) && is_string( $data[ 'cache' ][ 'folder' ] ) )
            {
                $this->setCacheCompileFolder( $data[ 'cache' ][ 'folder' ] );
            }
            if ( isset( $data[ 'cache' ][ 'lifetime' ] ) && is_numeric( $data[ 'cache' ][ 'lifetime' ] ) )
            {
                $this->setCacheCompileLifetime( (int) $data[ 'cache' ][ 'lifetime' ] );
            }
        }

        return $this;

    }

    /**
     * Sets the templates folder.
     *
     * @param string|null $folder
     *
     * @return Config
     * @throws ArgumentException
     */
    public function setTemplatesFolder( string $folder ): Config
    {

        if ( '' === trim( $folder ) )
        {
            throw new ArgumentException(
                'templatesFolder', $folder,
                'The template engine requires a templates folder!'
            );
        }

        if ( !is_dir( $folder ) )
        {
            throw new ArgumentException(
                'templatesFolder', $folder,
                'The template engine templates folder must exist!'
            );
        }

        $this->_data[ 'templatesFolder' ] = $folder;

        return $this;

    }

    // </editor-fold>


    /**
     * Gets if the compiler cache is configured for usage.
     *
     * @return bool
     */
    public function canUseCompilerCache(): bool
    {

        return null !== $this->getCacheCompileFolder();

    }

    /**
     * Register the current instance for global availability.
     *
     * @return Config
     */
    public function registerAsGlobalInstance(): Config
    {

        static::$_instance = $this;

        return $this;

    }

    /**
     * @return array
     */
    public function toArray(): array
    {

        return $this->_data;

    }

    public function isValid(): bool
    {

        return $this->canUseCompilerCache() && null !== $this->_data[ 'templatesFolder' ];

    }

    // </editor-fold>


    // <editor-fold desc="// – – –   P U B L I C   S T A T I C   M E T H O D S   – – – – – – – – – – – – – – – – –">

    /**
     * Creates a config instance from defined config file.
     *
     * @param string          $configFile PHP, JSON, INI or XML file
     * @param VfsManager|null $vfsManager Optional virtual file system manager.
     *
     * @return Config|null NULL is returned if a unknown/unsupported file format is used.
     * @throws ArgumentException
     * @throws FileNotFoundException
     * @throws IOException
     */
    public static function FromFile( string $configFile, ?VfsManager $vfsManager = null ): ?Config
    {

        $ext = ltrim( strtolower( File::GetExtension( $configFile ) ), '.' );

        switch ( $ext )
        {
            case 'php':
            case 'php5':
            case 'inc':
                return static::FromPHPFile( $configFile, $ext, $vfsManager );
            case 'ini':
                return static::FromINIFile( $configFile, 'ini', $vfsManager );
            case 'json':
                return static::FromINIFile( $configFile, 'json', $vfsManager );
            case 'xml':
                return static::FromINIFile( $configFile, 'xml', $vfsManager );
        }

        return null;

    }

    /**
     * Init a config from defined PHP file with specified required file name extension.
     *
     * Required format is
     *
     * ```php
     * <?php
     *
     * return [
     *    'openChars'       => '{',
     *    'closeChars'      => '}',
     *    'templatesFolder' => 'tpl-root:/templates',
     *    'cache'           => [
     *       'mode'            => 'user',
     *       'folder'          => 'tpl-root:/caches',
     *       'lifetime'        => 60
     *    ]
     * ];
     * ```
     *
     * @param string       $configFile
     * @param null|string  $requiredExtension
     * @param VfsManager|null $vfsManager Optional virtual file system manager.
     *
     * @return null|Config
     * @throws ArgumentException
     * @throws FileNotFoundException
     * @throws IOException
     */
    public static function FromPHPFile(
        string $configFile, ?string $requiredExtension = 'php', ?VfsManager $vfsManager = null ): ?Config
    {

        if ( null !== $vfsManager )
        {
            $configFile = $vfsManager->parsePath( $configFile );
        }

        if ( !file_exists( $configFile ) )
        {
            throw new FileNotFoundException( $configFile, 'Can not load template engine config!' );
        }

        if ( !empty( $requiredExtension ) && $requiredExtension !== static::_getFileType( $configFile ) )
        {
            throw new IOException( $configFile, 'Invalid file name extension!' );
        }

        $data = null;
        try
        {
            /** @noinspection PhpIncludeInspection */
            $data = include $configFile;
        }
        catch ( Throwable $ex )
        {
            throw new ArgumentException(
                'configFile', $configFile,
                'Can not load template engine config from a invalid PHP file!',
                256,
                $ex
            );
        }

        if ( !is_array( $data ) )
        {
            throw new ArgumentException(
                'configFile', $configFile,
                'Can not load template engine config from a invalid PHP file content!'
            );
        }

        if ( null !== $vfsManager )
        {
            if ( isset( $data[ 'templatesFolder' ] ) )
            {
                $data[ 'templatesFolder' ] = $vfsManager->parsePath( $data[ 'templatesFolder' ] );
            }
            if ( isset( $data[ 'cache' ][ 'folder' ] ) )
            {
                $data[ 'cache' ][ 'folder' ] = $vfsManager->parsePath( $data[ 'cache' ][ 'folder' ] );
            }
        }

        $config = new Config( $data, $configFile );
        $config->_type = Config::FILE_TYPE_PHP;

        return $config;

    }

    /**
     * Init a config from defined INI file with specified required file name extension.
     *
     * Required format is
     *
     * ```json
     * {
     *    "openChars"       : "{",
     *    "closeChars"      : "}",
     *    "templatesFolder" : "tpl-root:/templates",
     *    "cache"           : {
     *       "mode"            : "user",
     *       "folder"          : "tpl-root:/caches",
     *       "lifetime"        : 60
     *    }
     * }
     * ```
     *
     * @param string       $configFile
     * @param null|string  $requiredExtension
     * @param VfsManager|null $vfsManager Optional virtual file system manager.
     *
     * @return null|Config
     * @throws ArgumentException
     * @throws FileNotFoundException
     * @throws IOException
     */
    public static function FromJSONFile(
        string $configFile, ?string $requiredExtension = 'json', ?VfsManager $vfsManager = null ): ?Config
    {

        if ( null !== $vfsManager )
        {
            $configFile = $vfsManager->parsePath( $configFile );
        }

        if ( !file_exists( $configFile ) )
        {
            throw new FileNotFoundException( $configFile, 'Can not load template engine config!' );
        }

        if ( !empty( $requiredExtension ) && $requiredExtension !== static::_getFileType( $configFile ) )
        {
            throw new IOException( $configFile, 'Invalid file name extension!' );
        }

        $data = null;
        try
        {
            $data = json_decode( file_get_contents( $configFile ), true );
        }
        catch ( Throwable $ex )
        {
            throw new ArgumentException(
                'configFile', $configFile,
                'Can not load template engine config from a invalid JSON file!',
                256,
                $ex
            );
        }

        if ( !is_array( $data ) )
        {
            throw new ArgumentException(
                'configFile', $configFile,
                'Can not load template engine config from a invalid JSON file content!'
            );
        }

        if ( null !== $vfsManager )
        {
            if ( isset( $data[ 'templatesFolder' ] ) )
            {
                $data[ 'templatesFolder' ] = $vfsManager->parsePath( $data[ 'templatesFolder' ] );
            }
            if ( isset( $data[ 'cache' ][ 'folder' ] ) )
            {
                $data[ 'cache' ][ 'folder' ] = $vfsManager->parsePath( $data[ 'cache' ][ 'folder' ] );
            }
        }

        $config = new Config( $data, $configFile );
        $config->_type = Config::FILE_TYPE_JSON;

        return $config;

    }

    /**
     * Init a config from defined INI file with specified required file name extension.
     *
     * Required format is
     *
     * ```config
     * openChars         = "{"
     * closeChars        = "}"
     * templatesFolder   = "tpl-root:/templates"
     * cache.mode        = "user"
     * cache.folder      = "tpl-root:/caches"
     * cache.lifetime    = 60
     * ```
     *
     * @param string       $configFile
     * @param null|string  $requiredExtension
     * @param VfsManager|null $vfsManager Optional virtual file system manager.
     *
     * @return Config|null
     * @throws ArgumentException
     * @throws FileNotFoundException
     * @throws IOException
     */
    public static function FromINIFile(
        string $configFile, ?string $requiredExtension = 'ini', ?VfsManager $vfsManager = null ): ?Config
    {

        if ( null !== $vfsManager )
        {
            $configFile = $vfsManager->parsePath( $configFile );
        }

        if ( !file_exists( $configFile ) )
        {
            throw new FileNotFoundException( $configFile, 'Can not load template engine config!' );
        }

        if ( !empty( $requiredExtension ) && $requiredExtension !== static::_getFileType( $configFile ) )
        {
            throw new IOException( $configFile, 'Invalid file name extension!' );
        }

        $data = null;
        try
        {
            $data = parse_ini_file( $configFile, false, INI_SCANNER_TYPED );
        }
        catch ( Throwable $ex )
        {
            throw new ArgumentException(
                'configFile', $configFile,
                'Can not load template engine config from a invalid INI file!',
                256,
                $ex
            );
        }

        if ( !is_array( $data ) )
        {
            throw new ArgumentException(
                'configFile', $configFile,
                'Can not load template engine config from a invalid INI file content!'
            );
        }

        if ( null !== $vfsManager )
        {
            if ( isset( $data[ 'templatesFolder' ] ) )
            {
                $data[ 'templatesFolder' ] = $vfsManager->parsePath( $data[ 'templatesFolder' ] );
            }
            if ( isset( $data[ 'cache.folder' ] ) )
            {
                $data[ 'cache.folder' ] = $vfsManager->parsePath( $data[ 'cache.folder' ] );
            }
        }

        $config = static::_convertIniArrayToConfig( $data );
        $config->_type = Config::FILE_TYPE_INI;
        $config->_file = $configFile;

        return $config;

    }

    /**
     * Init a config from defined XML file with specified required file name extension.
     *
     * Required format is
     *
     * ```xml
     * <?xml version="1.0" charset="utf-8"?>
     * <config type="UK.Plate">
     *    <openChars>{</openChars>
     *    <closeChars>}</closeChars>
     *    <templatesFolder>tpl-root:/templates</templatesFolder>
     *    <cache>
     *       <mode>editor</mode>
     *       <folder>tpl-root:/caches</folder>
     *       <lifetime>60</lifetime>
     *    </cache>
     * </config>
     * ```
     *
     * @param string       $configFile
     * @param null|string  $requiredExtension
     * @param VfsManager|null $vfsManager Optional virtual file system manager.
     *
     * @return null|Config
     * @throws ArgumentException
     * @throws FileNotFoundException
     * @throws IOException
     */
    public static function FromXMLFile(
        string $configFile, ?string $requiredExtension = 'xml', ?VfsManager $vfsManager = null ): ?Config
    {

        if ( null !== $vfsManager )
        {
            $configFile = $vfsManager->parsePath( $configFile );
        }

        if ( !file_exists( $configFile ) )
        {
            throw new FileNotFoundException( $configFile, 'Can not load template engine config!' );
        }

        if ( !empty( $requiredExtension ) && $requiredExtension !== static::_getFileType( $configFile ) )
        {
            throw new IOException( $configFile, 'Invalid file name extension!' );
        }

        $xml = null;
        try
        {
            $xml = simplexml_load_file( $configFile );
        }
        catch ( Throwable $ex )
        {
            throw new ArgumentException(
                'configFile', $configFile,
                'Can not load template engine config from a invalid XML file!',
                256,
                $ex
            );
        }

        if ( !( $xml instanceof SimpleXMLElement ) )
        {
            throw new ArgumentException(
                'configFile', $configFile,
                'Can not load template engine config from a invalid XML file content!'
            );
        }

        $config = static::_convertXMLToConfig( $xml, $vfsManager );
        $config->_type = Config::FILE_TYPE_XML;
        $config->_file = $configFile;

        return $config;

    }

    /**
     * Gets the global instance.
     *
     * @return Config
     */
    public static function GetInstance(): Config
    {

        if ( null === static::$_instance )
        {
            static::$_instance = new Config();
        }

        return static::$_instance;

    }

    // </editor-fold>


    // <editor-fold desc="// – – –   P R I V A T E   M E T H O D S   – – – – – – – – – – – – – – – – – – – – – – –">

    /**
     * @param string|null $file
     *
     * @return string|null
     */
    private static function _getFileType( ?string $file ): ?string
    {

        if ( null === $file || '' === $file )
        {
            return null;
        }

        return strtolower( ltrim( ( File::GetExtension( $file ) ?? '' ), '.' ) );

    }

    /**
     * @param array $data
     *
     * @return Config
     * @throws ArgumentException
     */
    private static function _convertIniArrayToConfig( array $data ): Config
    {

        return ( new Config() )
            ->setOpenChars( $data[ 'openChars' ] ?? '{' )
            ->setCloseChars( $data[ 'closeChars' ] ?? '}' )
            ->setTemplatesFolder( $data[ 'templatesFolder' ] ?? '' )
            ->setCacheMode( $data[ 'cache.mode' ] ?? 'user' )
            ->setCacheCompileFolder( $data[ 'cache.folder' ] ?? '' )
            ->setCacheCompileLifetime( $data[ 'cache.lifetime' ] ?? static::DEFAULTS[ 'cache' ][ 'lifetime' ] );

    }

    /**
     * @param SimpleXMLElement $xml
     * @param VfsManager|null  $vfsManager
     *
     * @return Config
     * @throws ArgumentException
     */
    private static function _convertXMLToConfig( SimpleXMLElement $xml, ?VfsManager $vfsManager = null ): Config
    {

        $out = new Config();
        if ( isset( $xml->openChars ) )
        {
            $out->setOpenChars( (string) $xml->openChars );
        }
        if ( isset( $xml->closeChars ) )
        {
            $out->setCloseChars( (string) $xml->closeChars );
        }
        if ( isset( $xml->templatesFolder ) )
        {
            if ( null !== $vfsManager )
            {
                $out->setTemplatesFolder( $vfsManager->parsePath( (string) $xml->templatesFolder ) );
            }
            else
            {
                $out->setTemplatesFolder( (string) $xml->templatesFolder );
            }
        }
        if ( isset( $xml->cache ) )
        {
            if ( isset( $xml->cache->mode ) )
            {
                $out->setCacheMode( (string) $xml->cache->mode );
            }
            if ( isset( $xml->cache->folder ) )
            {
                if ( null !== $vfsManager )
                {
                    $out->setCacheCompileFolder( $vfsManager->parsePath( (string) $xml->cache->folder ) );
                }
                else
                {
                    $out->setCacheCompileFolder( (string) $xml->cache->folder );
                }
            }
            if ( isset( $xml->cache->lifetime ) )
            {
                $out->setCacheCompileLifetime( intval( (string) $xml->cache->lifetime ) );
            }
        }

        return $out;

    }


    // </editor-fold>


}

