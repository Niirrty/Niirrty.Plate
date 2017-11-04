# TEMPLATE-TAGS

A template tag is already defined by a opening and a closing character sequence.

**Default** is `{` as open character and `}` for closing.

If you want to change it you can do it by different ways:
 
1. Use a configuration file. The known formats and settings are described [here](/configuration-file.md)
2. Configure it programmatically.

After the opening char sequest one of the following character must follow:

* `*` : Comments
* `$` : Template-Var read access
* `+$`: Defina a new Template-Var
* `#` : Include other templates


## Template var access


### Variable read access

```
{$Varname|filterfunction1:'param',…|filterfunction2|…}
```

Each variable can be passed to one or more functions, separated by the pipe `|` symbol.

The used function(s) must accept the template variable as first parameter and can use more optional parameters
It must return the new value.

The parameter list starts with `:`, each parameter must be defined PHP compatible. 2 or more must be separated by `,`

You can also access variables as arrays and objects by using regular PHP syntax:

```php
$foo[ 0 ][ 'bar' ]->baz()
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
    {$key|escape:'json'}: {$value->__toString()}
  {/foreach}
```

The following params are known:

* `from`  The iterable object or array.
* `key`   Optional name of the key variable.
* `value` The name of the value variable.

#### For - Loops

```
  {for from=$Varname index=i count=c step=1 init=0}
    {$Varname[$i]['Name']}: {$Varname[$i]['Value']}
    {if ($i%1) == 0}
      <strong>{$Varname[$i]['Status']}</strong>
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
  {if isset($Varname['Key'])}
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
