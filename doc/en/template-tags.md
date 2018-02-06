# TEMPLATE-TAGS

A template tag is already defined by a opening and a closing character sequence.

**Default** is `{` as open character and `}` for closing.

If you want to change it you can do it by different ways:
 
1. Use a configuration file. The known formats and settings are described [here](/configuration-file.md)
2. Configure it programmatically.

After the opening char sequence, one of the following character must follow:

* `*` : Comments
* `$` : Template-Var read access
* `+$`: Defina a new Template-Var
* `#` : Include other templates


## Template var access


### Variable read access

```
{$Varname|filter1|filter2|…}
```

Each variable can be passed to one or more filters, separated by the pipe `|` symbol.

The usable filter(s) are the following

* `escape`|`escape-html` (default): Escape $variable content for use in HTML context
* `asIt`: Insert the template var content as it, without some escaping
* `asJSON`: Convert variable content to JSON format before output.

You can also access variables as arrays and objects by using regular PHP object access syntax
and also arrays by dot notation:

```php
$foo.0.bar->baz().blub.$i
// Will result in
$foo[0]['bar']->baz()['blub'][$i]
```


### Variable write/create access

```
{+$Varname = 'Whatever 1'}
```

Only simple variables can be assigned!


## Comments

Comments can be one- or multi line an must always start with `open chars` followed by `*`,
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

The file part cal also point to an other known template var

```
{# $pathOfTplFile}
```
