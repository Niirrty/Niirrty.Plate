# The Niirrty.Plate configuration file

If you will use the template engine in many different scripts, the best way for doing configure some equal settings,
is to use a configuration file.

## Config properties

The following configuration properties are known:

### `openChars` (string)

   Each template tag starts with a specific character sequence. The default value is `{`.
   You only have to set it if you need an other one.

### `closeChars` (string)

   Each template tag ends with a specific character sequence. The default value is `}`.
   You only have to set it if you need an other one.

### `templatesFolder` (string)

   The templates folder. For usage with config files you have to use a virtual file system by
   `Config::FromFile( … )`

### `cache.mode` [editor|user] (string)

   The template engine cache can be used in 2 different modes.
   
   The default mode `user` means, use the current defined cache settings
   
   The other `editor` mode means, ignore all cache setting and rebuild all caches if a template is used.


### `cache.folder` (string)

   The template compiler always must output to a file. It defines the folder/directory that should be used to store the
   compiled templates. This setting is required and must point to an existing folder. PHP must have the right to white
   the compiled template cache file into this folder.

### `cache.lifetime` (int)

   Defines how long (how many seconds) a compiled template file should life before it becomes a invalid state.
   
   0 means, on each template usage it should be checked if something has been changed. And only if so, the cache is
   newly created.
   
   The default value is `60`. It means the compiled template (e.g. The HTML + PHP mix) is marked as valid for the next
   60 seconds after compilation without some change checks.
   

## Config file formats   

Config file can be defined currently by the following formats:

### The JSON config file format

```json
{
   "openChars"       : "{",
   "closeChars"      : "}",
   "templatesFolder" : "tplbase:/templates",
   "cache"           : {
      "mode"            : "user",
      "folder"          : "tplbase:/compile-cache",
      "lifetime"        : 120
   }
}
```


## The INI config file format

```ini
openChars       = "{"
closeChars      = "}"
templatesFolder = "tplbase:/templates"
cache.mode      = "user"
cache.folder    = "tplbase:/compile-cache"
cache.lifetime  = 120
```



## The PHP config file format

```php
<?php

return [
   'openChars'       => '{',
   'closeChars'      => '}',
   'templatesFolder' => 'tplbase:/templates',
   'cache'           => [
      'mode'            => 'user',
      'folder'          => 'tplbase:/compile-cache',
      'lifetime'        => 120
   ]
];

```


## The XML config file format

```xml
<?xml version="1.0" charset="utf-8"?>
<config type="UK.Plate">
   <openChars>{</openChars>
   <closeChars>}</closeChars>
   <templatesFolder>tplbase:/templates</ignoreWhitespace>
   <cache>
      <mode>editor</mode>,
      <folder>tplbase:/compile-cache</folder>
      <lifetime>120</lifetime>
   </cache>
</config>
```



## How to use the template config file?

You can simple set it globally by using:

```php
<?php
\Niirrty\Plate\Config::FromFile(
   'config:/plate-config.json',
   \Niirrty\IO\Vfs\Manager::Create()
      ->addHandler(
         \Niirrty\IO\Vfs\Handler::Create( 'config folder' )
            ->setProtocol( 'config', ':/' )
            ->setRootFolder( __DIR__ )
         )
      ->addHandler(
         \Niirrty\IO\Vfs\Handler::Create( 'tpl base folder' )
            ->setProtocol( 'tplbase', ':/' )
            ->setRootFolder( __DIR__ )
         )
   )->registerAsGlobalInstance();
```

or without the `->registerAsGlobalInstance()` if you want to use more then one.

and access it elsewhere by need:

```php
<?php
$configArray = \Niirrty\Plate\Config::GetInstance()->toArray();
echo 'Open=', $configArray[ 'openChars' ], ' Close=', $configArray[ 'closeChars' ];
```