<?php


declare( strict_types=1 );


namespace Niirrty\Plate;


use Niirrty\ArgumentException;
use Niirrty\ArrayHelper;
use Niirrty\DB\Connection;
use Niirrty\DB\Driver\SQLite;
use Niirrty\IO\Folder;
use Niirrty\IO\Path;
use function Niirrty\strContains;


/**
 * The template compiler.
 */
class Compiler
{


   // <editor-fold desc="// – – –   P R I V A T E   F I E L D S   – – – – – – – – – – – – – – – – – – – – – – – –">

   /**
    * @var \Niirrty\Plate\Config
    */
   private $_config;

   /**
    * @type \Niirrty\DB\Connection
    */
   private $_cacheDB;

   /**
    * @type \Niirrty\DB\Connection[]
    */
   private static $_cacheDBs = [];

   // </editor-fold>


   // <editor-fold desc="// – – –   C O N S T R U C T O R   - - - - - - - - - - - - - - - - - - - - - - - - - - -">

   /**
    * Compiler constructor.
    *
    * @param \Niirrty\Plate\Config|null $config
    * @throws \Niirrty\ArgumentException
    */
   public function __construct( ?Config $config = null )
   {

      $this->_config  = $config ?? Config::GetInstance();

      if ( ! $this->_config->isValid() )
      {
         throw new ArgumentException(
            'config',
            $config,
            'Can not compile plate templates with a invalid config!'
         );
      }

   }

   // </editor-fold>


   // <editor-fold desc="// - - -   P U B L I C   M E T H O D S   - - - - - - - - - - - - - - - - - - - - - - - -">

   /**
    * Compiles the template file.
    *
    * @param  string      $tplFile The template file that should be compiled
    * @param  string|null $package Optional package name
    * @return string               Return the full path of the compiled php file.
    * @throws \Niirrty\Plate\CompileException
    */
   public function compile( $tplFile, ?string $package = null ) : string
   {

      if ( null !== $package && '' !== $package )
      {
         $tplFilePath  = Path::Combine( $this->_config->getTemplatesFolder(), $package, $tplFile );
      }
      else
      {
         $tplFilePath  = Path::Combine( $this->_config->getTemplatesFolder(), $tplFile );
      }

      if ( ! \file_exists( $tplFilePath ) )
      {
         throw new CompileException( $tplFilePath, 'The template file not exists!' );
      }

      $compileFolder   = $this->_config->getCacheCompileFolder();
      $compileLifeTime = $this->_config->getCacheCompileLifetime();
      if ( null === $compileFolder || '' === $compileFolder )
      {
         throw new CompileException( $tplFile, 'There is no usable compile folder configured!' );
      }

      // If a package is defined, add it to cache folder and ensure it exists.
      if ( null !== $package && '' !== \trim( $package ) )
      {
         $compileFolder = Path::Combine( $compileFolder, $package );
         if ( ! \is_dir( $compileFolder ) )
         {
            try { Folder::Create( $compileFolder, 0775 ); }
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
         try { Folder::Create( $tmpFolder, 0775 ); }
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
            [ $tplFile, \hash_file( 'sha512', $tplFilePath ) ]
         );
      }
      else
      {
         $this->_cacheDB->exec(
            'UPDATE `caches` SET checksum = ? WHERE template_file = ?',
            [ \hash_file( 'sha512', $tplFilePath ), $tplFile ]
         );
      }

      return $compiledFile;

   }

   // </editor-fold>


   // <editor-fold desc="// - - -   P R I V A T E   M E T H O D S   - - - - - - - - - - - - - - - - - - - - - - -">

   private function parse( string $tplFilePath, string $compiledFile, ?string $package  )
   {

      $w          = null;
      $openChars  = $this->_config->getOpenChars();
      $closeChars = $this->_config->getCloseChars();
      $closeLen   = \strlen( $closeChars );
      $openLen    = \strlen( $openChars );

      try
      {

         $isCommentOpen = false;

         $w = \fopen( $compiledFile, 'wb' );
         fwrite( $w, "<?php extract( \$this->data ); ?>\n" );
         $lines = \file( $tplFilePath );

         foreach ( $lines as $l )
         {

            $line = \rtrim( $l, "\r\n\t " );

            if ( $line == '' )
            {
               \fwrite( $w, "\n" );
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
                  $line = \Niirrty\strEndsWith( $line, '*' . $closeChars )
                     ? ''
                     : \Niirrty\substring( $line, $idx + 1 + $closeLen );
                  if ( \strlen( $line ) < 1 )
                  {
                     // Comment closes at ent of line => nothing comes after
                     \fwrite( $w, "\n" );
                     break;
                  }
                  continue;
               }

               // There is currently no comment open

               // Find out if the line contains a open tag char sequence
               if ( -1 === ( $idx = \Niirrty\strPos( $line, $openChars ) ) )
               {
                  // There is no template tag inside this line
                  \fwrite( $w, $line . "\n" );
                  break;
               }

               // Process the different known tag types

               // Get all before the opening tag char sequence
               $beforeTagOpen = ( 0 === $idx )
                  ? ''
                  : \Niirrty\substring( $line, 0, $idx );
               // Get all after the opening tag char sequence
               $afterTagOpen  = \Niirrty\substring( $line, $idx + $openLen );

               // If everything is before the open tag char sequence, write it to cache
               if ( '' !== $beforeTagOpen )
               {
                  \fwrite( $w, $beforeTagOpen );
               }

               // If nothing is after the tag open char seq. write open chars + new line and we are done with the line
               if ( ''  === $afterTagOpen )
               {
                   \fwrite( $w, $openChars . "\n" );
                   break;
               }

               // If the line not defines a complete tag write all to cache and do no replacements
               if ( -1 === ( $idx = \Niirrty\strPos( $afterTagOpen, $closeChars ) ) )
               {
                  \fwrite( $w, $openChars . $afterTagOpen . "\n" );
                  break;
               }

               $tagDefinition   = \Niirrty\substring( $afterTagOpen, 0, $idx );
               $afterTagClose   = \Niirrty\substring( $afterTagOpen, $idx + $closeLen );
               $newLineAfter    = "\n";

               switch ( $tagDefinition[ 0 ] )
               {

                  // Variable read access
                  case '$':
                     \fwrite( $w, $this->extractVarEcho( $tagDefinition ) );
                     $newLineAfter = " \n";
                     break;

                  // Variable write/create access
                  case '+':
                     if ( ! isset( $tagDefinition[ 1 ] ) || $tagDefinition[ 1 ] != '$' )
                     {
                        // Invalid/unknown tag
                        \fwrite( $w, $openChars );
                        $afterTagClose = $tagDefinition . $closeChars . $afterTagClose;
                        $newLineAfter = " \n";
                        break;
                     }
                     \fwrite( $w, $this->extractVarAdd( $tagDefinition ) );
                     $newLineAfter = "\n";
                     break;

                  // Include other template
                  case '#':
                     \fwrite( $w, $this->extractInclude( $tagDefinition, $package ) );
                     $newLineAfter = "";
                     break;

                  case '*':
                     if ( '*' !== $tagDefinition[ \strlen( $tagDefinition ) - 1 ] )
                     {
                        $isCommentOpen = true;
                     }
                     $newLineAfter = "";
                     break;

                  case 'i': // if
                  case 'e': // else|elseif|end
                  case 'f': // for|foreach
                     if ( 'end' === $tagDefinition )
                     {
                        $newLineAfter = " \n";
                     }
                     \fwrite( $w, $this->extractBlock( $tagDefinition ) );
                     break;

                  case '/':
                     $newLineAfter = " \n";
                     \fwrite( $w, $this->extractEnd( $tagDefinition ) );
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

   private function initCacheDbConnection()
   {

      if ( isset( static::$_cacheDBs[ $this->_config->getCacheCompileFolder() ] ) )
      {
         $this->_cacheDB = static::$_cacheDBs[ $this->_config->getCacheCompileFolder() ];
      }
      else
      {
         $dbFile         = Path::Combine( $this->_config->getCacheCompileFolder(), 'plate.sqlite3' );
         $dbExist        = \file_exists( $dbFile );
         $this->_cacheDB = new Connection( ( new SQLite() )->setDb( $dbFile ) );
         // Create the DB with the table if it not exists
         if ( ! $dbExist )
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

   private function extractVarEcho( $tagDefinition )
   {

      // Split into var and filter
      $tmp     = \preg_split( '~(?<!\\\\)\\|~', $tagDefinition );
      $var     = $tagDefinition;

      if ( \count( $tmp ) > 1 )
      {

         // There are one or more filters defined

         // Get the variable name
         $var = $this->normalizeVar( \trim( $tmp[ 0 ] ) );

         // Get the filters and reverse them: e.g. trim|escape:'htmlall' => escape:'htmlall'|trim
         $filters = \array_reverse( ArrayHelper::Remove($tmp, 0 ) );
         $appendix = '';
         $contents = '<?php echo ';

         if ( 1 > \count( $filters ) )
         {
            $filters = [ 'escape' ];
         }

         // Loop all defined filters
         foreach ( $filters as $filter )
         {
            switch ( \strtolower( $filter ) )
            {
               case 'escape':
               case 'escape-html':
               case 'escapehtml':
                  $filter = '\\Niirrty\\escapeXML';
                  break;
               case 'asit':
                  $filter = '';
                  break;
               case 'asjson':
                  $filter = '\\json_encode';
                  break;
               default:
                  if ( ! \function_exists( $filter ) )
                  {
                     $filter = '';
                  }
                  break;
            }
            if ( '' === $filter )
            {
               continue;
            }
            $appendix .= ' )';
            if ( $filter[ 0 ] !== '\\' )
            {
               $contents .= '\\';
            }
            $contents .= "{$filter}( ";
         }

         $contents .= "{$var}{$appendix}; ?>";
         return $contents;

      }
      else
      {

         $var = $this->normalizeVar( $var );

      }

      $contents = "<?= {$var}; ?>";
      return $contents;

   }

   private function extractVarAdd( $tagDefinition )
   {

      # +$var = 'value'

      return '<?php ' . \ltrim( $tagDefinition, '+' ) . '; ?> ';

   }

   private function extractInclude( $tagDefinition, ?string $package )
   {

      // {# file/to/include.tpl}
      // or
      // {# $fileFromEngineVariable}

      // Remove the leading # and whitespaces before and after.
      $tagDefinition = \trim( \ltrim( $tagDefinition, '#' ) );

      if ( \preg_match( '~^\\$[A-Za-z_][A-Za-z0-9_]*$~', $tagDefinition ) )
      {
         return '<?php $this->includeWithCaching( ' .
                $tagDefinition .
                ', ' .
                \json_encode( $package ) .
                ' ); ?> ';
      }

      if ( null !== $package && '' !== $package )
      {
         $tplFile = Path::Combine( $this->_config->getTemplatesFolder(), $package, $tagDefinition );
      }
      else
      {
         $tplFile = Path::Combine( $this->_config->getTemplatesFolder(), $tagDefinition );
      }

      if ( ! \file_exists( $tplFile ) )
      {
         return '<!-- PLATE-ERROR: Template ' . $tagDefinition . ' not exists! -->';
      }

      $comp      = new Compiler( $this->_config );
      $cacheFile = Path::Unixize( $comp->compile( $tagDefinition, $package ) );

      return "<?php include '{$cacheFile}'; ?> ";

   }

   private function extractBlock( $tagDefinition )
   {

      // if else|elseif|end for|foreach

      if ( $tagDefinition == 'end' )
      {
         return '<?php } ?>';
      }

      if ( $tagDefinition == 'else' )
      {
         return '<?php } else { ?>';
      }

      $tmp = \explode( ' ', $tagDefinition, 2 );

      if ( \count( $tmp ) !== 2 )
      {
         return $this->_config->getOpenChars() . $tagDefinition . $this->_config->getCloseChars();
      }

      if ( 'foreach' === \strtolower( $tmp[ 0 ] ) )
      {
         // foreach from=$Varname key=key value=value
         $attr = ArrayHelper::ParseHtmlAttributes( $tmp[ 1 ] );
         if ( ! isset( $attr[ 'from' ], $attr[ 'value' ] ) )
         {
            return $this->_config->getOpenChars() . $tagDefinition . $this->_config->getCloseChars();
         }
         $attr[ 'from'  ] = '$' . \ltrim( \trim( $this->normalizeVar( $attr[ 'from'  ] ) ), '$' );
         $attr[ 'value' ] = '$' . \ltrim( \trim( $attr[ 'value' ] ), '$' );
         if ( isset( $attr[ 'key' ] ) )
         {
            $attr[ 'key' ] = ( '$' . \ltrim( \trim( $attr[ 'key'  ] ) ) );
            $attr[ 'as' ]  = $attr[ 'key' ] . ' => ' . $attr[ 'value' ];
         }
         else
         {
            $attr[ 'key' ] = '';
            $attr[ 'as' ]  = $attr[ 'value' ];
         }
         return "<?php foreach( {$attr['from']} as {$attr['as']} ) { ?>";
      }

      if ( 'for' === \strtolower( $tmp[ 0 ] ) )
      {
         // for from=$array index=i count=c step=1 init=0
         $attr = ArrayHelper::ParseHtmlAttributes( $tmp[ 1 ] );
         if ( ! isset( $attr[ 'from' ] ) )
         {
            return $this->_config->getOpenChars() . $tagDefinition . $this->_config->getCloseChars();
         }
         $attr[ 'from'  ]   = '$' . \ltrim( \trim( $this->normalizeVar( $attr[ 'from'  ] ) ), '$' );
         $attr[ 'index' ]   = isset( $attr[ 'index' ] ) ? ( '$' . \ltrim( \trim( $attr[ 'index'  ] ), '$' ) ) : '$i';
         $attr[ 'count' ]   = isset( $attr[ 'count' ] ) ? ( '$' . \ltrim( \trim( $attr[ 'count'  ] ), '$' ) ) : '$c';
         $attr[ 'step'  ]   = isset( $attr[ 'step'  ] ) ? (int) $attr[ 'step' ] : 1;
         $attr[ 'reverse' ] = false;
         if ( 0 === $attr[ 'step' ] ) { $attr[ 'step'  ] = 1; }
         if ( 0 >   $attr[ 'step' ] )
         {
            $attr[ 'step'    ] = \abs( $attr[ 'step'  ] );
            $attr[ 'reverse' ] = true;
         }

         if ( $attr[ 'reverse' ] )
         {
            // for ( $i = \count( $from ); $i > -1; $i-- ) {
            $dec = ( 1 === $attr[ 'step' ] )
               ? ( $attr['index'] . '--' )
               : $attr['index'] . ' -= ' . $attr[ 'step' ];
            return "<?php for( {$attr['index']} = count( {$attr['from']} ); {$attr['index']} > -1; {$dec} ) { ?>";
         }

         $attr[ 'init' ] = isset( $attr[ 'init' ] ) ? ( (int) $attr[ 'init' ] ) : 0;
         // for ( $i = 0, $c = \count( $from ); $i < 0; $i++ ) {
         $inc = ( 1 === $attr[ 'step' ] )
            ? ( $attr['index'] . '++' )
            : $attr['index'] . ' += ' . $attr[ 'step' ];
         return "<?php for( {$attr['index']} = {$attr['init']}, {$attr['count']} = count( {$attr['from']} ); " .
                "{$attr['index']} < {$attr['count']}; {$inc} ) { ?>";
      }

      $tmp[ 1 ] = \trim( $tmp[ 1 ] );

      return "<?php {$tmp[0]} ( {$tmp[1]} ) { ?>";

   }

   private function extractEnd( $tagDefinition )
   {
      // /if /for /foreach

      if ( \in_array( $tagDefinition, [ '/if', '/for', '/foreach', '/end' ] ) )
      {
         return '<?php } ?>';
      }

      return $this->_config->getOpenChars() . $tagDefinition . $this->_config->getCloseChars();

   }

   private function normalizeVar( string $varDefinition ) : string
   {

      $parts = \preg_split( '~([."\'-])~', $varDefinition, -1, \PREG_SPLIT_DELIM_CAPTURE );

      $partsCount = \count( $parts );

      if ( 2 > $partsCount )
      {
         return $varDefinition;
      }

      $tmp = [];

      for ( $i = 0, $j = 0; $i < $partsCount; $i += 2 )
      {

         if ( $i === 0 )
         {
            $tmp[] = [ 'operator' => false, 'parts' => [ \trim( $parts[ $i ] ) ] ];
            continue;
         }

         switch ( $parts[ $i - 1 ] )
         {

            case '.':
               if ( '.' === $tmp[ $j ][ 'operator' ] )
               {
                  $tmp[ $j ][ 'parts' ][] = \trim( $parts[ $i ] );
                  break;
               }
               if ( '"' === $tmp[ $j ][ 'operator' ] || '\'' === $tmp[ $j ][ 'operator' ] )
               {
                  $tmp[ $j ][ 'parts' ][] = $parts[ $i - 1 ];
                  $tmp[ $j ][ 'parts' ][] = $parts[ $i ];
                  break;
               }
               $j++;
               $tmp[] = [ 'operator' => '.', 'parts' => [ \trim( $parts[ $i ] ) ] ];
               break;

            case '-':
               if ( false === $tmp[ $j ][ 'operator' ] )
               {
                  $tmp[ $j ][ 'parts' ][] = \trim( $parts[ $i - 1 ] );
                  $tmp[ $j ][ 'parts' ][] = \trim( $parts[ $i ] );
                  break;
               }
               if ( '"' === $tmp[ $j ][ 'operator' ] || '\'' === $tmp[ $j ][ 'operator' ] )
               {
                  $tmp[ $j ][ 'parts' ][] = $parts[ $i - 1 ];
                  $tmp[ $j ][ 'parts' ][] = $parts[ $i ];
                  break;
               }
               $j++;
               $tmp[] = [ 'operator' => false, 'parts' => [ \trim( $parts[ $i - 1 ] ) ] ];
               $tmp[ $j ][ 'parts' ][] = \trim( $parts[ $i ] );
               break;

            case '"':
               if ( '"' === $tmp[ $j ][ 'operator' ] )
               {
                  // Current " maybe close the open string "…
                  if ( $this->strEndsWithEscapeChar( $tmp[ $j ][ 'parts' ][ \count( $tmp[ $j ][ 'parts' ] ) - 1 ] ) )
                  {
                     // The " is escaped
                     $tmp[ $j ][ 'parts' ][] = $parts[ $i - 1 ];
                     $tmp[ $j ][ 'parts' ][] = $parts[ $i ];
                     break;
                  }
                  // Current " closes the open string "…
                  $tmp[ $j ][ 'parts' ][] = $parts[ $i - 1 ];
                  $tmp[ $j ][ 'operator' ] = false;
                  $j++;
                  $tmp[] = [ 'operator' => false, 'parts' => [ \trim( $parts[ $i ] ) ] ];
                  break;
               }
               // Current " opens a new string
               $j++;
               $tmp[] = [ 'operator' => '"', 'parts' => [ $parts[ $i - 1 ], $parts[ $i ] ] ];
               break;

            case '\'':
               if ( '\'' === $tmp[ $j ][ 'operator' ] )
               {
                  // Current ' maybe close the open string '…
                  if ( $this->strEndsWithEscapeChar( $tmp[ $j ][ 'parts' ][ \count( $tmp[ $j ][ 'parts' ] ) - 1 ] ) )
                  {
                     // The ' is escaped
                     $tmp[ $j ][ 'parts' ][] = $parts[ $i - 1 ];
                     $tmp[ $j ][ 'parts' ][] = $parts[ $i ];
                     break;
                  }
                  // Current ' close the open string '…
                  $tmp[ $j ][ 'parts' ][]  = $parts[ $i - 1 ];
                  $tmp[ $j ][ 'operator' ] = false;
                  $j++;
                  $tmp[] = [ 'operator' => false, 'parts' => [ $parts[ $i ] ] ];
                  break;
               }
               // Current ' opens a new string
               $j++;
               $tmp[] = [ 'operator' => '\'', 'parts' => [ $parts[ $i - 1 ], $parts[ $i ] ] ];
               break;

         }

      }

      $normalized = '';
      foreach ( $tmp as $partsGroup )
      {

         if ( false === $partsGroup[ 'operator' ] )
         {
            $normalized .= \implode( '', $partsGroup[ 'parts' ] );
            continue;
         }

         for ( $i = 0, $c = \count( $partsGroup[ 'parts' ] ); $i < $c; $i++ )
         {
            if ( '' !== $partsGroup[ 'parts' ][ $i ] &&
                 ( '$' === $partsGroup[ 'parts' ][ $i ][ 0 ] || \is_numeric( $partsGroup[ 'parts' ][ $i ] ) ) )
            {
               continue;
            }
            $partsGroup[ 'parts' ][ $i ] = \json_encode( $partsGroup[ 'parts' ][ $i ] );
         }

         $normalized .= '[' . \implode( '][', $partsGroup[ 'parts' ] ) . ']';

      }

      return $normalized;

   }

   private function strEndsWithEscapeChar( string $str ) : bool
   {
      if ( ! \preg_match( '~(\\\\+)$~', $str, $matches ) )
      {
         return false;
      }
      $backSlashCount = \strlen( $matches[ 1 ] );
      return 0 !== $backSlashCount && 0 !== ( $backSlashCount % 2 );
   }

   # </editor-fold>


   /**
    * Gets if the defined string is a valid template variable name
    *
    * @param string $varDefinition
    * @return bool
    */
   public static function IsValidVarDefinition( string $varDefinition ) : bool
   {

      if ( 0 === \strlen( $varDefinition ) || '$' !== $varDefinition[ 0 ] )
      {
         return false;
      }

      $charTypes = [
         'array'   => strContains( $varDefinition, '[' ) &&
                      strContains( $varDefinition, ']' ),
         'object'  => strContains( $varDefinition, '->' ) ||
                    ( strContains( $varDefinition, '(' ) &&
                      strContains( $varDefinition, ')' ) ),
         'space'   => strContains( $varDefinition, ' ' ),
         'special' => (bool) \preg_match( '~[^A-Za-z_0-9\\[\\]\\(\\)>\'"$ -]+~', $varDefinition )
      ];

      if ( $charTypes[ 'special' ] )
      {
         return false;
      }
      $varPattern = '~^\\$[A-Za-z_][A-Za-z_0-9]*$~';

      if ( ! $charTypes[ 'array' ] && ! $charTypes[ 'object' ] && ! $charTypes[ 'space' ] )
      {
         return (bool) \preg_match( $varPattern, $varDefinition );
      }

      if ( ! $charTypes[ 'array' ] && ! $charTypes[ 'object' ] && $charTypes[ 'space' ] )
      {
         // A space is not a valid part of a simple variable name
         return false;
      }

      // Split $varDefinition into pieces
      $parts = \preg_split(
         '~([\\[\\]\\(\\)]|->)~',
         $varDefinition,
         -1,
         PREG_SPLIT_DELIM_CAPTURE
      );

      if ( ! \preg_match( $varPattern, $parts[ 0 ] ) )
      {
         return false;
      }

      $openSquareBrackets = 0;
      $openRoundBrackets  = 0;

      for ( $i = 1, $c = \count( $parts ); $i < $c; $i++ )
      {
         $part = \trim( $parts[ $i ] );
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

