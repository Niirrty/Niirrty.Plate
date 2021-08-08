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
use \Niirrty\DB\{Connection, DBException, Driver\SQLite};
use \Niirrty\IO\{Folder, Path};
use \Niirrty\Plate\TagParser\{
    BlockTagParser, EndTagParser, IncludeTagParser, IPlateTagParser, TranslationTagParser, VarInTagParser, VarOutTagParser
};
use function \Niirrty\{substring};


/**
 * The template compiler.
 */
class Compiler
{


    #region // – – –   P R I V A T E   F I E L D S   – – – – – – – – – – – – – – – – – – – – – – – –

    private Config $_config;

    private Connection $_cacheDB;

    /**
     * @type Connection[]
     */
    private static array $_cacheDBs = [];

    /**
     * @var IPlateTagParser[]|array
     */
    private array $_tagParser;

    #endregion


    #region // – – –   C O N S T R U C T O R   - - - - - - - - - - - - - - - - - - - - - - - - - - -

    /**
     * Compiler constructor.
     *
     * @param Config|null $config
     *
     * @throws ArgumentException
     */
    public function __construct( ?Config $config = null )
    {

        $this->_config = $config ?? Config::GetInstance();

        if ( !$this->_config->isValid() )
        {
            throw new ArgumentException(
                'config',
                $config,
                'Can not compile plate templates with a invalid config!'
            );
        }

        $this->_tagParser = [
            new VarInTagParser( $this->_config ),
            new VarOutTagParser( $this->_config ),
            new IncludeTagParser( $this->_config ),
            new TranslationTagParser( $this->_config ),
            new BlockTagParser( $this->_config ),
            new EndTagParser( $this->_config )
        ];

    }

    #endregion


    #region // - - -   P U B L I C   M E T H O D S   - - - - - - - - - - - - - - - - - - - - - - - -

    /**
     * Compiles the template file.
     *
     * @param string      $tplFile The template file that should be compiled
     * @param string|null $package Optional package name. Is used as sub folder inside the template folder
     *
     * @return string              Return the full path of the compiled php file.
     * @throws CompileException
     * @throws \Throwable
     * @throws DBException
     */
    public function compile( string $tplFile, ?string $package = null ): string
    {

        if ( null !== $package && '' !== $package )
        {
            $tplFilePath = Path::Combine( $this->_config->getTemplatesFolder(), $package, $tplFile );
        }
        else
        {
            $tplFilePath = Path::Combine( $this->_config->getTemplatesFolder(), $tplFile );
        }

        if ( !file_exists( $tplFilePath ) )
        {
            throw new CompileException( $tplFilePath, 'The template file not exists!' );
        }

        $compileFolder = $this->_config->getCacheCompileFolder();
        $compileLifeTime = $this->_config->getCacheCompileLifetime();
        if ( null === $compileFolder || '' === $compileFolder )
        {
            throw new CompileException( $tplFile, 'There is no usable compile folder configured!' );
        }

        // If a package is defined, add it to cache folder and ensure it exists.
        if ( null !== $package && '' !== trim( $package ) )
        {
            $compileFolder = Path::Combine( $compileFolder, $package );
            if ( !is_dir( $compileFolder ) )
            {
                try
                {
                    Folder::Create( $compileFolder, 0775 );
                }
                catch ( \Throwable $ex )
                {
                    throw new CompileException(
                        $tplFile,
                        'Can not create package depending compile cache folder.',
                        256,
                        $ex
                    );
                }
            }
        }

        $compiledFile = Path::Combine( $compileFolder, $tplFile . '.php' );
        $tmpFolder = \dirname( $compiledFile );

        if ( ! \is_dir( $tmpFolder ) )
        {
            try
            {
                Folder::Create( $tmpFolder, 0775 );
            }
            catch ( \Throwable $ex )
            {
                throw new CompileException(
                    $tplFile,
                    'Can not create package depending compile cache folder "' . $tmpFolder . '".',
                    256,
                    $ex
                );
            }
        }

        /**
         * Open the cache SQLITE v3 DB
         */
        $this->initCacheDbConnection();

        if ( 0 < $compileLifeTime &&
             Config::CACHE_MODE_EDITOR !== $this->_config->getCacheMode() &&
             \file_exists( $compiledFile ) )
        {
            // There is a lifetime defined and the cache file exists

            if ( \filemtime( $compiledFile ) + $compileLifeTime >= \time() )
            {
                // The existing cache file is valid, use it
                return $compiledFile;
            }

            // The cache lifetime is reached => check for changes

            // First get the HASH of the last cache file (or FALSE)
            $checksum = $this->_cacheDB->fetchScalar(
                'SELECT `checksum` FROM `caches` WHERE template_file = ?',
                [ $tplFile ],
                false
            );

            if ( $checksum === \hash_file( 'sha512', $tplFilePath ) )
            {
                // all is fine => use the cache
                \touch( $compiledFile );

                return $compiledFile;
            }

            // The cache is invalid => go to recreation

        }

        // Create a new cache
        $this->parse( $tplFilePath, $compiledFile, $package );

        $tplFileCount = $this->_cacheDB->fetchScalar(
            'SELECT COUNT(*) FROM `caches` WHERE template_file = ?',
            [ $tplFile ],
            false
        );

        if ( false === $tplFileCount || 0 === ( (int) $tplFileCount ) )
        {
            $this->_cacheDB->exec(
                'INSERT INTO `caches` (template_file, checksum) VALUES (?, ?)',
                [ $tplFile, hash_file( 'sha512', $tplFilePath ) ]
            );
        }
        else
        {
            $this->_cacheDB->exec(
                'UPDATE `caches` SET checksum = ? WHERE template_file = ?',
                [ hash_file( 'sha512', $tplFilePath ), $tplFile ]
            );
        }

        return $compiledFile;

    }

    /**
     * Registers a external template tag parser.
     *
     * @param IPlateTagParser $parser
     * @return Compiler
     */
    public function registerTagParser( IPlateTagParser $parser ) : Compiler
    {

        $this->_tagParser[] = $parser;

        return $this;

    }

    #endregion


    #region // - - -   P R I V A T E   M E T H O D S   - - - - - - - - - - - - - - - - - - - - - - -

    /**
     * @param string      $tplFilePath
     * @param string      $compiledFile
     * @param string|null $package
     * @throws \Throwable
     */
    private function parse( string $tplFilePath, string $compiledFile, ?string $package )
    {

        $w = null;
        $openChars = $this->_config->getOpenChars();
        $closeChars = $this->_config->getCloseChars();
        $closeLen = \strlen( $closeChars );
        $openLen = \strlen( $openChars );

        try
        {

            $isCommentOpen = false;

            $w = \fopen( $compiledFile, 'wb' );
            \fwrite( $w, "<" . "?php if ( ! isset( \$____plate ) ) { extract( \$this->data ); \$hasTranslator = null !== \$this->translator; } ?>\n" );
            $lines = \file( $tplFilePath );

            foreach ( $lines as $l )
            {

                //remove white space from the end of the line
                $line = rtrim( $l, "\r\n\t " );

                // If the line is empty, simple write a new line
                if ( $line == '' )
                {
                    fwrite( $w, "\n" );
                    continue;
                }

                while ( '' !== $line )
                {

                    if ( $isCommentOpen )
                    {
                        // A comment is currently open => search for close part
                        if ( -1 === ( $idx = \Niirrty\strPos( $line, '*' . $closeChars ) ) )
                        {
                            // No comment close part found, ignore this line
                            $line = '';
                            continue;
                        }
                        // Comment end part found
                        // Remember the new state
                        $isCommentOpen = false;
                        // Extract all after the comment
                        $line = \str_ends_with( $line, '*' . $closeChars )
                            ? ''
                            : substring( $line, $idx + 1 + $closeLen );
                        if ( strlen( $line ) < 1 )
                        {
                            // Comment closes at ent of line => nothing comes after
                            fwrite( $w, "\n" );
                            break;
                        }
                        continue;
                    }

                    // There is currently no comment open

                    // Find out if the line contains a open tag char sequence
                    if ( -1 === ( $idx = \Niirrty\strPos( $line, $openChars ) ) )
                    {
                        // There is no template tag inside this line
                        fwrite( $w, $line . "\n" );
                        break;
                    }

                    // Process the different known tag types

                    // Get all before the opening tag char sequence
                    $beforeTagOpen = ( 0 === $idx )
                        ? ''
                        : substring( $line, 0, $idx );
                    // Get all after the opening tag char sequence
                    $afterTagOpen = substring( $line, $idx + $openLen );

                    // If everything is before the open tag char sequence, write it to cache
                    if ( '' !== $beforeTagOpen )
                    {
                        fwrite( $w, $beforeTagOpen );
                    }

                    // If nothing is after the tag open char seq. write open chars + new line and we are done with the line
                    if ( '' === $afterTagOpen )
                    {
                        fwrite( $w, $openChars . "\n" );
                        break;
                    }

                    // If the line not defines a complete tag write all to cache and do no replacements
                    if ( -1 === ( $idx = \Niirrty\strPos( $afterTagOpen, $closeChars ) ) )
                    {
                        fwrite( $w, $openChars . $afterTagOpen . "\n" );
                        break;
                    }

                    $tagDefinition = substring( $afterTagOpen, 0, $idx );
                    $afterTagClose = substring( $afterTagOpen, $idx + $closeLen );
                    $newLineAfter  = "\n";
                    $handled       = false;

                    foreach ( $this->_tagParser as $tagParser )
                    {
                        if ( $tagParser->parse( $tagDefinition, $afterTagClose, $newLineAfter, $package ) )
                        {
                            $res = $tagParser->getCompiled();
                            $afterTagClose = $res->getAfterTagClose();
                            $newLineAfter = $res->getNewLineAfter();
                            \fwrite( $w, $res->getPhpCode() );
                            $handled = true;
                            break;
                        }
                    }

                    if ( $handled )
                    {
                        if ( '' !== $afterTagClose )
                        {
                            $line = $afterTagClose;
                            continue;
                        }
                        else
                        {
                            \fwrite( $w, $newLineAfter );
                            break;
                        }
                    }

                    switch ( $tagDefinition[ 0 ] )
                    {

                        case '*':
                            if ( '*' !== $tagDefinition[ strlen( $tagDefinition ) - 1 ] )
                            {
                                $isCommentOpen = true;
                            }
                            $newLineAfter = "";
                            break;

                        default :
                            \fwrite( $w, $this->_config->getOpenChars() );
                            $afterTagClose = $tagDefinition .
                                             $this->_config->getCloseChars() .
                                             $afterTagClose;
                            break;

                    }

                    if ( '' !== $afterTagClose )
                    {
                        $line = $afterTagClose;
                    }
                    else
                    {
                        \fwrite( $w, $newLineAfter );
                        break;
                    }

                }

            }

            \fclose( $w );
            $w = null;
            \chmod( $compiledFile, 0750 );

        }
        catch ( \Throwable $ex )
        {
            if ( null !== $w )
            {
                \fclose( $w );
            }
            throw $ex;
        }

    }

    /**
     * @throws ArgumentException
     * @throws DBException
     */
    private function initCacheDbConnection()
    {

        if ( isset( static::$_cacheDBs[ $this->_config->getCacheCompileFolder() ] ) )
        {
            $this->_cacheDB = static::$_cacheDBs[ $this->_config->getCacheCompileFolder() ];
        }
        else
        {
            $dbFile = Path::Combine( $this->_config->getCacheCompileFolder(), 'plate.sqlite3' );
            $dbExist = \file_exists( $dbFile );
            $this->_cacheDB = new Connection( ( new SQLite() )->setDb( $dbFile ) );
            // Create the DB with the table if it not exists
            if ( !$dbExist )
            {
                $sql = '
                 CREATE
                    TABLE
                       `caches`
                    (
	                    `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
	                    `template_file` TEXT NOT NULL UNIQUE,
	                    `checksum` TEXT NOT NULL
                    )';
                $this->_cacheDB->exec( $sql );
            }
        }

    }

    #endregion


    /**
     * Gets if the defined string is a valid template variable name
     *
     * @param string $varDefinition
     *
     * @return bool
     */
    public static function IsValidVarDefinition( string $varDefinition ): bool
    {

        if ( 0 === strlen( $varDefinition ) || '$' !== $varDefinition[ 0 ] )
        {
            return false;
        }

        $charTypes = [
            'array'   => \str_contains( $varDefinition, '[' ) &&
                         \str_contains( $varDefinition, ']' ),
            'object'  => \str_contains( $varDefinition, '->' ) ||
                         ( \str_contains( $varDefinition, '(' ) &&
                           \str_contains( $varDefinition, ')' ) ),
            'space'   => \str_contains( $varDefinition, ' ' ),
            'special' => (bool) preg_match( '~[^A-Za-z_0-9\\[\\]()>\'"$ -]+~', $varDefinition ),
        ];

        if ( $charTypes[ 'special' ] )
        {
            return false;
        }
        $varPattern = '~^\\$[A-Za-z_][A-Za-z_0-9]*$~';

        if ( !$charTypes[ 'array' ] && !$charTypes[ 'object' ] && !$charTypes[ 'space' ] )
        {
            return (bool) preg_match( $varPattern, $varDefinition );
        }

        if ( !$charTypes[ 'array' ] && !$charTypes[ 'object' ] && $charTypes[ 'space' ] )
        {
            // A space is not a valid part of a simple variable name
            return false;
        }

        // Split $varDefinition into pieces
        $parts = preg_split(
            '~([\\[\\]()]|->)~',
            $varDefinition,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        if ( !preg_match( $varPattern, $parts[ 0 ] ) )
        {
            return false;
        }

        $openSquareBrackets = 0;
        $openRoundBrackets = 0;

        for ( $i = 1, $c = count( $parts ); $i < $c; $i++ )
        {
            $part = trim( $parts[ $i ] );
            if ( '' === $part || '->' === $part )
            {
                continue;
            }
            if ( '[' === $part )
            {
                $openSquareBrackets++;
                continue;
            }
            if ( ']' === $part )
            {
                $openSquareBrackets--;
                continue;
            }
            if ( '(' === $part )
            {
                $openRoundBrackets++;
                continue;
            }
            if ( ')' === $part )
            {
                $openRoundBrackets--;
                continue;
            }
            if ( static::IsValidVarDefinition( $part ) )
            {
                continue;
            }

            return false;
        }

        return 0 === $openSquareBrackets && 0 === $openRoundBrackets;

    }


}

