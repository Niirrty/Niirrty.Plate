# Supported Plate template tags

The template engine comes with a set of predefined, known template tags

## Extend with own tags

Since version 0.3.1 there are user defined template supported.

For an example implementation see `examples/MyHelpTagParser.php`

## Format

A template tag must be defined, by a open and a close character sequence.

**Default** is `{` as open character and `}` for closing.

After the opening char sequence, one of the following character must follow:

* `*` : Comments
* `$` : Template-Var read access
* `+$`: Defina a new Template-Var
* `#` : Include other templates
* `~` : Includes Translations via `Niirrty\Translation\Translator` instance

## Template var access

* `$Varname`
* `$Varname->Property`    The variable is an object and its property `Property` is accessed
* `$Varname->Method()`    The variable is an object and its method `Method()` is accessed. Parameters are accepted by need
* `$Varname.1`            The variable is an array and access to array value for index 1
* `$Varname.ArrayKey`     The variable is an array and access to array value for key `'ArrayKey'`
* `$Varname.$ArrayKey`    The variable is an array and access to array value for key, defined by template variable `$ArrayKey`

Each template variable can be handled by 0-n filter function. For example masking/escaping for HTML output, etc.

Filters are appended/separeted by the pipe `|` character.

e.g.: `$Varname|escape`

## Variable read access

```
{$Varname|filter1|filter2|…}
```

Each variable can be passed to one or more filters, separated by the pipe `|` symbol.

The usable predefined filter(s) are the following:

* `escape`|`escape-html` (default): Escape $variable content for use in HTML context
* `asIt`: Insert the template var content as it, without some escaping
* `asJSON`: Convert variable content to JSON format before output.

Other known php functions (must accept a call with a single parameter) can also been used.

You can also access variables as arrays and objects by using regular PHP object access syntax
and also arrays by dot notation:

```php
// $foo.0.bar->baz().blub.$i
// Will result in
$foo[ 0 ][ 'bar' ]->baz()[ 'blub' ][ $i ]
```


## Variable write/create access

```
{+$Varname = 'Whatever 1'}
```

Only simple variables (int|float|bool|string) can be assigned!


## Comments

Comments can be one- or multi line and must always start with `open chars` followed by `*`,
and end with `*` followed by `close chars`.

```
{* Single line comment *}
```

or

```
{* Multi
   line
   comment *}
```

Comments are not included in the resulting output format!


## Blocks (Loops and Conditions)

Loops and Conditions always ends with a end tag in format `{/TAGNAME]` or alternatively `{/end}` or `{end}`


### Loops 

#### Foreach - Loops

```
  {foreach from=$Varname key=key value=value}
    {$key|asJSON}: {$value->__toString()|asIt}
  {/foreach}
```

The following params are known:

* `from`  The iterable object or array.
* `key`   Optional name of the key variable.
* `value` The name of the value variable.

#### For - Loops

```
  {for from=$Varname index=i count=c step=1 init=0}
    {$Varname.$i.Name}: {$Varname.$i.Value}
    {if ($i%1) == 0}
      <strong>{$Varname.$i.Status}</strong>
    {else}
      …
    {/if}
  {/for}
```

The following params are known:

* `from`  The array.
* `index` Name of the index variable (default = $i)
* `count` Name of the count variable (default = $c)
* `step`  Step size (positive or negative) (default = 1)
* `init`  Optional initial index (default = 0)


### Conditions

```
  {if isset($Varname.Key)}
    …
  {elseif ($Varname % 2) != 0}
    …
  {elseif \count($Varname) < 3}
    …
  {elseif empty($Varname)}
    …
  {else}
    …
  {/if}
```

## Includes

```
{# file/to/include.tpl}
```

The file part can also point to an other known template var It must always be relative to current configured template folder

```
{# $pathOfTplFile}
```

## Translations

Let you use translations from a `Niirrty\Translation\Translator` instance.

```
{~ <SourceName>::<Identifier>=<Default Translation>|<filter1>|<filter2>|...}
```

All parts (excluding the filters) can be replaced by template variables

```
{~ $sourceName::$identifier=$defaultTranslation|filter1|filter2|...}
```

* `SourceName`: The source name of the translation, defining the translations set
* `Identifier`: The Translation identifier/name
* `DefaultTranslation`: The optional default translation text
* `filter`: All filters. Each variable can be passed to one or more filters,
  separated by the pipe `|` symbol. The predefined filter(s) are the following, or any known PHP function:
  * `escape`|`escape-html` (default): Escape $variable content for use in HTML context
  * `asIt`: Insert the template var content as it, without some escaping
  * `asJSON`: Convert variable content to JSON format before output.